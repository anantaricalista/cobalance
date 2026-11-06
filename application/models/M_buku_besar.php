<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_buku_besar extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Hitung saldo awal (sebelum tanggal mulai)
    public function get_opening_balance($id_akun, $start_date)
    {
        $this->db->select('IFNULL(SUM(d.debit), 0) as total_debit, IFNULL(SUM(d.kredit), 0) as total_kredit');
        $this->db->from('detail_jurnal d');
        $this->db->join('jurnal_transaksi jt', 'd.id_jurnal = jt.id_jurnal', 'left');
        // Filter berdasarkan perusahaan yang login
        $this->db->where('jt.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->where('d.id_akun', $id_akun);
        $this->db->where('jt.tanggal <', $start_date);
        $query = $this->db->get();
        $row = $query->row();
        return ($row->total_debit - $row->total_kredit);
    }

    // Ambil seluruh transaksi untuk akun dalam rentang tanggal
    public function get_transactions($id_akun, $start_date, $end_date)
    {
        $this->db->select('jt.tanggal, jt.deskripsi, jt.referensi, d.debit, d.kredit');
        $this->db->from('detail_jurnal d');
        $this->db->join('jurnal_transaksi jt', 'd.id_jurnal = jt.id_jurnal', 'left');
        $this->db->where('jt.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->where('d.id_akun', $id_akun);
        $this->db->where('jt.tanggal >=', $start_date);
        $this->db->where('jt.tanggal <=', $end_date);
        $this->db->order_by('jt.tanggal', 'ASC');
        $this->db->order_by('jt.id_jurnal', 'ASC');
        $query = $this->db->get();
        return $query->result();
    }

    // Gabungkan saldo awal dan transaksi ke dalam satu array
    public function get_ledger($id_akun, $start_date, $end_date)
    {
        $opening = $this->get_opening_balance($id_akun, $start_date);
        $transactions = $this->get_transactions($id_akun, $start_date, $end_date);
        return array('opening' => $opening, 'transactions' => $transactions);
    }
}
