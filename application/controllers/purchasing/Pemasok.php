<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Purchasing_Controller.php';

class Pemasok extends Purchasing_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchasing/M_purchasing', 'purchasing');
    }

    public function index()
    {
        $search = $this->input->get('cari');
        $search = is_string($search) ? substr(trim($search), 0, 100) : '';
        $this->load->view('template/header');
        $this->load->view('purchasing/pemasok', array('rows' => $this->purchasing->pemasok($search), 'cari' => $search));
        $this->load->view('template/footer');
    }
}
