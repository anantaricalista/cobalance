<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Pastikan model pengguna sudah dibuat, sesuaikan nama model jika perlu
        $this->load->model('M_pengguna', 'User_model');
        $this->load->helper('url');
        $this->load->library('session');
    }

    // Tampilkan halaman login
    public function index()
    {
        // Jika sudah login, langsung redirect ke dashboard atau fitur utama
        if ($this->session->userdata('id_pengguna')) {
            redirect('dashboard');
        }
        $this->load->view('login');
    }

    // Proses login
    public function login()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $user = $this->User_model->get_by_username($username);
        if ($user && md5(md5($password)) == $user->password) {
            // Login berhasil, simpan data pengguna dan perusahaan ke session
            $this->session->set_userdata(array(
                'id_pengguna' => $user->id_pengguna,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_perusahaan' => $user->id_perusahaan, // tidak ditampilkan, hanya untuk referensi internal
                'nama_perusahaan' => $user->nama_perusahaan, // simpan nama perusahaan di session
                'role' => $user->role
            ));
            redirect('dashboard'); // atau halaman utama fitur
        } else {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('auth');
        }
    }

    // Proses logout
    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth');
    }
}
