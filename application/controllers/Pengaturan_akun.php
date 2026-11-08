<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Master_Controller.php';

class Pengaturan_akun extends Master_Controller
{
    protected $config_master = array(
        'table' => 'pengaturan_akun', 'id' => 'id_pengaturan_akun', 'title' => 'Pengaturan Akun', 'route' => 'pengaturan_akun',
        'search' => array('jenis_pengaturan'),
        'list' => array('jenis_pengaturan', 'id_akun'),
        'account_settings' => array(
            'kas_bank' => '1100',
            'piutang_usaha' => '1200',
            'utang_usaha' => '2100',
            'persediaan' => '1700',
            'pajak_masukan' => '1500',
            'pajak_keluaran' => '2200',
            'beban_pokok_penjualan' => '5100',
            'pendapatan_default' => '4000',
            'modal' => '3100',
            'rugi_pelepasan_aset' => '9000'
        ),
        'fields' => array(
            'jenis_pengaturan' => array('label' => 'Jenis Pengaturan', 'type' => 'select', 'placeholder' => '-- Pilih Jenis Pengaturan --', 'options' => array(
                'kas_bank' => 'Kas dan Bank',
                'piutang_usaha' => 'Piutang Usaha',
                'utang_usaha' => 'Utang Usaha',
                'persediaan' => 'Persediaan',
                'pajak_masukan' => 'Pajak Masukan',
                'pajak_keluaran' => 'Pajak Keluaran',
                'beban_pokok_penjualan' => 'Beban Pokok Penjualan',
                'pendapatan_default' => 'Pendapatan Default',
                'modal' => 'Modal',
                'rugi_pelepasan_aset' => 'Rugi Pelepasan Aset'
            ), 'required' => true),
            'id_akun' => array('label' => 'Akun', 'type' => 'account', 'required' => true)
        )
    );
}
