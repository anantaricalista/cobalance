<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Perusahaan extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('system_admin') !== true || $this->session->userdata('id_pengguna')) {
            show_error('Akses hanya untuk system admin.', 403);
            exit;
        }
        $this->load->model('M_pengguna', 'system_user');
        $id = $this->session->userdata('id_system_admin');
        if (!$id || !$this->system_user->is_system_admin($id)) {
            $this->session->sess_destroy();
            redirect('auth');
            exit;
        }
        $this->load->model('System/M_perusahaan_system', 'system_company');
    }

    public function index()
    {
        $token = $this->session->userdata('system_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('system_form_token', $token);
        }
        $this->load->view('System/perusahaan', array(
            'companies' => $this->system_company->daftar(),
            'form_token' => $token
        ));
    }

    public function simpan()
    {
        $this->verify_form();
        $fields = array('nama_perusahaan', 'alamat', 'email', 'telepon', 'nama_admin', 'username', 'password');
        $input = array();
        foreach ($fields as $field) {
            $value = $this->input->post($field);
            if (!is_string($value)) {
                $this->fail('Data formulir tidak valid.');
                return;
            }
            $input[$field] = $field === 'password' ? $value : trim($value);
        }
        if ($input['nama_perusahaan'] === '' || mb_strlen($input['nama_perusahaan']) > 255
            || $input['nama_admin'] === '' || mb_strlen($input['nama_admin']) > 255
            || !preg_match('/\A[a-zA-Z0-9._-]{1,100}\z/', $input['username'])
            || strcasecmp($input['username'], 'system') === 0
            || strlen($input['password']) < 8 || strlen($input['password']) > 72
            || strpos($input['password'], "\0") !== false
            || mb_strlen($input['email']) > 100 || ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL))
            || mb_strlen($input['telepon']) > 20 || strlen($input['alamat']) > 65535) {
            $this->fail('Periksa isian: nama wajib diisi, username hanya boleh huruf, angka, titik, garis bawah, atau tanda hubung (selain system), dan password harus 8–72 byte.');
            return;
        }
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        if ($hash === false) {
            show_error('Password admin tidak dapat disiapkan.', 500);
            return;
        }
        unset($input['password']);
        try {
            $this->system_company->buat($input, $hash);
        } catch (DomainException $e) {
            $this->fail($e->getMessage());
            return;
        } catch (RuntimeException $e) {
            log_message('error', 'Gagal membuat perusahaan: ' . $e->getMessage());
            show_error('Perusahaan gagal dibuat. Periksa database dan migrasi, lalu coba lagi.', 500);
            return;
        }
        $this->session->set_flashdata('success', 'Perusahaan, admin, dan master akun berhasil dibuat.');
        redirect('System/Perusahaan');
    }

    public function logout()
    {
        $this->verify_form();
        $this->session->sess_destroy();
        redirect('auth');
    }

    private function verify_form()
    {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
            exit;
        }
        $token = $this->input->post('system_form_token');
        $expected = $this->session->userdata('system_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            show_error('Sesi formulir tidak valid. Muat ulang halaman.', 403);
            exit;
        }
    }

    private function fail($message)
    {
        $this->session->set_flashdata('error', $message);
        redirect('System/Perusahaan');
    }
}
