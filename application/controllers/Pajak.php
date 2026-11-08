<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Master_Controller.php';

class Pajak extends Master_Controller
{
    protected $config_master = array(
        'table' => 'pajak', 'id' => 'id_pajak', 'title' => 'Pajak', 'route' => 'pajak',
        'search' => array('kode_pajak', 'nama_pajak'),
        'list' => array('kode_pajak', 'nama_pajak', 'tarif_persen', 'aktif'),
        'fields' => array(
            'kode_pajak' => array('label' => 'Kode Pajak', 'type' => 'text', 'maxlength' => 50, 'required' => true),
            'nama_pajak' => array('label' => 'Nama Pajak', 'type' => 'text', 'maxlength' => 100, 'required' => true),
            'tarif_persen' => array('label' => 'Tarif (%)', 'type' => 'decimal', 'pattern' => '/^\d{1,3}(\.\d{1,4})?$/D', 'max' => 100, 'step' => '0.0001', 'required' => true),
            'id_akun_pajak_masukan' => array('label' => 'Akun Pajak Masukan', 'type' => 'account', 'account_roots' => array('1500')),
            'id_akun_pajak_keluaran' => array('label' => 'Akun Pajak Keluaran', 'type' => 'account', 'account_roots' => array('2200')),
            'aktif' => array('label' => 'Status', 'type' => 'select', 'options' => array('1' => 'Aktif', '0' => 'Tidak Aktif'), 'required' => true)
        )
    );
}
