<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_dashboard extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    public function get_summary($today)
    {
        $company = $this->session->userdata('id_perusahaan');
        $month_start = date('Y-m-01', strtotime($today));

        $cash = $this->db->query("
            WITH RECURSIVE cash_accounts (id_akun) AS (
                SELECT id_akun FROM akun
                WHERE id_perusahaan = ? AND kode_akun = '1100'
                UNION ALL
                SELECT a.id_akun FROM akun a
                JOIN cash_accounts c ON a.id_akun_induk = c.id_akun
                WHERE a.id_perusahaan = ?
            )
            SELECT COALESCE(SUM(d.debit - d.kredit), 0) AS balance
            FROM cash_accounts c
            JOIN detail_jurnal d ON d.id_akun = c.id_akun
            JOIN jurnal_transaksi j ON j.id_jurnal = d.id_jurnal
            WHERE j.id_perusahaan = ? AND j.tanggal <= ?
        ", array($company, $company, $company, $today))->row();

        $receivable = $this->outstanding_balance($company, 'piutang');
        $payable = $this->outstanding_balance($company, 'utang');
        $monthly = $this->db->query("
            SELECT COUNT(*) AS transaction_count,
                COALESCE(SUM(CASE
                    WHEN jenis_transaksi = 'penjualan' THEN total
                    WHEN jenis_transaksi = 'retur_penjualan' THEN -total
                    ELSE 0
                END), 0) AS sales,
                COALESCE(SUM(CASE
                    WHEN jenis_transaksi = 'pembelian' THEN total
                    WHEN jenis_transaksi = 'retur_pembelian' THEN -total
                    ELSE 0
                END), 0) AS purchases
            FROM transaksi
            WHERE id_perusahaan = ? AND status = 'diposting'
                AND tanggal BETWEEN ? AND ?
        ", array($company, $month_start, $today))->row();

        return array(
            'cash' => $cash ? $cash->balance : '0',
            'receivable' => $receivable,
            'payable' => $payable,
            'monthly' => $monthly ? $monthly : (object) array(
                'transaction_count' => 0,
                'sales' => 0,
                'purchases' => 0
            ),
            'recent_transactions' => $this->recent_transactions($company)
        );
    }

    private function outstanding_balance($company, $type)
    {
        if ($type === 'piutang') {
            $party_field = 'id_pelanggan';
            $eligible = "((t.jenis_transaksi = 'saldo_awal_piutang')
                OR (t.jenis_transaksi IN ('penjualan', 'pelepasan_aset') AND t.cara_pembayaran = 'kredit'))";
            $payment_type = 'penerimaan_piutang';
            $return_type = 'retur_penjualan';
        } else {
            $party_field = 'id_pemasok';
            $eligible = "((t.jenis_transaksi = 'saldo_awal_utang')
                OR (t.jenis_transaksi IN ('pembelian', 'perolehan_aset', 'beban_penjualan_jasa') AND t.cara_pembayaran = 'kredit'))";
            $payment_type = 'pembayaran_utang';
            $return_type = 'retur_pembelian';
        }

        $sql = "
            SELECT COALESCE(SUM(open_items.sisa), 0) AS balance
            FROM (
                SELECT t.total
                    - COALESCE((SELECT SUM(a.jumlah)
                        FROM alokasi_transaksi a
                        JOIN transaksi p ON p.id_perusahaan = a.id_perusahaan
                            AND p.id_transaksi = a.id_transaksi
                        WHERE a.id_perusahaan = t.id_perusahaan
                            AND a.id_transaksi_tagihan = t.id_transaksi
                            AND p.status = 'diposting' AND p.jenis_transaksi = ?), 0)
                    - COALESCE((SELECT SUM(r.total) FROM transaksi r
                        WHERE r.id_perusahaan = t.id_perusahaan
                            AND r.id_transaksi_asal = t.id_transaksi
                            AND r.jenis_transaksi = ? AND r.status = 'diposting'), 0) AS sisa
                FROM transaksi t
                WHERE t.id_perusahaan = ? AND t.status = 'diposting'
                    AND t." . $party_field . " IS NOT NULL AND " . $eligible . "
            ) open_items
            WHERE open_items.sisa > 0
        ";
        $query = $this->db->query($sql, array($payment_type, $return_type, $company))->row();
        return $query ? $query->balance : '0';
    }

    private function recent_transactions($company)
    {
        return $this->db->query("
            SELECT t.nomor_transaksi, t.jenis_transaksi, t.tanggal, t.total,
                COALESCE(p.nama_pelanggan, s.nama_pemasok, '-') AS pihak
            FROM transaksi t
            LEFT JOIN pelanggan p ON p.id_perusahaan = t.id_perusahaan
                AND p.id_pelanggan = t.id_pelanggan
            LEFT JOIN pemasok s ON s.id_perusahaan = t.id_perusahaan
                AND s.id_pemasok = t.id_pemasok
            WHERE t.id_perusahaan = ? AND t.status = 'diposting'
            ORDER BY t.tanggal DESC, t.id_transaksi DESC
            LIMIT 8
        ", array($company))->result();
    }
}
