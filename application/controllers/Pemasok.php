<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pemasok extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_pemasok', 'pemasok');
    }

    public function index()
    {
        if ($this->session->userdata('role') === 'purchasing') {
            $token = $this->session->userdata('pemasok_form_token');
            if (!is_string($token) || $token === '') {
                $this->session->set_userdata('pemasok_form_token', bin2hex(random_bytes(32)));
            }
        }
        $this->load->view('template/header');
        $this->load->view('pemasok_view');
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
        foreach ($this->pemasok->get_datatables($search, $column, $direction, $start, $length) as $pemasok) {
            $id = (int) $pemasok->id_pemasok;
            $actions = '<button type="button" class="btn btn-sm btn-default supplier-edit" title="Edit pemasok" aria-label="Edit pemasok" onclick="edit_pemasok(' . $id . ')"><i class="glyphicon glyphicon-pencil"></i> Edit</button>';
            if ($this->session->userdata('role') !== 'purchasing') {
                $actions .= ' <button type="button" class="btn btn-sm btn-default supplier-delete" title="Hapus pemasok" aria-label="Hapus pemasok" onclick="delete_pemasok(' . $id . ')"><i class="glyphicon glyphicon-trash"></i></button>';
            }
            $rows[] = array(
                ++$start,
                html_escape($pemasok->kode_pemasok),
                html_escape($pemasok->nama_pemasok),
                html_escape($pemasok->telepon === null ? '' : $pemasok->telepon),
                html_escape($pemasok->email === null ? '' : $pemasok->email),
                $pemasok->aktif ? '<span class="supplier-status is-active">Aktif</span>'
                    : '<span class="supplier-status is-inactive">Tidak Aktif</span>',
                $actions
            );
        }
        $this->json(array(
            'draw' => (int) $this->input->post('draw'),
            'recordsTotal' => $this->pemasok->count_all(),
            'recordsFiltered' => $this->pemasok->count_filtered($search),
            'data' => $rows
        ));
    }

    public function ajax_edit($id)
    {
        $data = $this->pemasok->get_by_id($id);
        if (!$data) {
            return $this->error('Pemasok tidak ditemukan.', 404);
        }
        $this->json($data);
    }

    public function ajax_add()
    {
        if (!$this->require_post()) {
            return;
        }
        if (!$this->verify_purchasing_token()) {
            return;
        }
        $data = $this->supplier_data();
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
        if (!$this->verify_purchasing_token()) {
            return;
        }
        $id = $this->input->post('id_pemasok');
        if (!$this->pemasok->get_by_id($id)) {
            return $this->error('Pemasok tidak ditemukan.', 404);
        }
        $data = $this->supplier_data();
        if ($data === false) {
            return;
        }
        $this->write('update', $data, $id);
    }

    public function ajax_delete($id)
    {
        if ($this->session->userdata('role') === 'purchasing') {
            return $this->error('Purchasing tidak memiliki akses untuk menghapus pemasok.', 403);
        }
        if (!$this->require_post()) {
            return;
        }
        if (!$this->pemasok->get_by_id($id)) {
            return $this->error('Pemasok tidak ditemukan.', 404);
        }
        $this->write('delete', null, $id);
    }

    private function supplier_data()
    {
        $fields = array('kode_pemasok', 'nama_pemasok', 'alamat', 'telepon', 'email', 'aktif');
        $data = array();
        foreach ($fields as $field) {
            $value = $this->input->post($field);
            if (!is_string($value)) {
                $this->error('Data pemasok tidak valid.', 400);
                return false;
            }

            $data[$field] = trim($value);
        }
        if ($data['kode_pemasok'] === '' || $data['nama_pemasok'] === ''
            || $this->text_length($data['kode_pemasok']) > 50
            || $this->text_length($data['nama_pemasok']) > 255
            || $this->text_length($data['telepon']) > 20
            || $this->text_length($data['email']) > 100
            || strlen($data['alamat']) > 65535
            || !in_array($data['aktif'], array('0', '1'), true)
            || ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
            $this->error('Periksa kode, nama, email, status, dan panjang data pemasok.', 400);
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

    private function verify_purchasing_token()
    {
        if ($this->session->userdata('role') !== 'purchasing') {
            return true;
        }
        $token = $this->input->post('pemasok_form_token');
        $expected = $this->session->userdata('pemasok_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            $this->error('Token formulir tidak valid. Muat ulang halaman.', 403);
            return false;
        }
        return true;
    }

    private function write($action, $data, $id = null)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if ($action === 'save') {
            $success = $this->pemasok->save($data);
        } elseif ($action === 'update') {
            $success = $this->pemasok->update($id, $data);
        } else {
            $success = $this->pemasok->delete_by_id($id);
        }
        $error = $success ? null : $this->db->error();
        $this->db->db_debug = $debug;
        if (!$success) {
            if ((int) $error['code'] === 1062) {
                return $this->error('Kode pemasok sudah digunakan.', 409);
            }
            if ((int) $error['code'] === 1451) {
                return $this->error('Pemasok digunakan dalam transaksi dan tidak dapat dihapus. Ubah status menjadi tidak aktif.', 409);
            }
            log_message('error', 'Gagal ' . $action . ' pemasok: ' . $error['code'] . ' ' . $error['message']);
            return $this->error('Gagal menyimpan perubahan pemasok.', 500);
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
