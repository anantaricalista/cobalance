<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_jurnal_umum extends CI_Model
{

    var $table = 'jurnal_transaksi';
    var $column_order = array(null, 'tanggal', 'deskripsi', 'referensi');
    var $column_search = array('tanggal', 'deskripsi', 'referensi');
    var $order = array('tanggal' => 'asc');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function _get_datatables_query()
    {
        $this->db->from($this->table);
        $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'));

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

    // Mengambil detail entri jurnal berdasarkan id_jurnal dengan join ke tabel akun
    public function get_detail_by_id($id)
    {
        $this->db->select('d.*, a.nama_akun, a.kode_akun');
        $this->db->from('detail_jurnal d');
        $this->db->join('akun a', 'd.id_akun = a.id_akun', 'left');
        $this->db->where('d.id_jurnal', $id);
        $query = $this->db->get();
        return $query->result();
    }
}
