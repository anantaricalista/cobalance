<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Akun extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_akun', 'akun');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['parent_list'] = $this->akun->get_all();
        $this->load->view('template/header');
        $this->load->view('akun_view', $data);
        $this->load->view('template/footer');
    }

    public function ajax_list()
    {
        $list = $this->akun->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $akun) {
            $no++;
            $row = array();
            $row[] = $no;
            $row[] = $akun->kode_akun;
            $row[] = $akun->nama_akun;
            $row[] = $akun->tipe_akun;
            $row[] = $akun->nama_akun_induk;
            $row[] = '<a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit_akun(' . "'" . $akun->id_akun . "'" . ')">
                        <i class="glyphicon glyphicon-pencil"></i>
                      </a>
                      <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="delete_akun(' . "'" . $akun->id_akun . "'" . ')">
                        <i class="glyphicon glyphicon-trash"></i>
                      </a>';
            $data[] = $row;
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->akun->count_all(),
            "recordsFiltered" => $this->akun->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    public function ajax_edit($id)
    {
        $data = $this->akun->get_by_id($id);
        echo json_encode($data);
    }

    public function ajax_add()
    {
        $id_akun_induk = $this->input->post('id_akun_induk');
        if (empty($id_akun_induk)) {
            $id_akun_induk = NULL;
        }
        $data = array(
            'kode_akun' => $this->input->post('kode_akun'),
            'nama_akun' => $this->input->post('nama_akun'),
            'tipe_akun' => $this->input->post('tipe_akun'),
            'id_akun_induk' => $id_akun_induk,
            'id_perusahaan' => $this->session->userdata('id_perusahaan')
        );
        $this->akun->save($data);
        echo json_encode(array("status" => TRUE));
    }

    public function ajax_update()
    {
        $id_akun = $this->input->post('id_akun');
        $kode_akun = $this->input->post('kode_akun');
        $nama_akun = $this->input->post('nama_akun');

        // Cek apakah akun induk dipilih; jika tidak, set ke null
        $parent = $this->input->post('id_akun_induk');
        if (empty($parent)) {
            $parent = null;
        }

        $data = array(
            'kode_akun' => $kode_akun,
            'nama_akun' => $nama_akun,
            'id_akun_induk' => $parent
        );

        $where = array(
            'id_akun' => $id_akun,
            'id_perusahaan' => $this->session->userdata('id_perusahaan')
        );

        $this->akun->update($where, $data);
        echo json_encode(array("status" => TRUE));
    }


    public function ajax_delete($id)
    {
        $this->akun->delete_by_id($id);
        echo json_encode(array("status" => TRUE));
    }
}
