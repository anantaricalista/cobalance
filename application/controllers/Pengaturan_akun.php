<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Master_Controller.php';

class Pengaturan_akun extends Master_Controller
{
    protected $config_master = array(
        'table' => 'pengaturan_akun', 'id' => 'id_pengaturan_akun', 'title' => 'Pengaturan Akun', 'route' => 'pengaturan_akun',
        'search' => array('jenis_pengaturan'),
        'list' => array('jenis_pengaturan', 'id_akun'),
        'help' => 'Gunakan kode pengaturan huruf kecil, angka, dan garis bawah, misalnya piutang_usaha atau utang_usaha. Modul transaksi akan menggunakan kode yang disepakati saat dikembangkan.',
        'fields' => array(
            'jenis_pengaturan' => array('label' => 'Kode Pengaturan', 'type' => 'text', 'maxlength' => 50, 'pattern' => '/^[a-z][a-z0-9_]*$/D', 'required' => true),
            'id_akun' => array('label' => 'Akun', 'type' => 'account', 'required' => true)
        )
    );
}
