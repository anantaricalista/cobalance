<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Jurnal extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_jurnal', 'jurnal');
        // Untuk dropdown akun pada detail jurnal
        $this->load->model('M_akun', 'akun');
        $this->load->helper('url');
    }

    // Tampilkan halaman dengan template header & footer
    public function index()
    {
        $data['akun_list'] = $this->akun->get_all();
        $this->load->view('template/header');
        $this->load->view('jurnal_view', $data);
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
            // Hanya tampilkan ikon saja pada tombol aksi
            $row[] = '<a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit_jurnal(' . "'" . $jurnal->id_jurnal . "'" . ')">
                        <i class="glyphicon glyphicon-pencil"></i>
                      </a>
                      <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="delete_jurnal(' . "'" . $jurnal->id_jurnal . "'" . ')">
                        <i class="glyphicon glyphicon-trash"></i>
                      </a>
                      <a class="btn btn-sm btn-warning" target="_blank" href="' . site_url('jurnal/export_pdf/' . $jurnal->id_jurnal) . '" title="Export PDF">
                        <i class="glyphicon glyphicon-file"></i>
                      </a>';
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

    // Ambil data jurnal (header dan detail) berdasarkan id untuk proses edit
    public function ajax_edit($id)
    {
        $data = $this->jurnal->get_by_id($id);
        if (!$data) {
            return $this->error('Jurnal tidak ditemukan.', 404);
        }
        $data->detail = $this->jurnal->get_details($id);
        echo json_encode($data);
    }

    // Proses insert jurnal transaksi beserta detailnya
    public function ajax_add()
    {
        $header = array(
            'tanggal' => $this->input->post('tanggal'),
            'deskripsi' => $this->input->post('deskripsi'),
            'referensi' => $this->input->post('referensi'),
            'id_perusahaan' => $this->session->userdata('id_perusahaan')
        );
        $details = $this->details_from_input();
        if ($details === false || !$this->valid_date($header['tanggal'])) {
            return $this->error('Tanggal atau detail jurnal tidak valid. Pastikan akun dan nominal diisi dengan benar.', 400);
        }
        if (!$this->jurnal->save($header, $details)) {
            return $this->error('Jurnal gagal disimpan.', 500);
        }
        echo json_encode(array("status" => TRUE));
    }

    // Proses update jurnal transaksi dan detailnya
    public function ajax_update()
    {
        $id_jurnal = $this->input->post('id_jurnal');
        if (!$this->jurnal->get_by_id($id_jurnal)) {
            return $this->error('Jurnal tidak ditemukan.', 404);
        }
        $header = array(
            'tanggal' => $this->input->post('tanggal'),
            'deskripsi' => $this->input->post('deskripsi'),
            'referensi' => $this->input->post('referensi')
        );
        $details = $this->details_from_input();
        if ($details === false || !$this->valid_date($header['tanggal'])) {
            return $this->error('Tanggal atau detail jurnal tidak valid. Pastikan akun dan nominal diisi dengan benar.', 400);
        }
        if (!$this->jurnal->update($id_jurnal, $header, $details)) {
            return $this->error('Jurnal gagal diperbarui.', 500);
        }
        echo json_encode(array("status" => TRUE));
    }

    // Proses hapus jurnal (detail akan terhapus otomatis jika foreign key sudah diset cascade)
    public function ajax_delete($id)
    {
        if (!$this->jurnal->get_by_id($id)) {
            return $this->error('Jurnal tidak ditemukan.', 404);
        }
        if (!$this->jurnal->delete_by_id($id)) {
            return $this->error('Jurnal gagal dihapus.', 500);
        }
        echo json_encode(array("status" => TRUE));
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

    private function details_from_input()
    {
        $accounts = $this->input->post('detail_akun');
        $debits = $this->input->post('detail_debit');
        $credits = $this->input->post('detail_kredit');
        if ($accounts === null && $debits === null && $credits === null) {
            return array();
        }
        if (!is_array($accounts) || !is_array($debits) || !is_array($credits)
            || count($accounts) !== count($debits) || count($accounts) !== count($credits)) {
            return false;
        }
        $details = array();
        foreach ($accounts as $i => $account) {
            if (!isset($debits[$i], $credits[$i]) || !$this->akun->get_by_id($account)
                || !is_numeric($debits[$i]) || !is_numeric($credits[$i])
                || $debits[$i] < 0 || $credits[$i] < 0) {
                return false;
            }
            $details[] = array('id_akun' => $account, 'debit' => $debits[$i], 'kredit' => $credits[$i]);
        }
        return $details;
    }

    private function valid_date($value)
    {
        if (!is_string($value)) {
            return false;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function error($message, $code)
    {
        $this->output->set_status_header($code)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
