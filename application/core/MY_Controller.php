<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public $can_post_transactions = false;

    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('system_admin') === true) {
            $this->session->sess_destroy();
            redirect('auth');
            exit;
        }
        // Jika session pengguna tidak ada, redirect ke halaman login
        if (!$this->session->userdata('id_pengguna')) {
            redirect('auth');
        }
        $role = $this->session->userdata('role');
        $this->can_post_transactions = in_array($role, array('admin', 'akuntansi'), true);
        if (isset(self::$role_scope[$role]) && !in_array($this->router->fetch_class(), self::$role_scope[$role]['classes'], true)) {
            redirect(self::$role_scope[$role]['home']);
            exit;
        }
        if (in_array($this->router->fetch_method(), array('posting', 'batalkan'), true)
            && !$this->can_post_transactions) {
            show_error('Hanya Akuntansi atau Admin Perusahaan yang dapat mem-posting atau membatalkan transaksi.', 403);
        }
    }

    // Role terbatas hanya boleh membuka controller modul perusahaan yang terdaftar di sini.
    public static $role_scope = array(
        'gudang' => array('home' => 'gudang/beranda', 'classes' => array()),
        'purchasing' => array('home' => 'purchasing/beranda', 'classes' => array('pembelian', 'pembayaran_utang', 'pemasok', 'produk'))
    );
}
