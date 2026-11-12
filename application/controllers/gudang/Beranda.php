<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Gudang_Controller.php';

class Beranda extends Gudang_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gudang/M_gudang', 'gudang');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('gudang/beranda', array(
            'nama' => $this->session->userdata('nama_lengkap'),
            'ringkasan' => $this->gudang->ringkasan()
        ));
        $this->load->view('template/footer');
    }
}
