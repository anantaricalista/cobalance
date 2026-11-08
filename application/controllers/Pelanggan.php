<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pelanggan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_pelanggan', 'pelanggan');
    }

    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('pelanggan_view');
        $this->load->view('template/footer');
    }

    public function ajax_list()
    {
        $search = $this->input->post('search');
        $order = $this->input->post('order');
        $search = is_array($search) && isset($search['value']) && is_string($search['value']) ? $search['value'] : '';
        $column = isset($order[0]['column']) ? (int) $order[0]['column'] : 1;
        $direction = isset($order[0]['dir']) && $order[0]['dir'] === 'desc' ? 'desc' : 'asc';
        $start = max(0, (int) $this->input->post('start'));
        $length = (int) $this->input->post('length');
        $length = $length === -1 ? -1 : max(1, min(100, $length));
        $rows = array();
        foreach ($this->pelanggan->get_datatables($search, $column, $direction, $start, $length) as $pelanggan) {
            $id = (int) $pelanggan->id_pelanggan;
            $rows[] = array(
                ++$start,
                html_escape($pelanggan->kode_pelanggan),
                html_escape($pelanggan->nama_pelanggan),
                html_escape($pelanggan->telepon === null ? '' : $pelanggan->telepon),
                html_escape($pelanggan->email === null ? '' : $pelanggan->email),
                $pelanggan->aktif ? 'Aktif' : 'Tidak Aktif',
                '<button type="button" class="btn btn-sm btn-primary" title="Edit" onclick="edit_pelanggan(' . $id . ')"><i class="glyphicon glyphicon-pencil"></i></button> '
                . '<button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="delete_pelanggan(' . $id . ')"><i class="glyphicon glyphicon-trash"></i></button>'
            );
        }
        $this->json(array(
            'draw' => (int) $this->input->post('draw'),
            'recordsTotal' => $this->pelanggan->count_all(),
            'recordsFiltered' => $this->pelanggan->count_filtered($search),
            'data' => $rows
        ));
    }

    public function ajax_edit($id)
    {
        $data = $this->pelanggan->get_by_id($id);
        if (!$data) {
            return $this->error('Pelanggan tidak ditemukan.', 404);
        }
        $this->json($data);
    }

    public function ajax_add()
    {
        if (!$this->require_post()) {
            return;
        }
        $data = $this->customer_data();
        if ($data === false) {
            return;
        }
        $data['id_perusahaan'] = $this->session->userdata('id_perusahaan');
        $this->write('save', $data);
    }

    public function ajax_update()
    {
        if (!$this->require_post()) {
            return;
        }
        $id = $this->input->post('id_pelanggan');
        if (!$this->pelanggan->get_by_id($id)) {
            return $this->error('Pelanggan tidak ditemukan.', 404);
        }
        $data = $this->customer_data();
        if ($data === false) {
            return;
        }
        $this->write('update', $data, $id);
    }

    public function ajax_delete($id)
    {
        if (!$this->require_post()) {
            return;
        }
        if (!$this->pelanggan->get_by_id($id)) {
            return $this->error('Pelanggan tidak ditemukan.', 404);
        }
        $this->write('delete', null, $id);
    }

    private function customer_data()
    {
        $fields = array('kode_pelanggan', 'nama_pelanggan', 'alamat', 'telepon', 'email', 'aktif');
        $data = array();
        foreach ($fields as $field) {
            $value = $this->input->post($field);
            if (!is_string($value)) {
                $this->error('Data pelanggan tidak valid.', 400);
                return false;
            }

            $data[$field] = trim($value);
        }
        if ($data['kode_pelanggan'] === '' || $data['nama_pelanggan'] === ''
            || $this->text_length($data['kode_pelanggan']) > 50
            || $this->text_length($data['nama_pelanggan']) > 255
            || $this->text_length($data['telepon']) > 20
            || $this->text_length($data['email']) > 100
            || strlen($data['alamat']) > 65535
            || !in_array($data['aktif'], array('0', '1'), true)
            || ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
            $this->error('Periksa kode, nama, email, status, dan panjang data pelanggan.', 400);
            return false;
        }
        foreach (array('alamat', 'telepon', 'email') as $field) {
            if ($data[$field] === '') {
                $data[$field] = null;
            }
        }
        return $data;
    }

    private function text_length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->error('Metode permintaan tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function write($action, $data, $id = null)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if ($action === 'save') {
            $success = $this->pelanggan->save($data);
        } elseif ($action === 'update') {
            $success = $this->pelanggan->update($id, $data);
        } else {
            $success = $this->pelanggan->delete_by_id($id);
        }
        $error = $success ? null : $this->db->error();
        $this->db->db_debug = $debug;
        if (!$success) {
            if ((int) $error['code'] === 1062) {
                return $this->error('Kode pelanggan sudah digunakan.', 409);
            }
            if ((int) $error['code'] === 1451) {
                return $this->error('Pelanggan digunakan dalam transaksi dan tidak dapat dihapus. Ubah status menjadi tidak aktif.', 409);
            }
            log_message('error', 'Gagal ' . $action . ' pelanggan: ' . $error['code'] . ' ' . $error['message']);
            return $this->error('Gagal menyimpan perubahan pelanggan.', 500);
        }
        $this->json(array('status' => true));
    }

    private function json($data)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status);
        $this->json(array('status' => false, 'message' => $message));
    }
}
