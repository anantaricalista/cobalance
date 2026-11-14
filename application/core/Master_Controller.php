<?php
defined('BASEPATH') or exit('No direct script access allowed');

abstract class Master_Controller extends MY_Controller
{
    protected $config_master;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_master', 'master');
        $this->load->model('M_akun', 'akun');
        $this->master->configure($this->config_master['table'], $this->config_master['id'], array_keys($this->config_master['fields']));
    }

    public function index()
    {
        $options = array();
        foreach ($this->config_master['fields'] as $name => $field) {
            if ($field['type'] === 'account') {
                $types = isset($field['account_types']) ? $field['account_types'] : array();
                $roots = isset($field['account_roots']) ? $field['account_roots'] : array();
                $options[$name] = $this->akun->get_display_options($types, $roots);
            }
        }
        $setting_options = array();
        if (isset($this->config_master['account_settings'])) {
            foreach ($this->config_master['account_settings'] as $key => $root) {
                $setting_options[$key] = $this->akun->get_display_options(array(), array($root));
            }
        }
        $data = array('master' => $this->config_master, 'accounts' => $options, 'setting_options' => $setting_options, 'all_accounts' => $this->akun->get_all());
        $this->load->view('template/header');
        $this->load->view('master_view', $data);
        $this->load->view('template/footer');
    }

    public function ajax_list()
    {
        $search = $this->input->post('search');
        $search = is_array($search) && isset($search['value']) && is_string($search['value']) ? $search['value'] : '';
        $order = $this->input->post('order');
        $index = isset($order[0]['column']) ? (int) $order[0]['column'] - 1 : 0;
        $direction = isset($order[0]['dir']) && $order[0]['dir'] === 'desc' ? 'desc' : 'asc';
        $columns = $this->config_master['list'];
        $column = isset($columns[$index]) ? $columns[$index] : $columns[0];
        $start = max(0, (int) $this->input->post('start'));
        $length = (int) $this->input->post('length');
        $length = $length === -1 ? -1 : max(1, min(100, $length));
        $records = $this->master->listing($search, $this->config_master['search'], $column, $direction, $start, $length);
        $accounts = array();
        foreach ($this->akun->get_all() as $account) {
            $accounts[$account->id_akun] = $account->kode_akun . ' - ' . $account->nama_akun;
        }
        $rows = array();
        foreach ($records as $record) {
            $row = array(++$start);
            foreach ($columns as $field) {
                $value = $record->$field;
                if ($field === 'aktif') {
                    $value = $value ? 'Aktif' : 'Tidak Aktif';
                } elseif ($this->config_master['fields'][$field]['type'] === 'select' && isset($this->config_master['fields'][$field]['options'][$value])) {
                    $value = $this->config_master['fields'][$field]['options'][$value];
                } elseif ($this->config_master['fields'][$field]['type'] === 'account') {
                    $value = isset($accounts[$value]) ? $accounts[$value] : '-';
                } elseif ($this->config_master['table'] === 'produk' && $field === 'harga_jual') {
                    $value = format_nominal($value);
                } elseif ($this->config_master['table'] === 'produk' && $field === 'stok_minimum') {
                    $value = format_jumlah_indonesia($value);
                }
                $row[] = html_escape((string) $value);
            }
            $id = (int) $record->{$this->config_master['id']};
            if ($this->config_master['table'] === 'produk') {
                $row[] = $record->memiliki_gambar
                    ? '<img class="product-list-image" src="' . html_escape(site_url('produk/gambar/' . $id)) .
                        '" alt="Gambar produk" loading="lazy">'
                    : '<span class="text-muted">Tidak ada</span>';
            }
            $row[] = '<button type="button" class="btn btn-sm btn-primary" title="Edit" onclick="edit_master(' . $id . ')"><i class="glyphicon glyphicon-pencil"></i></button> '
                . '<button type="button" class="btn btn-sm btn-danger" title="Hapus" onclick="delete_master(' . $id . ')"><i class="glyphicon glyphicon-trash"></i></button>';
            $rows[] = $row;
        }
        $this->json(array(
            'draw' => (int) $this->input->post('draw'),
            'recordsTotal' => $this->master->count_filtered('', $this->config_master['search']),
            'recordsFiltered' => $this->master->count_filtered($search, $this->config_master['search']),
            'data' => $rows
        ));
    }

    public function ajax_edit($id)
    {
        $record = $this->master->find($id);
        if (!$record) {
            return $this->error('Data tidak ditemukan.', 404);
        }
        $this->json($record);
    }

    public function ajax_add()
    {
        if (!$this->require_post()) {
            return;
        }
        $data = $this->validated_data();
        if ($data === false) {
            return;
        }
        try {
            $data = $this->prepare_data($data);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
        $data['id_perusahaan'] = $this->session->userdata('id_perusahaan');
        $this->write('insert', null, $data);
    }

    public function ajax_update()
    {
        if (!$this->require_post()) {
            return;
        }
        $id = $this->input->post($this->config_master['id']);
        $record = $this->master->find($id);
        if (!$record) {
            return $this->error('Data tidak ditemukan.', 404);
        }
        $data = $this->validated_data($record);
        if ($data === false) {
            return;
        }
        if ($this->config_master['table'] === 'produk' && $record->tipe_produk !== $data['tipe_produk']
            && $this->master->product_used($id)) {
            return $this->error('Tipe produk yang sudah digunakan tidak dapat diubah.', 409);
        }
        try {
            $data = $this->prepare_data($data, $record);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
        $this->write('update', $id, $data);
    }

    protected function prepare_data(array $data, $record = null)
    {
        return $data;
    }

    public function ajax_delete($id)
    {
        if (!$this->require_post()) {
            return;
        }
        if (!$this->master->find($id)) {
            return $this->error('Data tidak ditemukan.', 404);
        }
        $this->write('delete', $id, null);
    }

    private function account_options($field)
    {
        $types = isset($field['account_types']) ? $field['account_types'] : array();
        $roots = isset($field['account_roots']) ? $field['account_roots'] : array();
        return $this->akun->get_selectable($types, $roots);
    }

    private function validated_data($record = null)
    {
        $data = array();
        foreach ($this->config_master['fields'] as $name => $field) {
            $value = $this->input->post($name);
            if (!is_string($value)) {
                $this->error('Data ' . $field['label'] . ' tidak valid.', 400);
                return false;
            }
            $value = trim($value);
            $required = !empty($field['required']);
            if ($value === '') {
                if ($required) {
                    $this->error($field['label'] . ' wajib diisi.', 400);
                    return false;
                }
                $data[$name] = null;
                continue;
            }
            if ($field['type'] === 'text') {
                $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
                if ($length > $field['maxlength'] || (isset($field['pattern']) && !preg_match($field['pattern'], $value))) {
                    $this->error($field['label'] . ' tidak valid.', 400);
                    return false;
                }
            } elseif ($field['type'] === 'select') {
                if (!array_key_exists($value, $field['options'])
                    && !($record && isset($record->$name) && (string) $record->$name === $value && $name === 'jenis_pengaturan')) {
                    $this->error($field['label'] . ' tidak valid.', 400);
                    return false;
                }
            } elseif ($field['type'] === 'decimal') {
                if (!preg_match($field['pattern'], $value) || (isset($field['max']) && (float) $value > $field['max'])) {
                    $this->error($field['label'] . ' tidak valid.', 400);
                    return false;
                }
            } elseif ($field['type'] === 'quantity') {
                if (!preg_match('/^\d{1,14}(?:,\d{1,4})?$/D', $value)) {
                    $this->error($field['label'] . ' tidak valid. Gunakan koma untuk desimal, maksimal empat angka.', 400);
                    return false;
                }
                $value = str_replace(',', '.', $value);
            } elseif ($field['type'] === 'account') {
                $allowed = $this->account_options($field);
                if ($this->config_master['table'] === 'pengaturan_akun' && isset($this->config_master['account_settings'][$data['jenis_pengaturan']])) {
                    $allowed = $this->akun->get_selectable(array(), array($this->config_master['account_settings'][$data['jenis_pengaturan']]));
                }
                $valid = false;
                foreach ($allowed as $account) {
                    if ((string) $account->id_akun === $value) {
                        $valid = true;
                        break;
                    }
                }
                $legacy = $record && isset($record->$name) && (string) $record->$name === $value
                    && ($this->config_master['table'] !== 'pengaturan_akun' || $record->jenis_pengaturan === $data['jenis_pengaturan']);
                if (!ctype_digit($value) || (!$valid && !($legacy && $this->akun->get_by_id($value)))) {
                    $this->error($field['label'] . ' tidak sesuai kategori atau bukan akun yang dapat dipilih.', 400);
                    return false;
                }
            }
            $data[$name] = $value;
        }
        return $data;
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->error('Metode permintaan tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function write($action, $id, $data)
    {
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if ($action === 'insert') {
            $success = $this->master->insert($data);
        } elseif ($action === 'update') {
            $success = $this->master->update($id, $data);
        } else {
            $success = $this->master->delete($id);
        }
        $error = $success ? null : $this->db->error();
        $this->db->db_debug = $debug;
        if (!$success) {
            if ((int) $error['code'] === 1062) {
                return $this->error('Kode atau jenis pengaturan sudah digunakan.', 409);
            }
            if ((int) $error['code'] === 1451 || (int) $error['code'] === 1452) {
                return $this->error('Data tidak dapat disimpan atau dihapus karena memiliki relasi dengan data lain.', 409);
            }
            log_message('error', 'Gagal ' . $action . ' ' . $this->config_master['table'] . ': ' . $error['code'] . ' ' . $error['message']);
            return $this->error('Gagal menyimpan perubahan.', 500);
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
