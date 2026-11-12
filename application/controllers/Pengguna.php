<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pengguna extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_pengguna', 'pengguna');
    }

    public function index()
    {
        $token = $this->session->userdata('user_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('user_form_token', $token);
        }
        $this->load->view('template/header');
        $this->load->view('pengguna_view', array('form_token' => $token));
        $this->load->view('template/footer');
    }

    public function ajax_list()
    {
        if (!$this->require_post()) {
            return;
        }
        $search = $this->input->post('search');
        $search = is_array($search) && isset($search['value']) && is_string($search['value'])
            ? trim($search['value']) : '';
        $order = $this->input->post('order');
        $column = isset($order[0]['column']) ? (int) $order[0]['column'] - 1 : 0;
        $direction = isset($order[0]['dir']) && $order[0]['dir'] === 'desc' ? 'desc' : 'asc';
        $start = max(0, (int) $this->input->post('start'));
        $length = (int) $this->input->post('length');
        $length = $length === -1 ? -1 : max(1, min(100, $length));
        $company = $this->company_id();
        $rows = array();
        foreach ($this->pengguna->user_list($company, $search, $start, $length, $column, $direction) as $user) {
            $id = (int) $user->id_pengguna;
            $rows[] = array(
                ++$start,
                html_escape($user->nama_lengkap),
                html_escape($user->username),
                $user->role === 'admin' ? 'Admin Perusahaan' : 'User',
                '<button type="button" class="btn btn-sm btn-primary" title="Edit" onclick="edit_pengguna(' . $id . ')"><i class="glyphicon glyphicon-pencil"></i></button> '
                . '<button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="delete_pengguna(' . $id . ')"><i class="glyphicon glyphicon-trash"></i></button>'
            );
        }
        $this->json(array(
            'draw' => (int) $this->input->post('draw'),
            'recordsTotal' => $this->pengguna->user_count($company),
            'recordsFiltered' => $this->pengguna->user_count($company, $search),
            'data' => $rows
        ));
    }

    public function ajax_edit($id)
    {
        if (!$this->valid_id($id)) {
            return $this->error('ID pengguna tidak valid.', 400);
        }
        $user = $this->pengguna->get_company_user($this->company_id(), (int) $id);
        if (!$user) {
            return $this->error('Pengguna tidak ditemukan.', 404);
        }
        $this->json($user);
    }

    public function ajax_save()
    {
        if (!$this->require_post() || !$this->verify_form_token()) {
            return;
        }
        $raw_id = $this->input->post('id_pengguna');
        $id = null;
        if ($raw_id !== null && $raw_id !== '') {
            if (!is_string($raw_id) || !$this->valid_id($raw_id)) {
                return $this->error('ID pengguna tidak valid.', 400);
            }
            $id = (int) $raw_id;
        }
        $name = $this->input->post('nama_lengkap');
        $username = $this->input->post('username');
        $role = $this->input->post('role');
        $password = $this->input->post('password');
        if (!is_string($name) || !is_string($username) || !is_string($role) || !is_string($password)) {
            return $this->error('Data pengguna tidak valid.', 400);
        }
        $name = trim($name);
        $username = trim($username);
        if ($name === '' || $this->text_length($name) > 255
            || !preg_match('/\A[a-zA-Z0-9._-]{1,100}\z/', $username)
            || strcasecmp($username, 'system') === 0
            || !in_array($role, array('admin', 'user'), true)
            || ($id === null && $password === '')
            || ($password !== '' && (strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false))) {
            return $this->error('Periksa nama, username, role, dan password. Password baru harus 8–72 byte.', 400);
        }

        $data = array('nama_lengkap' => $name, 'username' => $username, 'role' => $role);
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            if ($data['password'] === false) {
                return $this->error('Password tidak dapat diproses.', 500);
            }
        }

        try {
            $this->pengguna->save_company_user($this->company_id(), $data, $id);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (RuntimeException $e) {
            log_message('error', 'Gagal menyimpan pengguna perusahaan: ' . $e->getMessage());
            return $this->error('Gagal menyimpan pengguna. Periksa database dan migrasi.', 500);
        }
        $this->json(array('status' => true));
    }

    public function ajax_delete($id)
    {
        if (!$this->require_post() || !$this->verify_form_token()) {
            return;
        }
        if (!$this->valid_id($id)) {
            return $this->error('ID pengguna tidak valid.', 400);
        }
        try {
            $this->pengguna->delete_company_user($this->company_id(), (int) $id);
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (RuntimeException $e) {
            log_message('error', 'Gagal menghapus pengguna perusahaan: ' . $e->getMessage());
            return $this->error('Gagal menghapus pengguna.', 500);
        }
        $this->json(array('status' => true));
    }

    private function company_id()
    {
        $company = $this->session->userdata('id_perusahaan');
        if (!is_numeric($company) || (int) $company < 1) {
            show_error('Sesi perusahaan tidak valid. Silakan login kembali.', 403);
            exit;
        }
        return (int) $company;
    }

    private function valid_id($id)
    {
        return is_string($id) && preg_match('/\A[1-9][0-9]*\z/', $id) && (int) $id > 0;
    }

    private function text_length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->error('Metode permintaan tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function verify_form_token()
    {
        $token = $this->input->post('user_form_token');
        $expected = $this->session->userdata('user_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            $this->error('Sesi formulir tidak valid. Muat ulang halaman.', 403);
            return false;
        }
        return true;
    }

    private function json($data)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status);
        $this->json(array('status' => false, 'message' => $message));
    }
}
