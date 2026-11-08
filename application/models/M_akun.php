<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_akun extends CI_Model
{

    var $table = 'akun';
    var $column_order = array(null, 'a.kode_akun', 'a.nama_akun', 'a.tipe_akun', 'p.nama_akun');
    var $column_search = array('a.kode_akun', 'a.nama_akun', 'a.tipe_akun', 'p.nama_akun');
    var $order = array('a.id_akun' => 'asc');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    private function _get_datatables_query()
    {
        // Gunakan alias "a" untuk akun utama dan "p" untuk akun induk
        $this->db->select('a.*, p.nama_akun as nama_akun_induk');
        $this->db->from($this->table . ' a');
        // Left join dengan tabel akun untuk mendapatkan nama akun induk
        $this->db->join($this->table . ' p', 'a.id_akun_induk = p.id_akun', 'left');

        // Filter berdasarkan perusahaan yang login
        $this->db->where('a.id_perusahaan', $this->session->userdata('id_perusahaan'));

        $i = 0;
        foreach ($this->column_search as $item) {
            if (isset($_POST['search']['value']) && $_POST['search']['value'] !== '') {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }

                if (count($this->column_search) - 1 == $i)
                    $this->db->group_end();
            }
            $i++;
        }

        $column = isset($_POST['order'][0]['column']) ? (int) $_POST['order'][0]['column'] : -1;
        if (isset($this->column_order[$column]) && isset($_POST['order'][0]['dir'])) {
            $direction = strtolower($_POST['order'][0]['dir']) === 'desc' ? 'desc' : 'asc';
            $this->db->order_by($this->column_order[$column], $direction);
        } else {
            $order = $this->order;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }


    function get_datatables()
    {
        $this->_get_datatables_query();
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        $query = $this->db->get();
        return $query->result();
    }

    function count_filtered()
    {
        $this->_get_datatables_query();
        $query = $this->db->get();
        return $query->num_rows();
    }

    public function count_all()
    {
        $this->db->from($this->table);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        return $this->db->count_all_results();
    }

    public function get_by_id($id)
    {
        $this->db->from($this->table);
        $this->db->where('id_akun', $id);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        $query = $this->db->get();
        return $query->row();
    }

    public function get_all()
    {
        $this->db->order_by('kode_akun', 'asc');
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        return $this->db->get($this->table)->result();
    }

    public function get_selectable($types = array(), $roots = array())
    {
        $accounts = $this->get_all();
        $by_id = array();
        $parents = array();
        foreach ($accounts as $account) {
            $by_id[$account->id_akun] = $account;
            if ($account->id_akun_induk !== null && $account->id_akun_induk != 0) {
                $parents[$account->id_akun_induk] = true;
            }
        }
        $result = array();
        foreach ($accounts as $account) {
            if (isset($parents[$account->id_akun])) {
                continue;
            }
            if (!$types && !$roots) {
                $result[] = $account;
                continue;
            }
            if (in_array($account->tipe_akun, $types, true)) {
                $result[] = $account;
                continue;
            }
            $current = $account;
            $seen = array();
            while ($current && !isset($seen[$current->id_akun])) {
                $seen[$current->id_akun] = true;
                if (in_array($current->kode_akun, $roots, true)) {
                    $result[] = $account;
                    break;
                }
                $parent = $current->id_akun_induk;
                $current = isset($by_id[$parent]) ? $by_id[$parent] : null;
            }
        }
        return $result;
    }

    public function save($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($where, $data)
    {
        return $this->db->update($this->table, $data, $where);
    }

    public function is_in_use($id)
    {
        return $this->db->where('id_akun_induk', $id)->count_all_results($this->table) > 0
            || $this->db->where('id_akun', $id)->count_all_results('detail_jurnal') > 0;
    }

    public function delete_by_id($id)
    {
        $this->db->where('id_akun', $id);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        return $this->db->delete($this->table);
    }
}
