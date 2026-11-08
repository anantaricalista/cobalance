<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_jurnal extends CI_Model
{

    var $table = 'jurnal_transaksi';
    var $column_order = array(null, 'tanggal', 'deskripsi', 'referensi');
    var $column_search = array('tanggal', 'deskripsi', 'referensi');
    var $order = array('tanggal' => 'desc');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function _get_datatables_query()
    {
        $this->db->from($this->table);
        // Filter berdasarkan perusahaan yang login
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
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
        // Filter berdasarkan perusahaan yang login
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        return $this->db->count_all_results();
    }

    public function get_by_id($id)
    {
        $this->db->from($this->table);
        $this->db->where('id_jurnal', $id);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        $query = $this->db->get();
        return $query->row();
    }

    public function get_details($id_jurnal)
    {
        $this->db->select('dj.*, a.kode_akun, a.nama_akun');
        $this->db->from('detail_jurnal dj');
        $this->db->join('jurnal_transaksi jt', 'jt.id_jurnal = dj.id_jurnal');
        $this->db->join('akun a', 'a.id_akun = dj.id_akun', 'left');
        $this->db->where('dj.id_jurnal', $id_jurnal);
        $this->db->where('jt.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $query = $this->db->get();
        return $query->result();
    }

    public function save($header, $details)
    {
        $this->db->trans_start();
        $this->db->insert($this->table, $header);
        $id_jurnal = $this->db->insert_id();
        if (!empty($details)) {
            foreach ($details as &$detail) {
                $detail['id_jurnal'] = $id_jurnal;
            }
            $this->db->insert_batch('detail_jurnal', $details);
        }
        $this->db->trans_complete();
        return $this->db->trans_status() ? $id_jurnal : false;
    }

    public function update($id_jurnal, $header, $details)
    {
        if (!$this->get_by_id($id_jurnal)) {
            return false;
        }
        $this->db->trans_start();
        $this->db->where('id_jurnal', $id_jurnal);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->update($this->table, $header);
        $this->db->where('id_jurnal', $id_jurnal);
        $this->db->delete('detail_jurnal');
        if (!empty($details)) {
            foreach ($details as &$detail) {
                $detail['id_jurnal'] = $id_jurnal;
            }
            $this->db->insert_batch('detail_jurnal', $details);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_by_id($id)
    {
        if (!$this->get_by_id($id)) {
            return false;
        }
        $this->db->trans_start();
        $this->db->where('id_jurnal', $id)->delete('detail_jurnal');
        $this->db->where('id_jurnal', $id)
            ->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->delete($this->table);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
