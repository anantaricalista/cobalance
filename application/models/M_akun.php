<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_akun extends CI_Model
{

    var $table = 'akun';
    var $column_order = array(null, 'kode_akun', 'nama_akun', 'tipe_akun', 'id_akun_induk');
    var $column_search = array('kode_akun', 'nama_akun', 'tipe_akun');
    var $order = array('id_akun' => 'asc');

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
            if ($_POST['search']['value']) {
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

        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
        } else if (isset($this->order)) {
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

    public function save($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($where, $data)
    {
        $this->db->update($this->table, $data, $where);
        return $this->db->affected_rows();
    }

    public function delete_by_id($id)
    {
        $this->db->where('id_akun', $id);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->delete($this->table);
    }
}
