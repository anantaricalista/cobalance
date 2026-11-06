<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Neraca extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_neraca', 'neraca');
        $this->load->helper('url');
    }

    public function index()
    {
        // Default end_date: hari ini
        $end_date = date('Y-m-d');
        $data['end_date'] = $end_date;
        $data_sheet = $this->neraca->get_all_balance_sheet($end_date);
        $data['aset'] = $data_sheet['aset'];
        $data['liabilitas'] = $data_sheet['liabilitas'];
        $data['ekuitas'] = $data_sheet['ekuitas'];

        $this->load->view('template/header');
        $this->load->view('neraca_view', $data);
        $this->load->view('template/footer');
    }

    public function ajax_get_neraca()
    {
        $end_date = $this->input->post('end_date');
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }
        $data_sheet = $this->neraca->get_all_balance_sheet($end_date);
        $response = array(
            'aset' => $data_sheet['aset'],
            'liabilitas' => $data_sheet['liabilitas'],
            'ekuitas' => $data_sheet['ekuitas']
        );
        echo json_encode($response);
    }

    public function export_pdf()
    {
        // Ambil parameter end_date dari GET, jika tidak ada gunakan tanggal hari ini
        $end_date = $this->input->get('end_date');
        if (empty($end_date)) {
            $end_date = date('Y-m-d');
        }

        // Ambil data neraca berdasarkan filter end_date
        $data_sheet = $this->neraca->get_all_balance_sheet($end_date);
        $data['aset'] = $data_sheet['aset'];
        $data['liabilitas'] = $data_sheet['liabilitas'];
        $data['ekuitas'] = $data_sheet['ekuitas'];
        $data['end_date'] = $end_date;

        // Render view neraca_pdf.php sebagai HTML
        $html = $this->load->view('neraca_pdf', $data, true);

        // Gunakan library Pdf untuk membuat file PDF
        $this->load->library('pdf');
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        $this->pdf->stream("neraca_" . $end_date . ".pdf", array("Attachment" => 0));
    }

}
