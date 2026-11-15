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
        if ($role === 'keuangan' && in_array($this->router->fetch_class(), array('kas_bank', 'pembayaran_utang', 'penerimaan_piutang'), true)) {
            $token = $this->session->userdata('keuangan_form_token');
            if ($this->router->fetch_method() === 'simpan') {
                $submitted = $this->input->post('keuangan_form_token');
                if (!is_string($token) || $token === '' || !is_string($submitted) || !hash_equals($token, $submitted)) {
                    $this->output->set_status_header(403)->set_content_type('application/json')
                        ->set_output(json_encode(array('status' => false, 'message' => 'Token formulir tidak valid. Muat ulang halaman.')))->_display();
                    exit;
                }
            } elseif (!is_string($token) || $token === '') {
                $this->session->set_userdata('keuangan_form_token', bin2hex(random_bytes(32)));
            }
        }
    }

    // Role terbatas hanya boleh membuka controller modul perusahaan yang terdaftar di sini.
    public static $role_scope = array(
        'gudang' => array('home' => 'gudang/beranda', 'classes' => array()),
        'purchasing' => array('home' => 'purchasing/beranda', 'classes' => array('pembelian', 'pembayaran_utang', 'pemasok', 'produk')),
        'kasir' => array('home' => 'kasir/beranda', 'classes' => array('pos', 'penjualan', 'produk', 'pelanggan', 'lampiran')),
        'keuangan' => array('home' => 'keuangan/beranda', 'classes' => array('kas_bank', 'pembayaran_utang', 'penerimaan_piutang', 'lampiran')),
        'penjualan' => array('home' => 'sales/beranda', 'classes' => array('penjualan', 'penerimaan_piutang', 'pelanggan', 'produk', 'lampiran'))
    );
}
