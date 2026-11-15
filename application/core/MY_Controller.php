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
        if ($role === 'user') {
            $this->session->sess_destroy();
            show_error('Role User tidak lagi tersedia. Hubungi Admin Perusahaan untuk menetapkan role baru.', 403);
            exit;
        }
        $this->can_post_transactions = in_array($role, array('admin', 'akuntansi'), true);
        if (isset(self::$role_scope[$role]) && !in_array($this->router->fetch_class(), self::$role_scope[$role]['classes'], true)) {
            redirect(self::$role_scope[$role]['home']);
            exit;
        }
        if ($role === 'manajer') {
            $read_methods = array(
                'manajer' => array('index', 'kas', 'penjualan', 'tagihan'),
                'persediaan' => array('index'),
                'jurnal' => array('index', 'ajax_list', 'ajax_detail', 'export_pdf'),
                'jurnal_umum' => array('index', 'ajax_list', 'ajax_detail'),
                'buku_besar' => array('index', 'ajax_get_ledger', 'export_pdf'),
                'laporan' => array('index', 'export_pdf'),
                'laba_rugi' => array('index', 'ajax_get_laba_rugi', 'export_pdf'),
                'neraca' => array('index', 'ajax_get_neraca', 'export_pdf'),
                'bagan_akun' => array('index', 'ajax_get_chart', 'export_pdf')
            );
            $class = $this->router->fetch_class();
            if (!isset($read_methods[$class]) || !in_array($this->router->fetch_method(), $read_methods[$class], true)) {
                show_error('Role Manajer/Pemilik hanya memiliki akses baca-saja.', 403);
                exit;
            }
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
        'manajer' => array('home' => 'manajer', 'classes' => array('manajer', 'persediaan', 'jurnal',
            'jurnal_umum', 'buku_besar', 'laporan', 'laba_rugi', 'neraca', 'bagan_akun')),
        'akuntansi' => array('home' => 'akuntansi/beranda', 'classes' => array(
            'akun', 'pelanggan', 'pemasok', 'produk', 'pajak', 'pengaturan_akun',
            'saldo_awal', 'penjualan', 'beban_penjualan_jasa', 'penerimaan_piutang',
            'pembelian', 'pembayaran_utang', 'kas_bank', 'aset_tetap', 'persediaan',
            'penyesuaian_persediaan', 'usulan_opname', 'retur_persediaan', 'modal_prive',
            'penyesuaian_akuntansi', 'jurnal', 'buku_besar', 'jurnal_umum', 'laporan',
            'laba_rugi', 'neraca', 'bagan_akun', 'lampiran'
        )),
        'penjualan' => array('home' => 'sales/beranda', 'classes' => array('penjualan', 'penerimaan_piutang', 'pelanggan', 'produk', 'lampiran'))
    );
}
