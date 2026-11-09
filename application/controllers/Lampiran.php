<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lampiran extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('lampiran_service');
    }

    public function unduh($id = null)
    {
        try {
            $file = $this->lampiran_service->ambil($id);
        } catch (Throwable $e) {
            log_message('error', 'Lampiran: ' . $e->getMessage());
            show_error('Lampiran gagal dibaca.', 500);
            return;
        }
        if (!$file) {
            show_404();
            return;
        }
        $this->output->set_content_type($file->lampiran_tipe)
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: attachment; filename="' . $file->lampiran_nama . '"')
            ->set_output($file->lampiran);
    }
}
