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
        if (!in_array($this->session->userdata('role'), array('kasir', 'admin'), true)) {
            show_error('Anda tidak memiliki akses ke beranda Kasir.', 403);
            exit;
        }
        $this->load->model('kasir/M_kasir', 'kasir');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('kasir/beranda', array(
            'nama' => $this->session->userdata('nama_lengkap'),
            'ringkasan' => $this->kasir->ringkasan()
        ));
        $this->load->view('template/footer');
    }
}
