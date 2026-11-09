<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Persediaan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('persediaan_service');
    }

    public function index()
    {
        $id = $this->input->get('produk');
        $before = $this->input->get('sebelum');
        if ($id === null && $before !== null) {
            show_404();
            return;
        }
        try {
            $history = $id === null ? null : $this->persediaan_service->riwayat($id, $before);
        } catch (InvalidArgumentException $e) {
            show_404();
            return;
        } catch (DomainException $e) {
            show_404();
            return;
        }
        $this->load->view('template/header');
        $this->load->view('persediaan_view', array('saldo' => $this->persediaan_service->saldo(), 'riwayat' => $history));
        $this->load->view('template/footer');
    }
}
