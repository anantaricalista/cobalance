<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pembayaran_utang_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status, t.total,
            t.id_pemasok, p.nama_pemasok, t.lampiran_nama
            FROM transaksi t JOIN pemasok p ON p.id_perusahaan=t.id_perusahaan AND p.id_pemasok=t.id_pemasok
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='pembayaran_utang'
            ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $company = $this->company();
        $this->id($id);
        $query = $this->ci->db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . ", p.nama_pemasok
            FROM transaksi t JOIN pemasok p ON p.id_perusahaan=t.id_perusahaan AND p.id_pemasok=t.id_pemasok
            WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi='pembayaran_utang'",
            array($company, $id));
        $this->ok($query);
        $transaction = $query->row();
        if (!$transaction) {
            return null;
        }
        $query = $this->ci->db->query('SELECT a.id_transaksi_tagihan, a.jumlah, i.nomor_transaksi, i.tanggal, i.total
            FROM alokasi_transaksi a
            JOIN transaksi i ON i.id_perusahaan=a.id_perusahaan AND i.id_transaksi=a.id_transaksi_tagihan
            WHERE a.id_perusahaan=? AND a.id_transaksi=? ORDER BY a.id_alokasi_transaksi', array($company, $id));
        $this->ok($query);
        $transaction->alokasi = $query->result_array();
        return $transaction;
    }

    public function tagihan_terbuka($supplier)
    {
        $company = $this->company();
        $this->id($supplier);
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.jenis_transaksi, t.tanggal,
            t.jatuh_tempo, t.total, t.total - COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                AND r.status='diposting' AND r.jenis_transaksi='pembayaran_utang'), 0) AS sisa
            FROM transaksi t WHERE t.id_perusahaan=? AND t.id_pemasok=? AND t.status='diposting'
            AND (t.jenis_transaksi='saldo_awal_utang'
                OR (t.jenis_transaksi IN ('pembelian', 'perolehan_aset') AND t.cara_pembayaran='kredit'))
            HAVING sisa > 0 ORDER BY t.tanggal, t.id_transaksi", array($company, $supplier));
        $this->ok($query);
        return $query->result_array();
    }

    public function simpan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data pembayaran utang tidak valid.');
        }
        if ($id !== null && $id !== '') {
            $this->id($id);
        } else {
            $id = null;
        }
        if ($id === null) {
            return $this->ci->transaksi_service->buat_draft('pembayaran_utang', $tanggal,
                $this->description($data),
                function ($transaction_id, $company, $db) use ($tanggal, $data, $lampiran) {
                    $clean = $this->validate($company, $tanggal, $data);
                    $this->write_draft($transaction_id, $company, $clean, $db);
                    $this->ci->lampiran_service->simpan($company, $transaction_id, $lampiran, false);
                });
        }
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $data, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT id_transaksi, nomor_transaksi, status FROM transaksi
                WHERE id_perusahaan=? AND id_transaksi=? AND jenis_transaksi='pembayaran_utang' FOR UPDATE",
                array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft pembayaran utang milik perusahaan ini yang dapat diubah.');
            }
            $clean = $this->validate($company, $tanggal, $data);
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                ->update('transaksi', array('tanggal' => $tanggal)));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('alokasi_transaksi'));
            $this->write_draft($id, $company, $clean, $db);
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            $company = $transaction->id_perusahaan;
            if ($transaction->jenis_transaksi !== 'pembayaran_utang' || !$transaction->id_pemasok
                || !$transaction->id_akun_kas) {
                throw new DomainException('Transaksi pembayaran utang tidak ditemukan.');
            }
            $query = $db->query('SELECT id_transaksi_tagihan, jumlah FROM alokasi_transaksi
                WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_alokasi_transaksi FOR UPDATE',
                array($company, $transaction->id_transaksi));
            $this->ok($query);
            $rows = array();
            $total = 0;
            foreach ($query->result() as $row) {
                $rows[] = array('id' => $row->id_transaksi_tagihan, 'jumlah' => $row->jumlah);
                $total = $this->add_cents($total, $this->cents($row->jumlah));
            }
            if (!$rows) {
                throw new DomainException('Pembayaran utang harus mengalokasikan minimal satu tagihan.');
            }
            if ($total !== $this->cents($transaction->total)) {
                throw new DomainException('Total pembayaran tidak sama dengan jumlah alokasi.');
            }
            $this->check_invoices($company, $transaction->id_pemasok, $transaction->tanggal, $rows);
            $this->account($company, $transaction->id_akun_kas, '1100');
            $payable = $this->setting($company, 'utang_usaha', '2100');
            $amount = $this->money($total);
            return array(
                array('id_akun' => $payable, 'debit' => $amount, 'kredit' => '0.00'),
                array('id_akun' => $transaction->id_akun_kas, 'debit' => '0.00', 'kredit' => $amount)
            );
        });
    }

    public function batalkan($id, $date, $reason)
    {
        return $this->ci->transaksi_service->batalkan($id, $date, $reason, function ($transaction) {
            if ($transaction->jenis_transaksi !== 'pembayaran_utang') {
                throw new DomainException('Transaksi pembayaran utang tidak ditemukan.');
            }
        });
    }

    private function description($data)
    {
        return isset($data['deskripsi']) && is_string($data['deskripsi']) && trim($data['deskripsi']) !== ''
            ? trim($data['deskripsi']) : 'Pembayaran utang';
    }

    private function write_draft($id, $company, $data, $db)
    {
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
            'id_pemasok' => $data['id_pemasok'], 'id_akun_kas' => $data['id_akun_kas'],
            'referensi' => $data['referensi'], 'deskripsi' => $data['deskripsi'],
            'total_sebelum_pajak' => $data['total'], 'total_pajak' => '0.00', 'total' => $data['total']
        )));
        foreach ($data['alokasi'] as $row) {
            $this->ok($db->insert('alokasi_transaksi', array('id_perusahaan' => $company, 'id_transaksi' => $id,
                'id_transaksi_tagihan' => $row['id'], 'jumlah' => $row['jumlah'])));
        }
    }

    private function validate($company, $date, $data)
    {
        if (!isset($data['id_pemasok'], $data['id_akun_kas'], $data['alokasi']) || !is_array($data['alokasi'])
            || !$data['alokasi'] || count($data['alokasi']) > 200) {
            throw new InvalidArgumentException('Data harus memuat pemasok, akun kas/bank, dan 1 sampai 200 alokasi.');
        }
        $this->id($data['id_pemasok']);
        $this->id($data['id_akun_kas']);
        $reference = isset($data['referensi']) && is_string($data['referensi']) ? trim($data['referensi']) : '';
        $description = $this->description($data);
        if ($this->length($reference) > 100 || $this->length($description) > 255) {
            throw new InvalidArgumentException('Referensi atau keterangan terlalu panjang.');
        }
        $supplier = $this->ci->db->query('SELECT id_pemasok FROM pemasok
            WHERE id_perusahaan=? AND id_pemasok=? AND aktif=1 FOR UPDATE', array($company, $data['id_pemasok']));
        $this->ok($supplier);
        if (!$supplier->row()) {
            throw new DomainException('Pemasok tidak ditemukan atau tidak aktif.');
        }
        $this->account($company, $data['id_akun_kas'], '1100');
        $rows = array();
        $seen = array();
        $total = 0;
        foreach ($data['alokasi'] as $item) {
            if (!is_array($item) || !isset($item['id_transaksi_tagihan'], $item['jumlah'])) {
                throw new InvalidArgumentException('Alokasi tagihan tidak lengkap.');
            }
            $this->id($item['id_transaksi_tagihan']);
            $invoice = (int) $item['id_transaksi_tagihan'];
            if (isset($seen[$invoice])) {
                throw new DomainException('Satu tagihan tidak boleh dialokasikan lebih dari sekali.');
            }
            $seen[$invoice] = true;
            $amount = $item['jumlah'];
            if ((!is_string($amount) && !is_int($amount))
                || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount) || $this->cents($amount) < 1) {
                throw new InvalidArgumentException('Jumlah alokasi harus lebih dari nol dengan maksimal 2 desimal.');
            }
            $cents = $this->cents($amount);
            $total = $this->add_cents($total, $cents);
            $rows[] = array('id' => $invoice, 'jumlah' => $this->money($cents));
        }
        $this->check_invoices($company, $data['id_pemasok'], $date, $rows);
        return array('id_pemasok' => (int) $data['id_pemasok'], 'id_akun_kas' => (int) $data['id_akun_kas'],
            'referensi' => $reference === '' ? null : $reference, 'deskripsi' => $description,
            'alokasi' => $rows, 'total' => $this->money($total));
    }

    private function check_invoices($company, $supplier, $date, $rows)
    {
        foreach ($rows as $row) {
            $query = $this->ci->db->query("SELECT t.id_pemasok, t.tanggal, t.total, t.jenis_transaksi, t.cara_pembayaran,
                t.status, COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                    AND r.status='diposting' AND r.jenis_transaksi='pembayaran_utang'), 0) AS terbayar
                FROM transaksi t WHERE t.id_perusahaan=? AND t.id_transaksi=? FOR UPDATE",
                array($company, $row['id']));
            $this->ok($query);
            $invoice = $query->row();
            if (!$invoice || (string) $invoice->id_pemasok !== (string) $supplier || $invoice->status !== 'diposting'
                || !($invoice->jenis_transaksi === 'saldo_awal_utang'
                    || (in_array($invoice->jenis_transaksi, array('pembelian', 'perolehan_aset'), true)
                        && $invoice->cara_pembayaran === 'kredit'))) {
                throw new DomainException('Tagihan tidak ditemukan, bukan milik pemasok ini, atau belum diposting.');
            }
            if ($date < $invoice->tanggal) {
                throw new DomainException('Tanggal pembayaran tidak boleh sebelum tanggal tagihan.');
            }
            if ($this->cents($row['jumlah']) > $this->cents($invoice->total) - $this->cents($invoice->terbayar)) {
                throw new DomainException('Jumlah alokasi melebihi sisa utang tagihan.');
            }
        }
    }

    private function setting($company, $key, $root)
    {
        $query = $this->ci->db->query('SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan=? FOR UPDATE', array($company, $key));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengaturan akun ' . $key . ' belum tersedia.');
        }
        $account = $query->row()->id_akun;
        $this->account($company, $account, $root);
        return $account;
    }

    private function account($company, $id, $root)
    {
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account) {
            throw new DomainException('Akun transaksi tidak ditemukan.');
        }
        $children = $this->ci->db->query('SELECT id_akun FROM akun
            WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE', array($company, $id));
        $this->ok($children);
        if ($children->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan pada transaksi.');
        }
        $found = false;
        $seen = array();
        while ($account) {
            if (isset($seen[$account->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$account->id_akun] = true;
            if ($account->kode_akun === $root) {
                $found = true;
            }
            if (!$account->id_akun_induk) {
                break;
            }
            $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
                array($company, $account->id_akun_induk));
            $this->ok($query);
            $account = $query->row();
            if (!$account) {
                throw new DomainException('Induk akun tidak ditemukan.');
            }
        }
        if (!$found) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $this->id($company);
        $this->id($user);
        $query = $this->ci->db->query('SELECT id_pengguna FROM pengguna
            WHERE id_perusahaan=? AND id_pengguna=?', array($company, $user));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return $company;
    }

    private function id($id)
    {
        if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id)
            || (int) $id < 1 || (int) $id > 2147483647) {
            throw new InvalidArgumentException('ID tidak valid.');
        }
    }

    private function date($date)
    {
        if (!is_string($date) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
            || !($value = DateTime::createFromFormat('!Y-m-d', $date)) || $value->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }
    }

    private function cents($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount)) {
            throw new DomainException('Nominal transaksi melebihi batas database.');
        }
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }

    private function add_cents($left, $right)
    {
        if ($right < 0 || $left > 999999999999999 - $right) {
            throw new DomainException('Total transaksi melebihi batas database.');
        }
        return $left + $right;
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database pembayaran utang gagal.');
        }
    }
}
