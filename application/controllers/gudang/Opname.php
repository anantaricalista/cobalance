<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Gudang_Controller.php';

class Opname extends Gudang_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('gudang/M_gudang', 'gudang');
    }

    public function index()
    {
        $token = $this->session->userdata('gudang_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('gudang_form_token', $token);
        }
        $this->load->view('template/header');
        $this->load->view('gudang/opname', array(
            'form_token' => $token,
            'produk' => $this->gudang->stok(),
            'usulan' => $this->gudang->usulan()
        ));
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
            return;
        }
        $token = $this->input->post('gudang_form_token');
        $expected = $this->session->userdata('gudang_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            show_error('Token formulir tidak valid. Muat ulang halaman.', 403);
            return;
        }
        try {
            $this->gudang->simpan_usulan($this->input->post('produk'), $this->input->post('jumlah_fisik'),
                $this->input->post('catatan'));
            $this->session->set_flashdata('sukses', 'Usulan opname dicatat dan menunggu tindak lanjut akuntansi/admin.');
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->session->set_flashdata('error', $e->getMessage());
        } catch (Throwable $e) {
            log_message('error', 'Usulan opname: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Usulan opname gagal disimpan.');
        }
        redirect('gudang/opname');
    }
}
