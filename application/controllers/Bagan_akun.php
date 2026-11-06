<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Bagan_akun extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_bagan_akun', 'bagan');
        $this->load->helper('url');
    }

    // Tampilkan halaman Bagan Akun dengan filter tanggal "as of"
    public function index()
    {
        // Default: hari ini sebagai tanggal "as of"
        $data['end_date'] = date('Y-m-d');
        $this->load->view('template/header');
        $this->load->view('bagan_akun_view', $data);
        $this->load->view('template/footer');
    }

    // Ambil data Bagan Akun via AJAX berdasarkan tanggal "as of"
    public function ajax_get_chart()
    {
        $end_date = $this->input->post('end_date');
        $result = $this->bagan->get_chart($end_date);
        echo json_encode($result);
    }

    // Export Bagan Akun ke PDF
    public function export_pdf()
    {
        $end_date = $this->input->get('end_date');
        $result = $this->bagan->get_chart($end_date);
        $data['accounts'] = $result;
        $data['end_date'] = $end_date;

        $this->load->library('pdf'); // pastikan library Pdf (TCPDF) sudah dibuat
        $html = $this->load->view('bagan_akun_pdf', $data, true);
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        $this->pdf->stream("bagan_akun.pdf", array("Attachment" => 0));
    }
}
