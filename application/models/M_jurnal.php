<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_jurnal extends CI_Model
{

    var $table = 'jurnal_transaksi';
    var $column_order = array(null, 'j.tanggal', 't.nomor_transaksi', 't.jenis_transaksi', null, null,
        't.status', 'j.deskripsi', null, null, null);
    var $column_search = array('j.tanggal', 'j.deskripsi', 'j.referensi', 't.nomor_transaksi',
        't.jenis_transaksi', 't.deskripsi', 't.status', 'p.nama_pelanggan', 's.nama_pemasok',
        'poster.nama_lengkap', 'poster.username', 'pembatal.nama_lengkap', 'pembatal.username');
    var $order = array('j.tanggal' => 'desc');

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function journal_query()
    {
        $this->db->select('j.*, tj.jenis_jurnal, t.nomor_transaksi, t.jenis_transaksi, t.status AS status_transaksi,
            t.deskripsi AS deskripsi_transaksi, t.id_pengguna_pembuat, t.id_pengguna_posting,
            t.id_pengguna_pembatal, t.waktu_posting, t.waktu_pembatalan, t.alasan_pembatalan,
            pembuat.nama_lengkap AS nama_pembuat, pembuat.username AS username_pembuat,
            poster.nama_lengkap AS nama_poster, poster.username AS username_poster,
            pembatal.nama_lengkap AS nama_pembatal, pembatal.username AS username_pembatal,
            p.nama_pelanggan, s.nama_pemasok')
            ->select('(SELECT SUM(d.debit) FROM detail_jurnal d WHERE d.id_jurnal = j.id_jurnal) AS total_debit', false)
            ->from('jurnal_transaksi j')
            ->join('transaksi_jurnal tj', 'tj.id_jurnal = j.id_jurnal AND tj.id_perusahaan = j.id_perusahaan', 'left')
            ->join('transaksi t', 't.id_transaksi = tj.id_transaksi AND t.id_perusahaan = tj.id_perusahaan', 'left')
            ->join('pengguna pembuat', 'pembuat.id_pengguna = t.id_pengguna_pembuat AND pembuat.id_perusahaan = t.id_perusahaan', 'left')
            ->join('pengguna poster', 'poster.id_pengguna = t.id_pengguna_posting AND poster.id_perusahaan = t.id_perusahaan', 'left')
            ->join('pengguna pembatal', 'pembatal.id_pengguna = t.id_pengguna_pembatal AND pembatal.id_perusahaan = t.id_perusahaan', 'left')
            ->join('pelanggan p', 'p.id_pelanggan = t.id_pelanggan AND p.id_perusahaan = t.id_perusahaan', 'left')
            ->join('pemasok s', 's.id_pemasok = t.id_pemasok AND s.id_perusahaan = t.id_perusahaan', 'left')
            ->where('j.id_perusahaan', $this->session->userdata('id_perusahaan'));
    }

    private function _get_datatables_query()
    {
        $this->journal_query();
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
        $this->db->order_by('j.id_jurnal', 'desc');
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
        $this->journal_query();
        $this->db->where('j.id_jurnal', $id);
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
        $company = $this->session->userdata('id_perusahaan');
        if (!$company || !isset($header['id_perusahaan']) || (string) $header['id_perusahaan'] !== (string) $company) {
            $this->db->trans_rollback();
            return false;
        }
        $locked = $this->db->query('SELECT id_perusahaan FROM perusahaan WHERE id_perusahaan = ? FOR UPDATE', array($company));
        if (!$locked || !$locked->row()) {
            $this->db->trans_rollback();
            return false;
        }
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
        if (!$this->get_by_id($id_jurnal) || $this->is_generated($id_jurnal)) {
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
        if (!$this->get_by_id($id) || $this->is_generated($id)) {
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

    public function is_generated($id)
    {
        return $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where('id_jurnal', $id)->count_all_results('transaksi_jurnal') > 0;
    }

    public function generated_ids($ids)
    {
        if (!$ids) {
            return array();
        }
        $rows = $this->db->select('id_jurnal')->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where_in('id_jurnal', $ids)->get('transaksi_jurnal')->result();
        $result = array();
        foreach ($rows as $row) {
            $result[$row->id_jurnal] = true;
        }
        return $result;
    }
}
