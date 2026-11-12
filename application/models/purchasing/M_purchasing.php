<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_purchasing extends CI_Model
{
    private function company()
    {
        return (int) $this->session->userdata('id_perusahaan');
    }

    public function ringkasan()
    {
        $row = $this->db->query("SELECT
            COALESCE(SUM(status='draft'), 0) AS draft,
            COALESCE(SUM(status='diposting' AND tanggal>=?), 0) AS bulan_ini
            FROM transaksi WHERE id_perusahaan=? AND jenis_transaksi='pembelian'",
            array(date('Y-m-01'), $this->company()))->row();
        $supplier = $this->db->query('SELECT COUNT(*) AS jumlah FROM pemasok WHERE id_perusahaan=? AND aktif=1',
            array($this->company()))->row();
        return array('draft' => (int) $row->draft, 'bulan_ini' => (int) $row->bulan_ini,
            'pemasok' => (int) $supplier->jumlah);
    }

    // Harga jual sengaja tidak diambil untuk area purchasing.
    public function produk($search = '')
    {
        $sql = 'SELECT kode_produk, nama_produk, tipe_produk, satuan, aktif FROM produk WHERE id_perusahaan=?';
        $params = array($this->company());
        if ($search !== '') {
            $like = '%' . $this->db->escape_like_str($search) . '%';
            $sql .= ' AND (kode_produk LIKE ? OR nama_produk LIKE ?)';
            array_push($params, $like, $like);
        }
        return $this->db->query($sql . ' ORDER BY kode_produk, id_produk LIMIT 500', $params)->result();
    }
}
