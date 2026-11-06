<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_laporan extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_trial_balance()
    {
        $this->db->select('a.id_akun, a.kode_akun, a.nama_akun, a.tipe_akun,
            IFNULL(SUM(d.debit), 0) as total_debit,
            IFNULL(SUM(d.kredit), 0) as total_kredit,
            (IFNULL(SUM(d.debit), 0) - IFNULL(SUM(d.kredit), 0)) as saldo');
        $this->db->from('akun a');
        $this->db->where('a.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->join('detail_jurnal d', 'a.id_akun = d.id_akun', 'left');
        $this->db->group_by('a.id_akun, a.kode_akun, a.nama_akun, a.tipe_akun');
        $this->db->order_by('a.kode_akun', 'asc');
        $query = $this->db->get();
        return $query->result();
    }
}
