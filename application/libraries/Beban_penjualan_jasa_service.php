<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Beban_penjualan_jasa_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
    }

    public function jasa_terjual()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT d.id_detail_transaksi, d.deskripsi, d.nilai_sebelum_pajak,
            t.id_transaksi, t.nomor_transaksi, t.tanggal, p.nama_pelanggan,
            COALESCE((SELECT SUM(rd.nilai_sebelum_pajak) FROM detail_transaksi rd
                JOIN transaksi r ON r.id_perusahaan=rd.id_perusahaan AND r.id_transaksi=rd.id_transaksi
                WHERE rd.id_perusahaan=d.id_perusahaan AND rd.id_detail_asal=d.id_detail_transaksi
                AND r.jenis_transaksi='retur_penjualan' AND r.status='diposting'), 0) AS retur,
            COALESCE((SELECT SUM(b.total) FROM transaksi b
                JOIN detail_transaksi bd ON bd.id_perusahaan=b.id_perusahaan AND bd.id_transaksi=b.id_transaksi
                WHERE b.id_perusahaan=t.id_perusahaan AND b.id_transaksi_asal=t.id_transaksi
                AND b.jenis_transaksi='beban_penjualan_jasa' AND b.status='diposting'
                AND bd.id_detail_asal=d.id_detail_transaksi), 0) AS beban
            FROM detail_transaksi d
            JOIN transaksi t ON t.id_perusahaan=d.id_perusahaan AND t.id_transaksi=d.id_transaksi
            JOIN produk pr ON pr.id_perusahaan=d.id_perusahaan AND pr.id_produk=d.id_produk
            LEFT JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penjualan' AND t.status='diposting'
                AND pr.tipe_produk='jasa'
            ORDER BY t.tanggal DESC, t.id_transaksi DESC, d.id_detail_transaksi", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status, t.total,
            t.cara_pembayaran, t.id_akun_kas, t.id_pemasok, t.jatuh_tempo,
            s.nomor_transaksi AS nomor_penjualan, d.id_detail_asal, d.id_akun, sd.deskripsi AS jasa,
            d.deskripsi AS rincian, a.kode_akun, a.nama_akun,
            k.kode_akun AS kode_kas, k.nama_akun AS nama_kas, p.nama_pemasok
            FROM transaksi t
            JOIN transaksi s ON s.id_perusahaan=t.id_perusahaan AND s.id_transaksi=t.id_transaksi_asal
            JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN detail_transaksi sd ON sd.id_perusahaan=d.id_perusahaan AND sd.id_detail_transaksi=d.id_detail_asal
            JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            LEFT JOIN akun k ON k.id_perusahaan=t.id_perusahaan AND k.id_akun=t.id_akun_kas
            LEFT JOIN pemasok p ON p.id_perusahaan=t.id_perusahaan AND p.id_pemasok=t.id_pemasok
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='beban_penjualan_jasa'
            ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function simpan($id, $tanggal, array $data)
    {
        $this->date($tanggal);
        $write = function ($id, $company, $db) use ($tanggal, $data) {
            foreach (array('id_detail_asal', 'id_akun', 'jumlah', 'cara_pembayaran', 'deskripsi') as $key) {
                if (!isset($data[$key]) || !is_string($data[$key])) {
                    throw new InvalidArgumentException('Data beban jasa tidak lengkap.');
                }
            }
            $this->id($data['id_detail_asal']);
            $this->id($data['id_akun']);
            $amount = $this->amount($data['jumlah']);
            $description = trim($data['deskripsi']);
            if ($description === '' || $this->length($description) > 255) {
                throw new InvalidArgumentException('Keterangan beban wajib diisi, maksimal 255 karakter.');
            }
            $source = $this->source($company, $data['id_detail_asal'], $tanggal);
            $this->expense_account($company, $data['id_akun']);
            $payment = $data['cara_pembayaran'];
            $cash = null;
            $supplier = null;
            $due = null;
            if ($payment === 'tunai') {
                $cash = isset($data['id_akun_kas']) ? $data['id_akun_kas'] : null;
                $this->id($cash);
                $this->account_root($company, $cash, '1100');
            } elseif ($payment === 'kredit') {
                $supplier = isset($data['id_pemasok']) ? $data['id_pemasok'] : null;
                $due = isset($data['jatuh_tempo']) ? $data['jatuh_tempo'] : null;
                $this->id($supplier);
                $this->date($due);
                if ($due < $tanggal) {
                    throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal beban.');
                }
                $this->supplier($company, $supplier);
                $this->payable($company);
            } else {
                throw new InvalidArgumentException('Cara pembayaran tidak valid.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
                'tanggal' => $tanggal, 'id_transaksi_asal' => $source->id_transaksi, 'cara_pembayaran' => $payment,
                'id_akun_kas' => $cash, 'id_pemasok' => $supplier, 'jatuh_tempo' => $due,
                'deskripsi' => $description, 'total_sebelum_pajak' => $amount,
                'total_pajak' => '0.00', 'total' => $amount
            )));
            $this->ok($db->insert('detail_transaksi', array(
                'id_perusahaan' => $company, 'id_transaksi' => $id,
                'id_detail_asal' => $data['id_detail_asal'], 'id_akun' => $data['id_akun'],
                'deskripsi' => $description, 'jumlah' => '1.0000', 'harga' => $amount,
                'nilai_sebelum_pajak' => $amount, 'nilai_pajak' => '0.00', 'subtotal' => $amount
            )));
        };
        if ($id === null || $id === '') {
            return $this->ci->transaksi_service->buat_draft('beban_penjualan_jasa', $tanggal, 'Beban penjualan jasa', $write);
        }
        $this->id($id);
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $write) {
            $db = $this->ci->db;
            $query = $db->query("SELECT id_transaksi FROM transaksi WHERE id_perusahaan=? AND id_transaksi=?
                AND jenis_transaksi='beban_penjualan_jasa' AND status='draft' FOR UPDATE", array($company, $id));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Hanya draft beban jasa milik perusahaan ini yang dapat diubah.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('detail_transaksi'));
            $write($id, $company, $db);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            if ($transaction->jenis_transaksi !== 'beban_penjualan_jasa') {
                throw new DomainException('Beban penjualan jasa tidak ditemukan.');
            }
            $company = $transaction->id_perusahaan;
            $query = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                array($company, $transaction->id_transaksi));
            $this->ok($query);
            $rows = $query->result();
            if (count($rows) !== 1) {
                throw new DomainException('Rincian beban tidak valid.');
            }
            $row = $rows[0];
            $source = $this->source($company, $row->id_detail_asal, $transaction->tanggal);
            if ((string) $source->id_transaksi !== (string) $transaction->id_transaksi_asal) {
                throw new DomainException('Penjualan asal tidak sesuai.');
            }
            $amount = $this->amount($row->subtotal);
            if ($amount !== $this->amount($transaction->total)) {
                throw new DomainException('Total beban tidak sesuai dengan rincian.');
            }
            $this->expense_account($company, $row->id_akun);
            if ($transaction->cara_pembayaran === 'tunai') {
                $this->account_root($company, $transaction->id_akun_kas, '1100');
                $credit = $transaction->id_akun_kas;
            } elseif ($transaction->cara_pembayaran === 'kredit') {
                $this->supplier($company, $transaction->id_pemasok);
                $this->date($transaction->jatuh_tempo);
                if ($transaction->jatuh_tempo < $transaction->tanggal) {
                    throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal beban.');
                }
                $credit = $this->payable($company);
            } else {
                throw new DomainException('Cara pembayaran tidak valid.');
            }
            return array(
                array('id_akun' => $row->id_akun, 'debit' => $amount, 'kredit' => '0.00'),
                array('id_akun' => $credit, 'debit' => '0.00', 'kredit' => $amount)
            );
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction, $db) {
            if ($transaction->jenis_transaksi !== 'beban_penjualan_jasa') {
                throw new DomainException('Beban penjualan jasa tidak ditemukan.');
            }
            $query = $db->query("SELECT a.id_alokasi_transaksi FROM alokasi_transaksi a
                JOIN transaksi p ON p.id_perusahaan=a.id_perusahaan AND p.id_transaksi=a.id_transaksi
                WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND p.status='diposting'
                LIMIT 1 FOR UPDATE", array($transaction->id_perusahaan, $transaction->id_transaksi));
            $this->ok($query);
            if ($query->row()) {
                throw new DomainException('Beban sudah memiliki pembayaran utang terposting. Batalkan pembayaran terlebih dahulu.');
            }
        });
    }

    private function source($company, $detail, $date)
    {
        $this->id($detail);
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.tanggal FROM detail_transaksi d
            JOIN transaksi t ON t.id_perusahaan=d.id_perusahaan AND t.id_transaksi=d.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            WHERE d.id_perusahaan=? AND d.id_detail_transaksi=? AND t.jenis_transaksi='penjualan'
                AND t.status='diposting' AND p.tipe_produk='jasa' FOR UPDATE", array($company, $detail));
        $this->ok($query);
        $source = $query->row();
        if (!$source) {
            throw new DomainException('Rincian jasa pada penjualan terposting tidak ditemukan.');
        }
        if ($date < $source->tanggal) {
            throw new DomainException('Tanggal beban tidak boleh sebelum tanggal penjualan.');
        }
        return $source;
    }

    private function supplier($company, $id)
    {
        $this->id($id);
        $query = $this->ci->db->query('SELECT id_pemasok FROM pemasok WHERE id_perusahaan=? AND id_pemasok=? AND aktif=1 FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pemasok tidak ditemukan atau tidak aktif.');
        }
    }

    private function expense_account($company, $id)
    {
        $account = $this->leaf_account($company, $id);
        if ($account->tipe_akun !== 'Beban') {
            throw new DomainException('Akun beban harus berupa akun rincian bertipe Beban.');
        }
    }

    private function payable($company)
    {
        $query = $this->ci->db->query("SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan='utang_usaha' FOR UPDATE", array($company));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengaturan akun utang usaha belum tersedia.');
        }
        $id = $query->row()->id_akun;
        $this->account_root($company, $id, '2100');
        return $id;
    }

    private function leaf_account($company, $id)
    {
        $this->id($id);
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE', array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account) {
            throw new DomainException('Akun tidak ditemukan.');
        }
        $query = $this->ci->db->query('SELECT id_akun FROM akun WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        if ($query->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan.');
        }
        return $account;
    }

    private function account_root($company, $id, $root)
    {
        $account = $this->leaf_account($company, $id);
        $seen = array();
        $found = false;
        while ($account) {
            if (isset($seen[$account->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$account->id_akun] = true;
            if ($account->kode_akun === $root) $found = true;
            if (!$account->id_akun_induk) break;
            $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
                array($company, $account->id_akun_induk));
            $this->ok($query);
            $account = $query->row();
            if (!$account) throw new DomainException('Induk akun tidak ditemukan.');
        }
        if (!$found) throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
    }

    private function company()
    {
        $id = $this->ci->session->userdata('id_perusahaan');
        $this->id($id);
        return $id;
    }

    private function id($value)
    {
        if ((!is_string($value) && !is_int($value)) || !ctype_digit((string) $value)
            || (int) $value < 1 || (int) $value > 2147483647) {
            throw new InvalidArgumentException('ID tidak valid.');
        }
    }

    private function date($value)
    {
        if (!is_string($value) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $value)
            || !($date = DateTime::createFromFormat('!Y-m-d', $value)) || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }
    }

    private function amount($value)
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Nominal beban tidak valid.');
        }
        $parts = explode('.', (string) $value);
        $cents = (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
        if ($cents < 1) throw new InvalidArgumentException('Nominal beban harus lebih dari nol.');
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function ok($result)
    {
        if (!$result) throw new RuntimeException('Operasi database beban penjualan jasa gagal.');
    }
}
