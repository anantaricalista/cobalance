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
            redirect('System/Perusahaan');
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
            redirect('System/Perusahaan');
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

        if (strcasecmp(trim($username), 'system') === 0) {
            $system_password = $this->system_password();
            if (is_string($system_password) && $system_password !== ''
                && hash_equals($system_password, $password)) {
                $this->session->sess_regenerate(true);
                $this->session->set_userdata(array('system_admin' => true, 'username' => 'system'));
                redirect('System/Perusahaan');
                return;
            }
            if (!is_string($system_password) || $system_password === '') {
                log_message('error', 'Password system admin belum dikonfigurasi.');
            }
            $this->session->set_flashdata('error', 'Username atau password salah.');
            redirect('auth');
            return;
        }

        $user = $this->User_model->get_by_username($username);
        $legacy = $user && preg_match('/\A[0-9a-f]{32}\z/', $user->password);
        $valid = $legacy
            ? hash_equals($user->password, md5(md5($password)))
            : password_verify($password, $user ? $user->password : $this->dummy_hash);

        if ($valid) {
            if ($legacy || password_needs_rehash($user->password, PASSWORD_DEFAULT)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($hash === false || !$this->User_model->upgrade_password($user->id_pengguna, $user->password, $hash)) {
                    show_error('Password tidak dapat diperbarui. Silakan coba lagi atau hubungi administrator.', 503);
                    return;
                }
            }
            $this->session->sess_regenerate(true);
            $this->session->set_userdata(array(
                'id_pengguna' => $user->id_pengguna,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'id_perusahaan' => $user->id_perusahaan,
                'nama_perusahaan' => $user->nama_perusahaan,
                'role' => $user->role
            ));
            redirect('dashboard');
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

    private function system_password()
    {
        $password = getenv('COBALANCE_SYSTEM_PASSWORD');
        if ($password !== false) {
            return $password;
        }

        $path = FCPATH . '.env';
        if (!file_exists($path)) {
            return false;
        }
        if (!is_readable($path)) {
            show_error('Konfigurasi system admin tidak dapat dibaca. Hubungi administrator.', 503);
            return false;
        }
        $settings = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($settings === false || !isset($settings['COBALANCE_SYSTEM_PASSWORD'])
            || !is_string($settings['COBALANCE_SYSTEM_PASSWORD'])) {
            show_error('Konfigurasi system admin tidak valid. Hubungi administrator.', 503);
            return false;
        }
        return $settings['COBALANCE_SYSTEM_PASSWORD'];
    }
}
