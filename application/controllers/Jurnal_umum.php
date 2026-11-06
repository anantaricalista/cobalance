<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Jurnal_umum extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_jurnal_umum', 'jurnal_umum');
        $this->load->helper('url');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('jurnal_umum_view');
        $this->load->view('template/footer');
    }

    public function ajax_list()
    {
        // Pastikan model M_jurnal_umum juga menerapkan filter berdasarkan id_perusahaan
        $list = $this->jurnal_umum->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $journal) {
            $no++;
            $row = array();
            $row[] = $no;
            // Misal: format tanggal dapat diterapkan di view (menggunakan fungsi JavaScript seperti formatDateIndo())
            $row[] = $journal->tanggal;
            $row[] = $journal->deskripsi;
            $row[] = $journal->referensi;
            $row[] = '<a class="btn btn-sm btn-info" href="javascript:void(0)" title="Lihat Detail" onclick="view_detail(' . "'" . $journal->id_jurnal . "'" . ')">
                        <i class="glyphicon glyphicon-eye-open"></i>
                      </a>';
            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->jurnal_umum->count_all(),
            "recordsFiltered" => $this->jurnal_umum->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    public function ajax_detail($id)
    {
        $data = $this->jurnal_umum->get_detail_by_id($id);
        echo json_encode($data);
    }
}
