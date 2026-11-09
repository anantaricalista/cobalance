<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kas_bank_service
{
    private $types = array('kas_masuk' => 'Kas masuk', 'kas_keluar' => 'Kas keluar', 'transfer_kas' => 'Transfer kas/bank');
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
    }

    public function jenis()
    {
        return $this->types;
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.jenis_transaksi, t.nomor_transaksi, t.tanggal,
            t.status, t.total, t.deskripsi, t.lampiran_nama, a.kode_akun, a.nama_akun
            FROM transaksi t LEFT JOIN akun a ON a.id_perusahaan=t.id_perusahaan AND a.id_akun=t.id_akun_kas
            WHERE t.id_perusahaan=? AND t.jenis_transaksi IN ('kas_masuk','kas_keluar','transfer_kas')
            ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $company = $this->company();
        $this->id($id);
        $query = $this->ci->db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . "
            FROM transaksi t WHERE t.id_perusahaan=? AND t.id_transaksi=?
            AND t.jenis_transaksi IN ('kas_masuk','kas_keluar','transfer_kas')", array($company, $id));
        $this->ok($query);
        $transaction = $query->row();
        if (!$transaction) {
            return null;
        }
        $query = $this->ci->db->query('SELECT d.id_akun, d.deskripsi, d.subtotal, a.kode_akun, a.nama_akun
            FROM detail_transaksi d JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            WHERE d.id_perusahaan=? AND d.id_transaksi=? ORDER BY d.id_detail_transaksi', array($company, $id));
        $this->ok($query);
        $transaction->details = $query->result_array();
        return $transaction;
    }

    public function simpan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data kas dan bank tidak valid.');
        }
        if ($id !== null && $id !== '') {
            $this->id($id);
        } else {
            $id = null;
        }
        if ($id === null) {
            if (!isset($data['jenis']) || !is_string($data['jenis']) || !isset($this->types[$data['jenis']])) {
                throw new InvalidArgumentException('Jenis transaksi kas dan bank tidak valid.');
            }
            $type = $data['jenis'];
            return $this->ci->transaksi_service->buat_draft($type, $tanggal, $this->description($data, $type),
                function ($transaction_id, $company, $db) use ($type, $tanggal, $data, $lampiran) {
                    $clean = $this->validate($company, $type, $data);
                    $this->write_draft($transaction_id, $company, $clean, $db);
                    $this->ci->lampiran_service->simpan($company, $transaction_id, $lampiran, false);
                });
        }
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $data, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT id_transaksi, jenis_transaksi, nomor_transaksi, status FROM transaksi
                WHERE id_perusahaan=? AND id_transaksi=? AND jenis_transaksi IN ('kas_masuk','kas_keluar','transfer_kas')
                FOR UPDATE", array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft kas dan bank milik perusahaan ini yang dapat diubah.');
            }
            $clean = $this->validate($company, $transaction->jenis_transaksi, $data);
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                ->update('transaksi', array('tanggal' => $tanggal)));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('detail_transaksi'));
            $this->write_draft($id, $company, $clean, $db);
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            $company = $transaction->id_perusahaan;
            $type = $transaction->jenis_transaksi;
            if (!isset($this->types[$type]) || !$transaction->id_akun_kas) {
                throw new DomainException('Transaksi kas dan bank tidak ditemukan.');
            }
            $query = $db->query('SELECT id_akun, subtotal FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=?
                ORDER BY id_detail_transaksi FOR UPDATE', array($company, $transaction->id_transaksi));
            $this->ok($query);
            $rows = $query->result();
            if (!$rows || ($type === 'transfer_kas' && count($rows) !== 1)) {
                throw new DomainException('Rincian transaksi kas dan bank tidak valid.');
            }
            $this->cash_account($company, $transaction->id_akun_kas);
            $journal = array();
            $total = 0;
            foreach ($rows as $row) {
                $amount = $this->cents($row->subtotal);
                $total = $this->add_cents($total, $amount);
                if ($type === 'transfer_kas') {
                    $this->cash_account($company, $row->id_akun);
                    if ((string) $row->id_akun === (string) $transaction->id_akun_kas) {
                        throw new DomainException('Akun asal dan tujuan transfer harus berbeda.');
                    }
                    $this->add($journal, $row->id_akun, $amount, 0);
                } else {
                    $this->counter_account($company, $row->id_akun, $transaction->id_akun_kas);
                    $this->add($journal, $row->id_akun, $type === 'kas_keluar' ? $amount : 0,
                        $type === 'kas_masuk' ? $amount : 0);
                }
            }
            if ($total !== $this->cents($transaction->total)) {
                throw new DomainException('Total tidak sesuai dengan rincian yang tersimpan.');
            }
            $this->add($journal, $transaction->id_akun_kas, $type === 'kas_masuk' ? $total : 0,
                $type === 'kas_masuk' ? 0 : $total);
            $normalized = array();
            foreach ($journal as $row) {
                $net = $row['debit_cents'] - $row['credit_cents'];
                if ($net !== 0) {
                    $normalized[] = array('id_akun' => $row['id_akun'],
                        'debit' => $net > 0 ? $this->money($net) : '0.00',
                        'kredit' => $net < 0 ? $this->money(-$net) : '0.00');
                }
            }
            return $normalized;
        });
    }

    public function batalkan($id, $date, $reason)
    {
        return $this->ci->transaksi_service->batalkan($id, $date, $reason, function ($transaction) {
            if (!isset($this->types[$transaction->jenis_transaksi])) {
                throw new DomainException('Transaksi kas dan bank tidak ditemukan.');
            }
        });
    }

    private function description($data, $type)
    {
        return isset($data['deskripsi']) && is_string($data['deskripsi']) && trim($data['deskripsi']) !== ''
            ? trim($data['deskripsi']) : $this->types[$type];
    }

    private function write_draft($id, $company, $data, $db)
    {
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
            'id_akun_kas' => $data['id_akun_kas'], 'referensi' => $data['referensi'],
            'deskripsi' => $data['deskripsi'], 'total_sebelum_pajak' => $data['total'],
            'total_pajak' => '0.00', 'total' => $data['total']
        )));
        foreach ($data['details'] as $row) {
            $this->ok($db->insert('detail_transaksi', array(
                'id_perusahaan' => $company, 'id_transaksi' => $id, 'id_akun' => $row['id_akun'],
                'deskripsi' => $row['deskripsi'], 'jumlah' => '1.0000', 'harga' => $row['jumlah'],
                'nilai_sebelum_pajak' => $row['jumlah'], 'nilai_pajak' => '0.00', 'subtotal' => $row['jumlah']
            )));
        }
    }

    private function validate($company, $type, $data)
    {
        if (!isset($data['id_akun_kas'], $data['details']) || !is_array($data['details'])
            || !$data['details'] || count($data['details']) > 200) {
            throw new InvalidArgumentException('Data harus memuat akun kas/bank dan 1 sampai 200 rincian.');
        }
        if ($type === 'transfer_kas' && count($data['details']) !== 1) {
            throw new InvalidArgumentException('Transfer kas/bank hanya memiliki satu akun tujuan.');
        }
        $this->id($data['id_akun_kas']);
        $this->cash_account($company, $data['id_akun_kas']);
        $reference = isset($data['referensi']) && is_string($data['referensi']) ? trim($data['referensi']) : '';
        $description = $this->description($data, $type);
        if ($this->length($reference) > 100 || $this->length($description) > 255) {
            throw new InvalidArgumentException('Referensi atau keterangan terlalu panjang.');
        }
        $rows = array();
        $total = 0;
        foreach ($data['details'] as $item) {
            if (!is_array($item) || !isset($item['id_akun'], $item['jumlah'])) {
                throw new InvalidArgumentException('Rincian tidak lengkap.');
            }
            $this->id($item['id_akun']);
            $amount = $item['jumlah'];
            if ((!is_string($amount) && !is_int($amount))
                || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount) || $this->cents($amount) < 1) {
                throw new InvalidArgumentException('Jumlah harus lebih dari nol dengan maksimal 2 desimal.');
            }
            if ($type === 'transfer_kas') {
                $this->cash_account($company, $item['id_akun']);
                if ((string) $item['id_akun'] === (string) $data['id_akun_kas']) {
                    throw new DomainException('Akun asal dan tujuan transfer harus berbeda.');
                }
            } else {
                $this->counter_account($company, $item['id_akun'], $data['id_akun_kas']);
            }
            $line = isset($item['deskripsi']) && is_string($item['deskripsi']) ? trim($item['deskripsi']) : '';
            if ($this->length($line) > 255) {
                throw new InvalidArgumentException('Keterangan rincian terlalu panjang.');
            }
            $cents = $this->cents($amount);
            $total = $this->add_cents($total, $cents);
            $rows[] = array('id_akun' => (int) $item['id_akun'], 'jumlah' => $this->money($cents),
                'deskripsi' => $line === '' ? $this->types[$type] : $line);
        }
        return array('id_akun_kas' => (int) $data['id_akun_kas'], 'referensi' => $reference === '' ? null : $reference,
            'deskripsi' => $description, 'details' => $rows, 'total' => $this->money($total));
    }

    private function cash_account($company, $id)
    {
        $this->account($company, $id, '1100');
    }

    // Akun lawan tidak boleh kas/bank itu sendiri, piutang, atau utang usaha (dikelola modul tagihan).
    private function counter_account($company, $id, $cash)
    {
        if ((string) $id === (string) $cash) {
            throw new DomainException('Akun rincian tidak boleh sama dengan akun kas/bank.');
        }
        $codes = $this->account($company, $id, null);
        foreach (array('1200', '2100') as $blocked) {
            if (in_array($blocked, $codes, true)) {
                throw new DomainException('Akun piutang dan utang usaha dikelola melalui modul penerimaan dan pembayaran.');
            }
        }
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
        $codes = array();
        $seen = array();
        while ($account) {
            if (isset($seen[$account->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$account->id_akun] = true;
            $codes[] = $account->kode_akun;
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
        if ($root !== null && !in_array($root, $codes, true)) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
        return $codes;
    }

    private function add(&$journal, $account, $debit, $credit)
    {
        $key = (string) $account;
        if (!isset($journal[$key])) {
            $journal[$key] = array('id_akun' => $account, 'debit_cents' => 0, 'credit_cents' => 0);
        }
        $journal[$key]['debit_cents'] = $this->add_cents($journal[$key]['debit_cents'], $debit);
        $journal[$key]['credit_cents'] = $this->add_cents($journal[$key]['credit_cents'], $credit);
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
            throw new RuntimeException('Operasi database kas dan bank gagal.');
        }
    }
}
