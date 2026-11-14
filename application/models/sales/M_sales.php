<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_sales extends CI_Model
{
    public function ringkasan()
    {
        $company = $this->session->userdata('id_perusahaan');
        $today = date('Y-m-d');
        $sales = $this->db->query("SELECT
            COALESCE(SUM(status='draft'), 0) AS draft,
            COALESCE(SUM(status='diposting' AND tanggal BETWEEN ? AND ?), 0) AS bulan_ini
            FROM transaksi WHERE id_perusahaan=? AND jenis_transaksi='penjualan'",
            array(date('Y-m-01'), $today, $company));
        $customers = $this->db->query('SELECT COUNT(*) AS jumlah FROM pelanggan WHERE id_perusahaan=? AND aktif=1',
            array($company));
        if (!$sales || !$customers) {
            throw new RuntimeException('Gagal membaca ringkasan Penjualan.');
        }

        $open = "SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.jatuh_tempo, p.nama_pelanggan,
                t.total
                - COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                        AND r.jenis_transaksi='penerimaan_piutang' AND r.status='diposting'), 0)
                - COALESCE((SELECT SUM(r.total) FROM transaksi r
                    WHERE r.id_perusahaan=t.id_perusahaan AND r.id_transaksi_asal=t.id_transaksi
                        AND r.jenis_transaksi='retur_penjualan' AND r.status='diposting'), 0) AS sisa
            FROM transaksi t JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penjualan' AND t.cara_pembayaran='kredit'
                AND t.status='diposting' AND EXISTS (SELECT 1 FROM transaksi_jurnal tj
                    JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
                    WHERE tj.id_perusahaan=t.id_perusahaan AND tj.id_transaksi=t.id_transaksi AND tj.jenis_jurnal='posting')";
        $summary = $this->db->query("SELECT COUNT(*) AS jumlah, COALESCE(SUM(sisa), 0) AS total,
                COALESCE(SUM(CASE WHEN jatuh_tempo < ? THEN sisa ELSE 0 END), 0) AS terlambat,
                COALESCE(SUM(jatuh_tempo < ?), 0) AS jumlah_terlambat
            FROM (" . $open . ") tagihan WHERE sisa > 0", array($today, $today, $company));
        $invoices = $this->db->query("SELECT * FROM (" . $open . ") tagihan WHERE sisa > 0
            ORDER BY jatuh_tempo IS NULL, jatuh_tempo, tanggal, id_transaksi LIMIT 8", array($company));
        if (!$summary || !$invoices) {
            throw new RuntimeException('Gagal membaca piutang Penjualan.');
        }
        return array('draft' => (int) $sales->row()->draft, 'bulan_ini' => (int) $sales->row()->bulan_ini,
            'pelanggan' => (int) $customers->row()->jumlah, 'piutang' => $summary->row(),
            'tagihan' => $invoices->result());
    }
}
