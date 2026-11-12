<?php
defined('BASEPATH') or exit('No direct script access allowed');

abstract class Gudang_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('system_admin') === true) {
            $this->session->sess_destroy();
            redirect('auth');
            exit;
        }
        if (!$this->session->userdata('id_pengguna')) {
            redirect('auth');
            exit;
        }
        $role = $this->session->userdata('role');
        if ($role !== 'gudang' && $role !== 'admin') {
            show_error('Anda tidak memiliki akses ke modul gudang.', 403);
            exit;
        }
    }
}
