<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Purchasing_Controller.php';

class Pemasok extends Purchasing_Controller
{
    public function index()
    {
        redirect('pemasok');
    }
}
