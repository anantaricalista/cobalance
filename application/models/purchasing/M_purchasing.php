<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_purchasing extends CI_Model
{
    private function company()
    {
        return (int) $this->session->userdata('id_perusahaan');
    }

    public function ringkasan()
    {
        $company = $this->company();
        $today = date('Y-m-d');
        $barang = "EXISTS (SELECT 1 FROM detail_transaksi d
                WHERE d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi)
            AND NOT EXISTS (SELECT 1 FROM detail_transaksi d
                LEFT JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
                WHERE d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
                    AND (p.id_produk IS NULL OR p.tipe_produk <> 'barang'))";
        $purchases = "FROM transaksi t
            JOIN pengguna u ON u.id_perusahaan=t.id_perusahaan AND u.id_pengguna=t.id_pengguna_pembuat
            LEFT JOIN pemasok s ON s.id_perusahaan=t.id_perusahaan AND s.id_pemasok=t.id_pemasok
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='pembelian'
                AND u.role='purchasing' AND " . $barang;

        $query = $this->db->query("SELECT
            COALESCE(SUM(t.status='draft'), 0) AS draft,
            COALESCE(SUM(t.status='diposting' AND t.tanggal BETWEEN ? AND ?), 0) AS bulan_ini
            " . $purchases, array(date('Y-m-01'), $today, $company));
        if (!$query) {
            throw new RuntimeException('Gagal membaca ringkasan pembelian Purchasing.');
        }
        $row = $query->row();
        $supplier_query = $this->db->query('SELECT COUNT(*) AS jumlah FROM pemasok WHERE id_perusahaan=? AND aktif=1',
            array($company));
        if (!$supplier_query) {
            throw new RuntimeException('Gagal membaca jumlah pemasok.');
        }

        $open_items = "SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.jatuh_tempo,
                s.nama_pemasok, t.total
                - COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                    JOIN transaksi pembayaran ON pembayaran.id_perusahaan=a.id_perusahaan
                        AND pembayaran.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                        AND pembayaran.jenis_transaksi='pembayaran_utang' AND pembayaran.status='diposting'), 0)
                - COALESCE((SELECT SUM(retur.total) FROM transaksi retur
                    WHERE retur.id_perusahaan=t.id_perusahaan AND retur.id_transaksi_asal=t.id_transaksi
                        AND retur.jenis_transaksi='retur_pembelian' AND retur.status='diposting'), 0) AS sisa
            " . $purchases . "
                AND t.status='diposting' AND t.cara_pembayaran='kredit' AND t.id_pemasok IS NOT NULL";
        $debt_query = $this->db->query("SELECT COUNT(*) AS jumlah, COALESCE(SUM(sisa), 0) AS total,
                COALESCE(SUM(CASE WHEN jatuh_tempo < ? THEN sisa ELSE 0 END), 0) AS terlambat,
                COALESCE(SUM(jatuh_tempo < ?), 0) AS jumlah_terlambat
            FROM (" . $open_items . ") tagihan WHERE sisa > 0", array($today, $today, $company));
        $items_query = $this->db->query("SELECT * FROM (" . $open_items . ") tagihan
            WHERE sisa > 0 ORDER BY jatuh_tempo IS NULL, jatuh_tempo, tanggal, id_transaksi LIMIT 8",
            array($company));
        if (!$debt_query || !$items_query) {
            throw new RuntimeException('Gagal membaca utang pembelian Purchasing.');
        }

        return array(
            'draft' => (int) $row->draft,
            'bulan_ini' => (int) $row->bulan_ini,
            'pemasok' => (int) $supplier_query->row()->jumlah,
            'utang' => $debt_query->row(),
            'tagihan' => $items_query->result()
        );
    }
}
