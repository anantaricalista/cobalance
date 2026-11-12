<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Gudang_Controller.php';

class Stok extends Gudang_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gudang/M_gudang', 'gudang');
    }

    public function index()
    {
        $search = $this->input->get('cari');
        $search = is_string($search) ? substr(trim($search), 0, 100) : '';
        $this->load->view('template/header');
        $this->load->view('gudang/stok', array('stok' => $this->gudang->stok($search), 'cari' => $search));
        $this->load->view('template/footer');
    }

    public function kartu($id = null)
    {
        $product = $this->gudang->produk($id);
        $before = $this->input->get('sebelum');
        if (!$product || ($before !== null && (!is_string($before) || !preg_match('/^[1-9][0-9]{0,9}$/D', $before)))) {
            show_404();
            return;
        }
        $this->load->view('template/header');
        $this->load->view('gudang/kartu_stok', array(
            'produk' => $product, 'sebelum' => $before,
            'kartu' => $this->gudang->kartu_stok($product->id_produk, $before)
        ));
        $this->load->view('template/footer');
    }
}
