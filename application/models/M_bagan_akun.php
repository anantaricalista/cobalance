<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_bagan_akun extends CI_Model
{

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->library('session');
  }

  public function get_chart($end_date)
  {
    // Ambil id_perusahaan dari session
    $id_perusahaan = $this->session->userdata('id_perusahaan');

    // Buat subquery untuk detail_jurnal yang memenuhi syarat tanggal dan perusahaan,
    // hanya memilih kolom yang diperlukan untuk join (id_akun, debit, kredit)
    $subquery = $this->db->select('d.id_akun, d.debit, d.kredit')
      ->from('detail_jurnal d')
      ->join('jurnal_transaksi jt', 'd.id_jurnal = jt.id_jurnal', 'inner')
      ->where('jt.tanggal <=', $end_date)
      ->where('jt.id_perusahaan', $id_perusahaan)
      ->get_compiled_select();

    // Bangun query utama untuk mengambil data akun dan menghitung saldo
    // Perhitungan saldo: jika tipe akun termasuk ('Aset','Beban') maka saldo = SUM(debit) - SUM(kredit)
    // selain itu saldo = SUM(kredit) - SUM(debit)
    $this->db->select(
      'a.id_akun, a.kode_akun, a.nama_akun, a.tipe_akun, a.id_akun_induk, ' .
      'CASE WHEN a.tipe_akun IN ("Aset", "Beban") ' .
      'THEN IFNULL(SUM(d.debit),0) - IFNULL(SUM(d.kredit),0) ' .
      'ELSE IFNULL(SUM(d.kredit),0) - IFNULL(SUM(d.debit),0) ' .
      'END as saldo',
      false
    );
    $this->db->from('akun a');
    // Hanya ambil akun milik perusahaan yang login
    $this->db->where('a.id_perusahaan', $id_perusahaan);
    // Left join dengan subquery yang sudah dibuat
    $this->db->join("($subquery) d", 'a.id_akun = d.id_akun', 'left');
    $this->db->group_by(array('a.id_akun', 'a.kode_akun', 'a.nama_akun', 'a.tipe_akun', 'a.id_akun_induk'));
    $this->db->order_by('a.kode_akun', 'asc');

    $query = $this->db->get();
    return $query->result();
  }
}
