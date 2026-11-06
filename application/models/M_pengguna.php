<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_pengguna extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Ambil data pengguna berdasarkan username
    public function get_by_username($username)
    {
        $this->db->select('p.*, perusahaan.nama_perusahaan');
        $this->db->from('pengguna p');
        $this->db->join('perusahaan', 'p.id_perusahaan = perusahaan.id_perusahaan', 'left');
        $this->db->where('p.username', $username);
        $query = $this->db->get();
        return $query->row();
    }
}
