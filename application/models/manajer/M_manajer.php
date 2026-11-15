<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_manajer extends CI_Model
{
    private function query($sql, array $params)
    {
        $query = $this->db->query($sql, $params);
        if (!$query) {
            log_message('error', 'Gagal membaca data Manajer/Pemilik.');
            throw new RuntimeException('Gagal membaca data Manajer/Pemilik.');
        }
        return $query;
    }

    public function kas($start, $end, $page)
    {
        $company = $this->session->userdata('id_perusahaan');
        $root = $this->query("SELECT COUNT(*) AS jumlah FROM akun WHERE id_perusahaan=? AND kode_akun='1100'",
            array($company))->row();
        $cte = "WITH RECURSIVE cash_accounts AS (
            SELECT id_akun FROM akun WHERE id_perusahaan=? AND kode_akun='1100'
            UNION ALL
            SELECT a.id_akun FROM akun a JOIN cash_accounts c ON c.id_akun=a.id_akun_induk
            WHERE a.id_perusahaan=?
        ), movements AS (
            SELECT j.id_jurnal, j.tanggal, j.deskripsi, j.referensi, SUM(d.debit-d.kredit) AS neto
            FROM cash_accounts c JOIN detail_jurnal d ON d.id_akun=c.id_akun
            JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal AND j.id_perusahaan=?
            WHERE j.tanggal<=?
            GROUP BY j.id_jurnal, j.tanggal, j.deskripsi, j.referensi
        ) ";
        $base = array($company, $company, $company, $end);
        $summary = $this->query($cte . "SELECT
            COALESCE(SUM(CASE WHEN tanggal<? THEN neto ELSE 0 END), 0) AS awal,
            COALESCE(SUM(CASE WHEN tanggal>=? AND neto>0 THEN neto ELSE 0 END), 0) AS masuk,
            COALESCE(SUM(CASE WHEN tanggal>=? AND neto<0 THEN -neto ELSE 0 END), 0) AS keluar,
            COALESCE(SUM(neto), 0) AS akhir FROM movements",
            array_merge($base, array($start, $start, $start)))->row();
        $rows = $this->query($cte . "SELECT * FROM movements WHERE tanggal>=? AND neto<>0
            ORDER BY tanggal DESC, id_jurnal DESC LIMIT 51 OFFSET " . (($page - 1) * 50),
            array_merge($base, array($start)))->result();
        $result = $this->page($summary, $rows);
        $result['akun_tersedia'] = (int) $root->jumlah > 0;
        return $result;
    }

    public function penjualan($start, $end, $page)
    {
        $company = $this->session->userdata('id_perusahaan');
        $summary = $this->query("SELECT
            COALESCE(SUM(CASE WHEN jenis_transaksi='penjualan' THEN total ELSE 0 END),0) AS penjualan,
            COALESCE(SUM(CASE WHEN jenis_transaksi='retur_penjualan' THEN total ELSE 0 END),0) AS retur,
            COALESCE(SUM(CASE WHEN jenis_transaksi='penjualan' THEN 1 ELSE 0 END),0) AS jumlah
            FROM transaksi WHERE id_perusahaan=? AND status='diposting'
                AND jenis_transaksi IN ('penjualan','retur_penjualan') AND tanggal BETWEEN ? AND ?",
            array($company, $start, $end))->row();
        $rows = $this->query("SELECT t.nomor_transaksi, t.tanggal, t.jenis_transaksi, t.cara_pembayaran,
                t.total, p.nama_pelanggan
            FROM transaksi t LEFT JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.status='diposting'
                AND t.jenis_transaksi IN ('penjualan','retur_penjualan') AND t.tanggal BETWEEN ? AND ?
            ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT 51 OFFSET " . (($page - 1) * 50),
            array($company, $start, $end))->result();
        return $this->page($summary, $rows);
    }

    public function stok_minimum()
    {
        $company = $this->session->userdata('id_perusahaan');
        $base = " FROM produk p LEFT JOIN saldo_persediaan s ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.aktif=1 AND p.tipe_produk='barang' AND p.stok_minimum>0
                AND COALESCE(s.jumlah,0)<=p.stok_minimum";
        $count = $this->query('SELECT COUNT(*) AS jumlah' . $base, array($company))->row();
        $rows = $this->query("SELECT p.nama_produk, p.satuan, p.stok_minimum, COALESCE(s.jumlah,0) AS jumlah"
            . $base . " ORDER BY COALESCE(s.jumlah,0)/p.stok_minimum, p.nama_produk, p.id_produk LIMIT 5",
            array($company))->result();
        return array('jumlah' => (int) $count->jumlah, 'barang' => $rows);
    }

    private function page($summary, $rows)
    {
        $more = count($rows) > 50;
        if ($more) {
            array_pop($rows);
        }
        return array('ringkasan' => $summary, 'rows' => $rows, 'more' => $more);
    }
}
