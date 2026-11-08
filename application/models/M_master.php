<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_master extends CI_Model
{
    private $table;
    private $id_field;

    public function configure($table, $id_field)
    {
        $this->table = $table;
        $this->id_field = $id_field;
    }

    private function query($search, $columns)
    {
        $this->db->from($this->table);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        if ($search !== '') {
            $this->db->group_start();
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $this->db->like($column, $search);
                } else {
                    $this->db->or_like($column, $search);
                }
            }
            $this->db->group_end();
        }
    }

    public function listing($search, $columns, $order, $direction, $start, $length)
    {
        $this->query($search, $columns);
        $this->db->order_by($order, $direction);
        if ($length !== -1) {
            $this->db->limit($length, $start);
        }
        return $this->db->get()->result();
    }

    public function count_filtered($search, $columns)
    {
        $this->query($search, $columns);
        return $this->db->count_all_results();
    }

    public function find($id)
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where($this->id_field, $id)->get($this->table)->row();
    }

    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where($this->id_field, $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where($this->id_field, $id)->delete($this->table);
    }

    public function product_used($id)
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where('id_produk', $id)->count_all_results('detail_transaksi') > 0
            || $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
                ->where('id_produk', $id)->count_all_results('saldo_persediaan') > 0;
    }
}
