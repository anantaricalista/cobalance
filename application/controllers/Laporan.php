<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laporan extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_laporan', 'laporan');
        $this->load->helper('url');
    }

    // Tampilkan halaman laporan trial balance
    public function index()
    {
        $data['laporan'] = $this->laporan->get_trial_balance();
        $this->load->view('template/header');
        $this->load->view('laporan_view', $data);
        $this->load->view('template/footer');
    }

    // Export laporan ke PDF menggunakan TCPDF (melalui library Pdf.php)
    public function export_pdf()
    {
        $data['laporan'] = $this->laporan->get_trial_balance();
        $html = $this->load->view('laporan_pdf', $data, true);
        $this->load->library('pdf'); // Library PDF berbasis TCPDF
        $this->pdf->loadHtml($html);
        // Menggunakan orientasi landscape agar tabel lebih lebar
        $this->pdf->setPaper('A4', 'landscape');
        $this->pdf->render();
        $this->pdf->stream("laporan_akuntansi.pdf", array("Attachment" => 0));
    }
}
