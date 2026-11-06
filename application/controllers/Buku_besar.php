<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Buku_besar extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_buku_besar', 'buku_besar');
        $this->load->model('M_akun', 'akun');
        $this->load->helper('url');
    }

    // Tampilkan halaman Buku Besar dengan form filter
    public function index()
    {
        $data['akun_list'] = $this->akun->get_all();
        $this->load->view('template/header');
        $this->load->view('buku_besar_view', $data);
        $this->load->view('template/footer');
    }

    // Fungsi AJAX untuk mengambil data ledger berdasarkan filter
    public function ajax_get_ledger()
    {
        $id_akun = $this->input->post('id_akun');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');

        $result = $this->buku_besar->get_ledger($id_akun, $start_date, $end_date);
        echo json_encode($result);
    }

    // Export Buku Besar ke PDF
    public function export_pdf()
    {
        $id_akun = $this->input->get('id_akun');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');

        $result = $this->buku_besar->get_ledger($id_akun, $start_date, $end_date);
        $data['opening'] = $result['opening'];
        $data['transactions'] = $result['transactions'];

        // Dapatkan data akun untuk header laporan
        $data['akun'] = $this->akun->get_by_id($id_akun);
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $html = $this->load->view('buku_besar_pdf', $data, true);
        $this->load->library('pdf'); // Library PDF berbasis TCPDF
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'landscape');
        $this->pdf->render();
        $this->pdf->stream("buku_besar.pdf", array("Attachment" => 0));
    }
}
