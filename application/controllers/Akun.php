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
            $row[] = html_escape($akun->kode_akun);
            $row[] = html_escape($akun->nama_akun);
            $row[] = html_escape($akun->tipe_akun);
            $row[] = html_escape($akun->nama_akun_induk === null ? '' : $akun->nama_akun_induk);
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
        if (!$data) {
            return $this->error('Akun tidak ditemukan.', 404);
        }
        echo json_encode($data);
    }

    public function ajax_options()
    {
        echo json_encode($this->akun->get_all());
    }

    public function ajax_add()
    {
        $data = $this->account_data();
        if ($data === false) {
            return;
        }
        $data['id_perusahaan'] = $this->session->userdata('id_perusahaan');
        if (!$this->akun->save($data)) {
            return $this->error('Akun gagal disimpan.', 500);
        }
        echo json_encode(array("status" => TRUE));
    }

    public function ajax_update()
    {
        $id_akun = $this->input->post('id_akun');
        if (!$this->akun->get_by_id($id_akun)) {
            return $this->error('Akun tidak ditemukan.', 404);
        }
        $data = $this->account_data($id_akun);
        if ($data === false) {
            return;
        }
        $where = array(
            'id_akun' => $id_akun,
            'id_perusahaan' => $this->session->userdata('id_perusahaan')
        );

        if ($this->akun->update($where, $data) === false) {
            return $this->error('Akun gagal diperbarui.', 500);
        }
        echo json_encode(array("status" => TRUE));
    }


    public function ajax_delete($id)
    {
        if (!$this->akun->get_by_id($id)) {
            return $this->error('Akun tidak ditemukan.', 404);
        }
        if ($this->akun->is_in_use($id)) {
            return $this->error('Akun tidak dapat dihapus karena digunakan sebagai akun induk atau dalam jurnal.', 400);
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $success = $this->akun->delete_by_id($id);
        $error = $success ? null : $this->db->error();
        $this->db->db_debug = $debug;
        if (!$success && (int) $error['code'] === 1451) {
            return $this->error('Akun tidak dapat dihapus karena digunakan oleh data lain.', 409);
        }
        if (!$success) {
            log_message('error', 'Gagal menghapus akun: ' . $error['code'] . ' ' . $error['message']);
            return $this->error('Akun gagal dihapus.', 500);
        }
        echo json_encode(array("status" => TRUE));
    }

    private function account_data($id = null)
    {
        $kode = trim((string) $this->input->post('kode_akun'));
        $nama = trim((string) $this->input->post('nama_akun'));
        $tipe = $this->input->post('tipe_akun');
        $parent = $this->input->post('id_akun_induk');
        if ($kode === '' || $nama === '' || !in_array($tipe, array('Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban'), true)) {
            $this->error('Kode, nama, dan tipe akun wajib diisi.', 400);
            return false;
        }
        $seen = array();
        $parent_id = $parent;
        while ($parent_id !== null && $parent_id !== '') {
            if ((string) $parent_id === (string) $id || isset($seen[$parent_id])) {
                $this->error('Akun induk tidak boleh membentuk siklus.', 400);
                return false;
            }
            $seen[$parent_id] = true;
            $account = $this->akun->get_by_id($parent_id);
            if (!$account) {
                $this->error('Akun induk tidak ditemukan.', 400);
                return false;
            }
            $parent_id = $account->id_akun_induk;
        }
        return array('kode_akun' => $kode, 'nama_akun' => $nama, 'tipe_akun' => $tipe, 'id_akun_induk' => $parent === '' || $parent === null ? null : $parent);
    }

    private function error($message, $code)
    {
        $this->output->set_status_header($code)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
