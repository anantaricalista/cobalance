<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    private $dummy_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_pengguna', 'User_model');
        $this->load->helper('url');
        $this->load->library('session');
    }

    public function index()
    {
        if ($this->session->userdata('system_admin') === true) {
            redirect('system/perusahaan');
            return;
        }
        if ($this->session->userdata('id_pengguna')) {
            redirect('dashboard');
            return;
        }
        $token = bin2hex(random_bytes(32));
        $this->session->set_userdata('login_token', $token);
        $this->load->view('login', array('login_token' => $token));
    }

    public function login()
    {
        if ($this->session->userdata('system_admin') === true) {
            redirect('system/perusahaan');
            return;
        }
        if ($this->session->userdata('id_pengguna')) {
            redirect('dashboard');
            return;
        }
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
            return;
        }

        $token = $this->input->post('login_token');
        $session_token = $this->session->userdata('login_token');
        if (!is_string($token) || !is_string($session_token) || !hash_equals($session_token, $token)) {
            show_error('Sesi formulir login tidak valid. Muat ulang halaman login.', 403);
            return;
        }
        $this->session->unset_userdata('login_token');

        if (!$this->User_model->allow_login_attempt($this->input->ip_address())) {
            $this->session->set_flashdata('error', 'Terlalu banyak percobaan login dari jaringan ini. Coba lagi setelah 15 menit.');
            redirect('auth');
            return;
        }

        $username = $this->input->post('username');
        $password = $this->input->post('password');
        if (!is_string($username) || !is_string($password) || $username === ''
            || strlen($username) > 400 || $password === '' || strlen($password) > 72
            || strpos($password, "\0") !== false) {
            $this->session->set_flashdata('error', 'Username atau password tidak valid. Password maksimal 72 byte.');
            redirect('auth');
            return;
        }

        $is_system = strcasecmp(trim($username), 'system') === 0;
        $user = $this->User_model->get_by_username($is_system ? 'system' : $username);
        $legacy = $user && preg_match('/\A[0-9a-f]{32}\z/', $user->password);
        $valid = $legacy
            ? hash_equals($user->password, md5(md5($password)))
            : password_verify($password, $user ? $user->password : $this->dummy_hash);

        $eligible = $user && ($is_system
            ? $user->id_perusahaan === null && $user->role === 'admin'
            : $user->id_perusahaan !== null && $user->nama_perusahaan !== null);
        if ($valid && $eligible) {
            if ($legacy || password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($hash === false || !$this->User_model->upgrade_password($user->id_pengguna, $user->password, $hash)) {
                    show_error('Password tidak dapat diperbarui. Silakan coba lagi atau hubungi administrator.', 503);
                    return;
                }
            }
            $this->session->sess_regenerate(true);
            if ($is_system) {
                $this->session->set_userdata(array(
                    'system_admin' => true,
                    'id_system_admin' => $user->id_pengguna,
                    'username' => $user->username
                ));
                redirect('system/perusahaan');
                return;
            }
            $this->session->set_userdata(array(
                'id_pengguna' => $user->id_pengguna,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_perusahaan' => $user->id_perusahaan,
                'nama_perusahaan' => $user->nama_perusahaan,
                'role' => $user->role
            ));
            $scope = MY_Controller::$role_scope;
            redirect(isset($scope[$user->role]) ? $scope[$user->role]['home'] : 'dashboard');
        } else {
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('auth');
        }
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth');
    }
}
