<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_laporan extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_trial_balance($tanggal)
    {
        $this->db->select('a.id_akun, a.kode_akun, a.nama_akun, a.tipe_akun,
            IFNULL(SUM(CASE WHEN j.id_jurnal IS NOT NULL THEN COALESCE(d.debit, 0) ELSE 0 END), 0) as total_debit,
            IFNULL(SUM(CASE WHEN j.id_jurnal IS NOT NULL THEN COALESCE(d.kredit, 0) ELSE 0 END), 0) as total_kredit,
            IFNULL(SUM(CASE WHEN j.id_jurnal IS NOT NULL THEN COALESCE(d.debit, 0) - COALESCE(d.kredit, 0) ELSE 0 END), 0) as saldo', false);
        $this->db->from('akun a');
        $this->db->where('a.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->join('detail_jurnal d', 'a.id_akun = d.id_akun', 'left');
        $this->db->join('jurnal_transaksi j', 'j.id_jurnal = d.id_jurnal AND j.id_perusahaan = a.id_perusahaan AND j.tanggal <= ' . $this->db->escape($tanggal), 'left', false);
        $this->db->group_by('a.id_akun, a.kode_akun, a.nama_akun, a.tipe_akun');
        $this->db->order_by('a.kode_akun', 'asc');
        $query = $this->db->get();
        return $query->result();
    }
}
