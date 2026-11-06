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
            $row[] = $jurnal->deskripsi;
            $row[] = $jurnal->referensi;
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
        // Detail jurnal (dari form, berupa array)
        $detail_akun = $this->input->post('detail_akun');
        $detail_debit = $this->input->post('detail_debit');
        $detail_kredit = $this->input->post('detail_kredit');
        $details = array();
        if ($detail_akun) {
            for ($i = 0; $i < count($detail_akun); $i++) {
                $details[] = array(
                    'id_akun' => $detail_akun[$i],
                    'debit' => $detail_debit[$i],
                    'kredit' => $detail_kredit[$i]
                );
            }
        }
        $this->jurnal->save($header, $details);
        echo json_encode(array("status" => TRUE));
    }

    // Proses update jurnal transaksi dan detailnya
    public function ajax_update()
    {
        $id_jurnal = $this->input->post('id_jurnal');
        $header = array(
            'tanggal' => $this->input->post('tanggal'),
            'deskripsi' => $this->input->post('deskripsi'),
            'referensi' => $this->input->post('referensi')
        );
        $this->jurnal->update_header($id_jurnal, $header);
        // Hapus detail lama, kemudian simpan detail baru
        $this->jurnal->delete_details($id_jurnal);
        $detail_akun = $this->input->post('detail_akun');
        $detail_debit = $this->input->post('detail_debit');
        $detail_kredit = $this->input->post('detail_kredit');
        $details = array();
        if ($detail_akun) {
            for ($i = 0; $i < count($detail_akun); $i++) {
                $details[] = array(
                    'id_jurnal' => $id_jurnal,
                    'id_akun' => $detail_akun[$i],
                    'debit' => $detail_debit[$i],
                    'kredit' => $detail_kredit[$i]
                );
            }
            $this->jurnal->save_details_batch($id_jurnal, $details);
        }
        echo json_encode(array("status" => TRUE));
    }

    // Proses hapus jurnal (detail akan terhapus otomatis jika foreign key sudah diset cascade)
    public function ajax_delete($id)
    {
        $this->jurnal->delete_by_id($id);
        echo json_encode(array("status" => TRUE));
    }

    // Fitur Export PDF untuk data jurnal transaksi
    public function export_pdf($id)
    {
        // Ambil data jurnal (header dan detail)
        $jurnal = $this->jurnal->get_by_id($id);
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
}
