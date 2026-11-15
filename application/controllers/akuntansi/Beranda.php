<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Beranda extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('system_admin') === true) {
            $this->session->sess_destroy();
            redirect('auth');
            exit;
        }
        if (!$this->session->userdata('id_pengguna')) {
            redirect('auth');
            exit;
        }
        if (!in_array($this->session->userdata('role'), array('akuntansi', 'admin'), true)) {
            show_error('Anda tidak memiliki akses ke beranda Akuntansi.', 403);
            exit;
        }
        $this->load->model('akuntansi/M_akuntansi', 'akuntansi');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('akuntansi/beranda', array(
            'nama' => $this->session->userdata('nama_lengkap'),
            'hari_ini' => date('Y-m-d'),
            'ringkasan' => $this->akuntansi->ringkasan()
        ));
        $this->load->view('template/footer');
    }
}
