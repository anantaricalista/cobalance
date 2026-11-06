<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Laba_rugi extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_laba_rugi', 'laba_rugi');
        $this->load->helper('url');
    }

    // Tampilkan halaman laporan Laba Rugi
    public function index()
    {
        // Set default periode: bulan berjalan
        $data['start_date'] = date('Y-m-01');
        $data['end_date'] = date('Y-m-t');
        $this->load->view('template/header');
        $this->load->view('laba_rugi_view', $data);
        $this->load->view('template/footer');
    }

    // Ambil data Laporan Laba Rugi via AJAX
    public function ajax_get_laba_rugi()
    {
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        $result = $this->laba_rugi->get_income_statement($start_date, $end_date);
        echo json_encode($result);
    }

    // Export laporan Laba Rugi ke PDF
    public function export_pdf()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $result = $this->laba_rugi->get_income_statement($start_date, $end_date);
        $data['pendapatan'] = $result['pendapatan'];
        $data['total_pendapatan'] = $result['total_pendapatan'];
        $data['beban'] = $result['beban'];
        $data['total_beban'] = $result['total_beban'];
        $data['laba_bersih'] = $result['laba_bersih'];
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $html = $this->load->view('laba_rugi_pdf', $data, true);
        $this->load->library('pdf'); // menggunakan library Pdf (TCPDF)
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        $this->pdf->stream("laporan_laba_rugi.pdf", array("Attachment" => 0));
    }
}
