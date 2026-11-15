<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_akuntansi extends CI_Model
{
    private function query($sql, array $params)
    {
        $query = $this->db->query($sql, $params);
        if (!$query) {
            log_message('error', 'Gagal membaca beranda Akuntansi.');
            throw new RuntimeException('Gagal membaca beranda Akuntansi.');
        }
        return $query;
    }

    public function ringkasan()
    {
        $company = $this->session->userdata('id_perusahaan');
        $today = date('Y-m-d');
        $filter = "t.id_perusahaan=? AND t.status='draft'
            AND NOT (t.jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang') AND t.id_transaksi_asal IS NOT NULL)";
        $counts = $this->query("SELECT t.jenis_transaksi, COUNT(*) AS jumlah
            FROM transaksi t WHERE " . $filter . " GROUP BY t.jenis_transaksi
            ORDER BY jumlah DESC, t.jenis_transaksi", array($company))->result();
        $drafts = $this->query("SELECT t.id_transaksi, t.nomor_transaksi, t.jenis_transaksi, t.tanggal,
                t.total, u.nama_lengkap AS pembuat
            FROM transaksi t LEFT JOIN pengguna u ON u.id_perusahaan=t.id_perusahaan AND u.id_pengguna=t.id_pengguna_pembuat
            WHERE " . $filter . " ORDER BY t.tanggal, t.id_transaksi LIMIT 15", array($company))->result();
        $proposals = $this->query("SELECT COUNT(*) AS jumlah FROM usulan_opname
            WHERE id_perusahaan=? AND status='menunggu'", array($company))->row();
        $posted = $this->query("SELECT COUNT(*) AS jumlah FROM transaksi WHERE id_perusahaan=?
            AND status='diposting' AND tanggal BETWEEN ? AND ?",
            array($company, date('Y-m-01'), $today))->row();
        $total = 0;
        foreach ($counts as $row) {
            $total += (int) $row->jumlah;
        }
        return array('jumlah_draft' => $total, 'jenis_draft' => $counts, 'draft' => $drafts,
            'usulan' => (int) $proposals->jumlah, 'posting_bulan_ini' => (int) $posted->jumlah);
    }
}
