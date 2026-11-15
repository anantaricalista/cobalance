<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_keuangan extends CI_Model
{
    private function query($sql, array $params)
    {
        $query = $this->db->query($sql, $params);
        if (!$query) {
            throw new RuntimeException('Gagal membaca ringkasan Keuangan.');
        }
        return $query;
    }

    public function ringkasan()
    {
        $company = $this->session->userdata('id_perusahaan');
        $today = date('Y-m-d');
        $cash = $this->query("WITH RECURSIVE cash_accounts AS (
                SELECT id_akun, kode_akun, nama_akun FROM akun
                    WHERE id_perusahaan=? AND kode_akun='1100'
                UNION ALL
                SELECT a.id_akun, a.kode_akun, a.nama_akun FROM akun a
                    JOIN cash_accounts c ON a.id_akun_induk=c.id_akun
                    WHERE a.id_perusahaan=?
            )
            SELECT c.id_akun, c.kode_akun, c.nama_akun,
                COALESCE(SUM(CASE WHEN j.id_jurnal IS NOT NULL THEN d.debit-d.kredit ELSE 0 END), 0) AS saldo
            FROM cash_accounts c
            LEFT JOIN detail_jurnal d ON d.id_akun=c.id_akun
            LEFT JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal AND j.id_perusahaan=? AND j.tanggal<=?
            GROUP BY c.id_akun, c.kode_akun, c.nama_akun
            ORDER BY c.kode_akun, c.id_akun", array($company, $company, $company, $today))->result();

        $drafts = $this->query("SELECT jenis_transaksi, COUNT(*) AS jumlah
            FROM transaksi WHERE id_perusahaan=? AND status='draft'
                AND jenis_transaksi IN ('kas_masuk','kas_keluar','transfer_kas','pembayaran_utang','penerimaan_piutang')
            GROUP BY jenis_transaksi", array($company))->result();
        $counts = array('kas_bank' => 0, 'utang' => 0, 'piutang' => 0);
        foreach ($drafts as $draft) {
            $key = $draft->jenis_transaksi === 'pembayaran_utang' ? 'utang'
                : ($draft->jenis_transaksi === 'penerimaan_piutang' ? 'piutang' : 'kas_bank');
            $counts[$key] += (int) $draft->jumlah;
        }
        return array('kas' => $cash, 'draft' => $counts,
            'utang' => $this->tagihan($company, $today, 'utang'),
            'piutang' => $this->tagihan($company, $today, 'piutang'));
    }

    private function tagihan($company, $today, $type)
    {
        if ($type === 'utang') {
            $party_table = 'pemasok';
            $party_id = 'id_pemasok';
            $party_name = 'nama_pemasok';
            $payment = 'pembayaran_utang';
            $return_type = 'retur_pembelian';
            $eligible = "(t.jenis_transaksi='saldo_awal_utang' OR
                (t.jenis_transaksi IN ('pembelian','perolehan_aset','beban_penjualan_jasa') AND t.cara_pembayaran='kredit'))";
            $journal = "AND EXISTS (SELECT 1 FROM transaksi_jurnal tj
                JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
                WHERE tj.id_perusahaan=t.id_perusahaan AND tj.id_transaksi=t.id_transaksi AND tj.jenis_jurnal='posting')";
        } else {
            $party_table = 'pelanggan';
            $party_id = 'id_pelanggan';
            $party_name = 'nama_pelanggan';
            $payment = 'penerimaan_piutang';
            $return_type = 'retur_penjualan';
            $eligible = "(t.jenis_transaksi='saldo_awal_piutang' OR
                (t.jenis_transaksi IN ('penjualan','pelepasan_aset') AND t.cara_pembayaran='kredit'))";
            $journal = '';
        }
        $open = "SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.jatuh_tempo,
                p." . $party_name . " AS pihak, p.aktif,
                t.total - COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                        AND r.jenis_transaksi=? AND r.status='diposting'), 0)
                - COALESCE((SELECT SUM(r.total) FROM transaksi r
                    WHERE r.id_perusahaan=t.id_perusahaan AND r.id_transaksi_asal=t.id_transaksi
                        AND r.jenis_transaksi=? AND r.status='diposting'), 0) AS sisa
            FROM transaksi t JOIN " . $party_table . " p ON p.id_perusahaan=t.id_perusahaan
                AND p." . $party_id . "=t." . $party_id . "
            WHERE t.id_perusahaan=? AND t.status='diposting' AND " . $eligible . " " . $journal;
        $summary = $this->query("SELECT COUNT(*) AS jumlah, COALESCE(SUM(sisa),0) AS total,
                COALESCE(SUM(CASE WHEN jatuh_tempo IS NOT NULL AND jatuh_tempo < ? THEN sisa ELSE 0 END),0) AS terlambat,
                COALESCE(SUM(CASE WHEN jatuh_tempo IS NOT NULL AND jatuh_tempo < ? THEN 1 ELSE 0 END),0) AS jumlah_terlambat
            FROM (" . $open . ") tagihan WHERE sisa > 0",
            array($today, $today, $payment, $return_type, $company))->row();
        $items = $this->query("SELECT * FROM (" . $open . ") tagihan WHERE sisa > 0
            ORDER BY jatuh_tempo IS NULL, jatuh_tempo, tanggal, id_transaksi LIMIT 8",
            array($payment, $return_type, $company))->result();
        return array('ringkasan' => $summary, 'tagihan' => $items);
    }
}
