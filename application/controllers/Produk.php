<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Master_Controller.php';

class Produk extends Master_Controller
{
    protected $config_master = array(
        'table' => 'produk', 'id' => 'id_produk', 'title' => 'Produk dan Jasa', 'route' => 'produk',
        'search' => array('kode_produk', 'nama_produk', 'tipe_produk', 'satuan'),
        'list' => array('kode_produk', 'nama_produk', 'tipe_produk', 'satuan', 'harga_jual', 'aktif'),
        'fields' => array(
            'kode_produk' => array('label' => 'Kode', 'type' => 'text', 'maxlength' => 50, 'required' => true),
            'nama_produk' => array('label' => 'Nama', 'type' => 'text', 'maxlength' => 255, 'required' => true),
            'tipe_produk' => array('label' => 'Tipe', 'type' => 'select', 'options' => array('barang' => 'Barang', 'jasa' => 'Jasa'), 'required' => true),
            'satuan' => array('label' => 'Satuan', 'type' => 'text', 'maxlength' => 30, 'required' => true),
            'harga_jual' => array('label' => 'Harga Jual', 'type' => 'decimal', 'pattern' => '/^\d{1,13}(\.\d{1,2})?$/D', 'step' => '0.01', 'required' => true),
            'id_akun_pendapatan' => array('label' => 'Akun Pendapatan', 'type' => 'account', 'account_types' => array('Pendapatan')),
            'id_akun_pembelian' => array('label' => 'Akun Pembelian / Beban', 'type' => 'account', 'account_types' => array('Beban'), 'account_roots' => array('1700')),
            'id_akun_persediaan' => array('label' => 'Akun Persediaan', 'type' => 'account', 'account_roots' => array('1700')),
            'id_akun_beban_pokok' => array('label' => 'Akun Beban Pokok', 'type' => 'account', 'account_roots' => array('5100')),
            'aktif' => array('label' => 'Status', 'type' => 'select', 'options' => array('1' => 'Aktif', '0' => 'Tidak Aktif'), 'required' => true)
        )
    );
}
