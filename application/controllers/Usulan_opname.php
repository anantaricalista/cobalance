<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Usulan_opname extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!in_array($this->session->userdata('role'), array('admin', 'akuntansi', 'keuangan'), true)) {
            show_error('Anda tidak memiliki akses ke halaman ini.', 403);
            exit;
        }
        $this->load->model('M_usulan_opname', 'usulan');
    }

    public function index()
    {
        $token = $this->session->userdata('usulan_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('usulan_form_token', $token);
        }
        $this->load->view('template/header');
        $this->load->view('usulan_opname_view', array('rows' => $this->usulan->daftar(), 'form_token' => $token));
        $this->load->view('template/footer');
    }

    public function ubah()
    {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
            return;
        }
        $token = $this->input->post('usulan_form_token');
        $expected = $this->session->userdata('usulan_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            show_error('Token formulir tidak valid. Muat ulang halaman.', 403);
            return;
        }
        $status = $this->input->post('status');
        if ($this->usulan->ubah_status($this->input->post('id'), $status)) {
            $this->session->set_flashdata('sukses', 'Status usulan diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Usulan tidak ditemukan atau sudah diproses.');
        }
        redirect('usulan_opname');
    }
}
