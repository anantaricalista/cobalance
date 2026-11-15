<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_kasir extends CI_Model
{
    private function run($sql, array $binds)
    {
        $query = $this->db->query($sql, $binds);
        if (!$query) {
            throw new RuntimeException('Gagal membaca ringkasan Kasir.');
        }
        return $query;
    }

    public function ringkasan()
    {
        $company = $this->session->userdata('id_perusahaan');
        $today = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-6 days'));

        $days = $this->run("SELECT tanggal, COUNT(*) AS jumlah, SUM(total) AS total,
                SUM(CASE WHEN cara_pembayaran='tunai' THEN total ELSE 0 END) AS tunai,
                SUM(status='draft') AS draft
            FROM transaksi WHERE id_perusahaan=? AND jenis_transaksi='penjualan'
                AND status IN ('draft','diposting') AND tanggal BETWEEN ? AND ?
            GROUP BY tanggal", array($company, $from, $today))->result();
        $byDate = array();
        foreach ($days as $day) {
            $byDate[$day->tanggal] = $day;
        }
        $week = array();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $week[] = array('tanggal' => $date,
                'total' => isset($byDate[$date]) ? (float) $byDate[$date]->total : 0.0);
        }
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $now = isset($byDate[$today]) ? $byDate[$today] : null;

        $recent = $this->run("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status, t.cara_pembayaran,
                t.total, p.nama_pelanggan
            FROM transaksi t JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penjualan' AND t.status IN ('draft','diposting')
            ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT 8", array($company))->result();

        $top = $this->run("SELECT p.nama_produk, p.satuan, SUM(d.jumlah) AS jumlah, SUM(d.subtotal) AS nilai
            FROM detail_transaksi d
            JOIN transaksi t ON t.id_perusahaan=d.id_perusahaan AND t.id_transaksi=d.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penjualan' AND t.status IN ('draft','diposting')
                AND t.tanggal BETWEEN ? AND ?
            GROUP BY p.id_produk, p.nama_produk, p.satuan ORDER BY nilai DESC LIMIT 5",
            array($company, $from, $today))->result();

        $low = $this->run("SELECT p.nama_produk, p.satuan, p.stok_minimum, COALESCE(s.jumlah, 0) AS jumlah
            FROM produk p LEFT JOIN saldo_persediaan s ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.aktif=1 AND p.tipe_produk='barang' AND p.stok_minimum > 0
                AND COALESCE(s.jumlah, 0) <= p.stok_minimum
            ORDER BY COALESCE(s.jumlah, 0) / p.stok_minimum, p.nama_produk LIMIT 5", array($company))->result();

        return array(
            'hari_ini' => array('jumlah' => $now ? (int) $now->jumlah : 0, 'total' => $now ? (float) $now->total : 0.0,
                'tunai' => $now ? (float) $now->tunai : 0.0, 'draft' => $now ? (int) $now->draft : 0),
            'kemarin' => isset($byDate[$yesterday]) ? (float) $byDate[$yesterday]->total : 0.0,
            'minggu' => $week, 'terbaru' => $recent, 'terlaris' => $top, 'stok_menipis' => $low
        );
    }
}
