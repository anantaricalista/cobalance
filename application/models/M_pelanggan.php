<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_pelanggan extends CI_Model
{
    private $table = 'pelanggan';
    private $column_order = array(null, 'kode_pelanggan', 'nama_pelanggan', 'telepon', 'email', 'aktif');
    private $column_search = array('kode_pelanggan', 'nama_pelanggan', 'telepon', 'email', 'alamat');

    private function datatables_query($search)
    {
        $this->db->from($this->table);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        if ($search !== '') {
            $this->db->group_start();
            foreach ($this->column_search as $index => $column) {
                if ($index === 0) {
                    $this->db->like($column, $search);
                } else {
                    $this->db->or_like($column, $search);
                }
            }
            $this->db->group_end();
        }
    }

    public function get_datatables($search, $column, $direction, $start, $length)
    {
        $this->datatables_query($search);
        $order = isset($this->column_order[$column]) ? $this->column_order[$column] : 'kode_pelanggan';
        $this->db->order_by($order, $direction === 'desc' ? 'desc' : 'asc');
        if ($length !== -1) {
            $this->db->limit($length, $start);
        }
        return $this->db->get()->result();
    }

    public function count_filtered($search)
    {
        $this->datatables_query($search);
        return $this->db->count_all_results();
    }

    public function count_all()
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->count_all_results($this->table);
    }

    public function get_by_id($id)
    {
        return $this->db->where('id_pelanggan', $id)
            ->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->get($this->table)->row();
    }

    public function save($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('id_pelanggan', $id)
            ->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->update($this->table, $data);
    }

    public function delete_by_id($id)
    {
        return $this->db->where('id_pelanggan', $id)
            ->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->delete($this->table);
    }
}
