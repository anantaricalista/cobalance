<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penyesuaian_persediaan_service
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
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status,
            t.deskripsi, t.total, t.lampiran_nama, p.nama_produk, d.deskripsi AS jenis, d.jumlah
            FROM transaksi t JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penyesuaian_persediaan'
            ORDER BY t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $this->id($id);
        $company = $this->company();
        $query = $this->ci->db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . ',
            d.deskripsi AS jenis, d.jumlah, d.harga, d.subtotal, p.kode_produk, p.nama_produk,
            a.kode_akun, a.nama_akun
            FROM transaksi t JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi=\'penyesuaian_persediaan\'',
            array($company, $id));
        $this->ok($query);
        return $query->row();
    }

    public function simpan($tanggal, $data, $lampiran = null)
    {
        if (!is_array($data) || !isset($data['produk'], $data['akun'], $data['jenis'], $data['jumlah'])) {
            throw new InvalidArgumentException('Data penyesuaian tidak lengkap.');
        }
        $this->id($data['produk']);
        $this->id($data['akun']);
        if (!in_array($data['jenis'], array('masuk', 'keluar', 'opname'), true)) {
            throw new InvalidArgumentException('Jenis penyesuaian tidak valid.');
        }
        $quantity = $this->decimal($data['jumlah'], 14, 4);
        if ($data['jenis'] !== 'opname' && $this->units($quantity) === 0) {
            throw new InvalidArgumentException('Jumlah penyesuaian harus lebih dari nol.');
        }
        $value = $this->decimal(isset($data['nilai']) && $data['nilai'] !== '' ? $data['nilai'] : '0', 13, 2);
        $description = isset($data['deskripsi']) && is_string($data['deskripsi']) ? trim($data['deskripsi']) : '';
        if ($description === '' || strlen($description) > 255) {
            throw new InvalidArgumentException('Keterangan wajib diisi dan maksimal 255 byte.');
        }
        return $this->ci->transaksi_service->buat_draft('penyesuaian_persediaan', $tanggal, $description,
            function ($id, $company, $db) use ($data, $quantity, $value, $lampiran) {
                $this->product($company, $data['produk']);
                $this->account($company, $data['akun'], false);
                $this->ok($db->insert('detail_transaksi', array(
                    'id_perusahaan' => $company, 'id_transaksi' => $id,
                    'id_produk' => $data['produk'], 'id_akun' => $data['akun'],
                    'deskripsi' => $data['jenis'], 'jumlah' => $quantity, 'harga' => $value
                )));
                $this->ci->lampiran_service->simpan($company, $id, $lampiran, false);
            });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            if ($transaction->jenis_transaksi !== 'penyesuaian_persediaan') {
                throw new DomainException('Penyesuaian persediaan tidak ditemukan.');
            }
            $company = $transaction->id_perusahaan;
            $query = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                array($company, $transaction->id_transaksi));
            $this->ok($query);
            $rows = $query->result();
            if (count($rows) !== 1 || !in_array($rows[0]->deskripsi, array('masuk', 'keluar', 'opname'), true)) {
                throw new DomainException('Rincian penyesuaian tidak valid.');
            }
            $detail = $rows[0];
            $product = $this->product($company, $detail->id_produk);
            $inventory = $product->id_akun_persediaan;
            if (!$inventory) {
                $setting = $db->query("SELECT id_akun FROM pengaturan_akun WHERE id_perusahaan=?
                    AND jenis_pengaturan='persediaan' FOR UPDATE", array($company));
                $this->ok($setting);
                if (!$setting->row()) {
                    throw new DomainException('Pengaturan akun persediaan belum tersedia.');
                }
                $inventory = $setting->row()->id_akun;
            }
            $this->account($company, $inventory, true);
            $this->account($company, $detail->id_akun, false);
            if ((string) $inventory === (string) $detail->id_akun) {
                throw new DomainException('Akun selisih tidak boleh sama dengan akun persediaan.');
            }
            $query = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
                array($company, $detail->id_produk));
            $this->ok($query);
            $balance = $query->row();
            $oldUnits = $balance ? $this->units($balance->jumlah) : 0;
            $oldValue = $balance ? $this->cents($balance->nilai) : 0;
            $requested = $this->units($detail->jumlah);
            $delta = $detail->deskripsi === 'opname' ? $requested - $oldUnits
                : ($detail->deskripsi === 'masuk' ? $requested : -$requested);
            if ($delta === 0) {
                throw new DomainException('Tidak ada selisih jumlah persediaan untuk diposting.');
            }
            if ($delta < 0 && -$delta > $oldUnits) {
                throw new DomainException('Stok tidak mencukupi untuk penyesuaian keluar.');
            }
            $amountUnits = abs($delta);
            $amountQuantity = $this->quantity($amountUnits);
            $average = $balance ? $balance->harga_pokok_rata_rata : '0.000000';
            if ($delta > 0 && (!$oldUnits || $oldValue === 0 || $average === '0.000000')) {
                $cost = $this->cents($detail->harga);
                if ($cost === 0) {
                    throw new DomainException('Masukkan total nilai perolehan untuk penambahan tanpa harga pokok rata-rata.');
                }
            } elseif ($delta < 0 && $amountUnits === $oldUnits) {
                $cost = $oldValue;
            } else {
                $calculation = $db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(18,4)) *
                    CAST(? AS DECIMAL(19,6)), 2) AS CHAR) AS nilai', array($amountQuantity, $average));
                $this->ok($calculation);
                $cost = $this->cents($calculation->row()->nilai);
            }
            if ($cost <= 0) {
                throw new DomainException('Nilai penyesuaian harus lebih dari nol untuk membuat jurnal.');
            }
            $newUnits = $oldUnits + $delta;
            $newValue = $oldValue + ($delta > 0 ? $cost : -$cost);
            if ($newUnits > 999999999999999999 || $newValue < 0 || $newValue > 999999999999999
                || ($newUnits === 0 && $newValue !== 0)) {
                throw new DomainException('Saldo persediaan tidak valid atau melebihi batas database.');
            }
            $quantity = $this->quantity($newUnits);
            $value = $this->money($newValue);
            if ($newUnits === 0) {
                $newAverage = '0.000000';
            } elseif ($delta > 0) {
                $query = $db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) /
                    CAST(? AS DECIMAL(18,4)), 6) AS CHAR) AS rata', array($value, $quantity));
                $this->ok($query);
                $newAverage = $this->decimal($query->row()->rata, 13, 6);
            } else {
                $newAverage = $average;
            }
            $newBalance = array('jumlah' => $quantity, 'nilai' => $value, 'harga_pokok_rata_rata' => $newAverage);
            if ($balance) {
                $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $detail->id_produk)
                    ->update('saldo_persediaan', $newBalance));
            } else {
                $this->ok($db->insert('saldo_persediaan', $newBalance + array(
                    'id_perusahaan' => $company, 'id_produk' => $detail->id_produk)));
            }
            $this->ok($db->insert('mutasi_persediaan', array(
                'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                'id_detail_transaksi' => $detail->id_detail_transaksi, 'id_produk' => $detail->id_produk,
                'jenis_mutasi' => $delta > 0 ? 'masuk' : 'keluar', 'jumlah' => $amountQuantity,
                'nilai' => $this->money($cost), 'saldo_jumlah' => $quantity, 'saldo_nilai' => $value,
                'harga_pokok_rata_rata' => $newAverage
            )));
            $this->ok($db->where('id_perusahaan', $company)->where('id_detail_transaksi', $detail->id_detail_transaksi)
                ->update('detail_transaksi', array('nilai_sebelum_pajak' => $this->money($cost), 'subtotal' => $this->money($cost))));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transaction->id_transaksi)
                ->update('transaksi', array('total_sebelum_pajak' => $this->money($cost), 'total' => $this->money($cost))));
            return array(
                array('id_akun' => $inventory, 'debit' => $delta > 0 ? $this->money($cost) : '0.00',
                    'kredit' => $delta < 0 ? $this->money($cost) : '0.00'),
                array('id_akun' => $detail->id_akun, 'debit' => $delta < 0 ? $this->money($cost) : '0.00',
                    'kredit' => $delta > 0 ? $this->money($cost) : '0.00')
            );
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction, $db) {
            if ($transaction->jenis_transaksi !== 'penyesuaian_persediaan') {
                throw new DomainException('Penyesuaian persediaan tidak ditemukan.');
            }
            $company = $transaction->id_perusahaan;
            $query = $db->query("SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_transaksi=?
                AND jenis_mutasi IN ('masuk','keluar') FOR UPDATE", array($company, $transaction->id_transaksi));
            $this->ok($query);
            $moves = $query->result();
            if (count($moves) !== 1) {
                throw new DomainException('Riwayat penyesuaian tidak lengkap.');
            }
            $move = $moves[0];
            $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan WHERE id_perusahaan=?
                AND id_produk=? AND id_mutasi_persediaan>? LIMIT 1 FOR UPDATE',
                array($company, $move->id_produk, $move->id_mutasi_persediaan));
            $this->ok($later);
            if ($later->row()) {
                throw new DomainException('Persediaan sudah berubah setelah penyesuaian sehingga pembatalan tidak aman.');
            }
            $query = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
                array($company, $move->id_produk));
            $this->ok($query);
            $balance = $query->row();
            if (!$balance || $balance->jumlah !== $move->saldo_jumlah || $balance->nilai !== $move->saldo_nilai
                || $balance->harga_pokok_rata_rata !== $move->harga_pokok_rata_rata) {
                throw new DomainException('Saldo persediaan tidak sesuai dengan riwayat penyesuaian.');
            }
            $prior = $db->query('SELECT harga_pokok_rata_rata FROM mutasi_persediaan
                WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan<?
                ORDER BY id_mutasi_persediaan DESC LIMIT 1 FOR UPDATE',
                array($company, $move->id_produk, $move->id_mutasi_persediaan));
            $this->ok($prior);
            $sign = $move->jenis_mutasi === 'masuk' ? -1 : 1;
            $units = $this->units($balance->jumlah) + $sign * $this->units($move->jumlah);
            $value = $this->cents($balance->nilai) + $sign * $this->cents($move->nilai);
            if ($units < 0 || $value < 0 || ($units === 0 && $value !== 0) || ($units > 0 && !$prior->row())) {
                throw new DomainException('Saldo persediaan sebelumnya tidak dapat dipulihkan.');
            }
            $average = $units === 0 ? '0.000000' : $prior->row()->harga_pokok_rata_rata;
            $quantity = $this->quantity($units);
            $value = $this->money($value);
            $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $move->id_produk)
                ->update('saldo_persediaan', array('jumlah' => $quantity, 'nilai' => $value,
                    'harga_pokok_rata_rata' => $average)));
            $this->ok($db->insert('mutasi_persediaan', array(
                'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                'id_detail_transaksi' => $move->id_detail_transaksi, 'id_produk' => $move->id_produk,
                'jenis_mutasi' => $sign < 0 ? 'pembalik_masuk' : 'pembalik_keluar',
                'jumlah' => $move->jumlah, 'nilai' => $move->nilai, 'saldo_jumlah' => $quantity,
                'saldo_nilai' => $value, 'harga_pokok_rata_rata' => $average
            )));
        });
    }

    private function product($company, $id)
    {
        $query = $this->ci->db->query("SELECT id_produk, id_akun_persediaan FROM produk WHERE id_perusahaan=? AND id_produk=?
            AND tipe_produk='barang' AND aktif=1 FOR UPDATE", array($company, $id));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Produk barang tidak ditemukan atau tidak aktif.');
        }
        return $query->row();
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $this->id($company);
        $this->id($user);
        $query = $this->ci->db->query('SELECT id_pengguna FROM pengguna WHERE id_perusahaan=? AND id_pengguna=?',
            array($company, $user));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return $company;
    }

    private function account($company, $id, $inventory)
    {
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account) {
            throw new DomainException('Akun tidak ditemukan.');
        }
        if (!$inventory && !in_array($account->tipe_akun, array('Pendapatan', 'Beban'), true)) {
            throw new DomainException('Akun selisih harus berupa akun pendapatan atau beban.');
        }
        $query = $this->ci->db->query('SELECT id_akun FROM akun WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        if ($query->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan pada transaksi.');
        }
        $seen = array();
        $found = false;
        while ($account) {
            if (isset($seen[$account->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$account->id_akun] = true;
            $found = $found || $account->kode_akun === '1700';
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
        if ($inventory && !$found) {
            throw new DomainException('Akun persediaan harus termasuk kelompok 1700.');
        }
    }

    private function id($id)
    {
        if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id)
            || (int) $id < 1 || (int) $id > 2147483647) {
            throw new InvalidArgumentException('ID tidak valid.');
        }
    }

    private function decimal($value, $digits, $scale)
    {
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,' . $scale . '})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Jumlah atau nilai desimal tidak valid.');
        }
        $parts = explode('.', (string) $value);
        return $parts[0] . '.' . str_pad(isset($parts[1]) ? $parts[1] : '', $scale, '0');
    }

    private function units($quantity)
    {
        $parts = explode('.', $this->decimal($quantity, 14, 4));
        return (int) $parts[0] * 10000 + (int) $parts[1];
    }

    private function cents($amount)
    {
        $parts = explode('.', $this->decimal($amount, 13, 2));
        return (int) $parts[0] * 100 + (int) $parts[1];
    }

    private function quantity($units)
    {
        return intdiv($units, 10000) . '.' . str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database penyesuaian persediaan gagal.');
        }
    }
}
