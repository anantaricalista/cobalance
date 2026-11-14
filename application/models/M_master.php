<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_master extends CI_Model
{
    private $table;
    private $id_field;
    private $fields;
    private $product_type;

    public function configure($table, $id_field, array $fields)
    {
        $this->table = $table;
        $this->id_field = $id_field;
        $this->fields = array_merge(array($id_field), $fields);
    }

    public function restrict_product_type($type)
    {
        $this->product_type = $type;
    }

    private function select_fields()
    {
        $this->db->select($this->fields);
        if ($this->table === 'produk') {
            $this->db->select('CASE WHEN gambar IS NULL THEN 0 ELSE 1 END AS memiliki_gambar', false);
        }
    }

    private function query($search, $columns)
    {
        $this->db->from($this->table);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        if ($this->product_type !== null) {
            $this->db->where('tipe_produk', $this->product_type);
        }
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
        $this->select_fields();
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
        $this->select_fields();
        if ($this->product_type !== null) {
            $this->db->where('tipe_produk', $this->product_type);
        }
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
