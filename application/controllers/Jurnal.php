<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Jurnal extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_jurnal', 'jurnal');
        $this->load->helper('url');
    }

    // Tampilkan halaman dengan template header & footer
    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('jurnal_view');
        $this->load->view('template/footer');
    }

    // Menampilkan data jurnal transaksi untuk DataTable (server side)
    public function ajax_list()
    {
        $list = $this->jurnal->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $jurnal) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $jurnal->tanggal;
            $row[] = html_escape($jurnal->deskripsi);
            $row[] = html_escape($jurnal->referensi);
            $row[] = '<div class="journal-actions">' .
                '<button type="button" class="btn btn-sm btn-primary view-journal" data-jurnal-id="' .
                (int) $jurnal->id_jurnal . '"><i class="glyphicon glyphicon-list-alt"></i> Detail</button>' .
                '<a class="btn btn-sm btn-default" target="_blank" href="' .
                site_url('jurnal/export_pdf/' . (int) $jurnal->id_jurnal) .
                '" title="Buka jurnal sebagai PDF"><i class="glyphicon glyphicon-file"></i> PDF</a></div>';
            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->jurnal->count_all(),
            "recordsFiltered" => $this->jurnal->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    // Endpoint edit lama dipertahankan agar klien lama menerima respons yang jelas.
    public function ajax_edit($id)
    {
        return $this->read_only();
    }

    public function ajax_detail($id)
    {
        if ($this->input->method() !== 'get' || !is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(array('status' => false, 'message' => 'ID jurnal tidak valid.')));
        }
        $journal = $this->jurnal->get_by_id($id);
        if (!$journal) {
            return $this->output->set_status_header(404)->set_content_type('application/json')
                ->set_output(json_encode(array('status' => false, 'message' => 'Jurnal tidak ditemukan.')));
        }
        $details = array();
        foreach ($this->jurnal->get_details($id) as $detail) {
            $details[] = array(
                'kode_akun' => $detail->kode_akun,
                'nama_akun' => $detail->nama_akun,
                'debit' => $detail->debit,
                'kredit' => $detail->kredit
            );
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(array(
            'tanggal' => $journal->tanggal,
            'deskripsi' => $journal->deskripsi,
            'referensi' => $journal->referensi,
            'details' => $details
        )));
    }

    // Endpoint mutasi lama ditutup: jurnal harus berasal dari modul transaksi.
    public function ajax_add()
    {
        return $this->read_only();
    }

    public function ajax_update()
    {
        return $this->read_only();
    }

    public function ajax_delete($id)
    {
        return $this->read_only();
    }

    // Fitur Export PDF untuk data jurnal transaksi
    public function export_pdf($id)
    {
        // Ambil data jurnal (header dan detail)
        $jurnal = $this->jurnal->get_by_id($id);
        if (!$jurnal) {
            show_404();
            return;
        }
        $jurnal->detail = $this->jurnal->get_details($id);
        $data['jurnal'] = $jurnal;

        // Render view sebagai HTML
        $html = $this->load->view('jurnal_pdf', $data, true);

        // Gunakan library Pdf untuk membuat file PDF
        $this->load->library('pdf');
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        $this->pdf->stream("jurnal_{$id}.pdf", array("Attachment" => 0));
    }

    private function read_only()
    {
        $this->output->set_status_header(410)->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => false,
                'message' => 'Jurnal Transaksi hanya untuk melihat dan mengekspor jurnal. Buat penyesuaian melalui modul Penyesuaian Akuntansi.'
            )));
    }
}
