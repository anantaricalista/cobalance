<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Jika session pengguna tidak ada, redirect ke halaman login
        if (!$this->session->userdata('id_pengguna')) {
            redirect('auth');
        }
    }
}
