<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Purchasing_Controller.php';

class Beranda extends Purchasing_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchasing/M_purchasing', 'purchasing');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('purchasing/beranda', array(
            'nama' => $this->session->userdata('nama_lengkap'),
            'ringkasan' => $this->purchasing->ringkasan()
        ));
        $this->load->view('template/footer');
    }
}
