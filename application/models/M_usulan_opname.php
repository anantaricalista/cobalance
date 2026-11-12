<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_usulan_opname extends CI_Model
{
    private function company()
    {
        return (int) $this->session->userdata('id_perusahaan');
    }

    public function daftar()
    {
        return $this->db->query("SELECT u.id_usulan_opname, u.id_produk, u.jumlah_sistem, u.jumlah_fisik, u.catatan,
            u.status, u.dibuat_pada, p.kode_produk, p.nama_produk, p.satuan, g.nama_lengkap,
            COALESCE(s.jumlah, 0) AS jumlah_sekarang
            FROM usulan_opname u
            JOIN produk p ON p.id_perusahaan=u.id_perusahaan AND p.id_produk=u.id_produk
            LEFT JOIN pengguna g ON g.id_perusahaan=u.id_perusahaan AND g.id_pengguna=u.id_pengguna
            LEFT JOIN saldo_persediaan s ON s.id_perusahaan=u.id_perusahaan AND s.id_produk=u.id_produk
            WHERE u.id_perusahaan=? ORDER BY (u.status='menunggu') DESC, u.id_usulan_opname DESC LIMIT 200",
            array($this->company()))->result();
    }

    // Hanya usulan berstatus menunggu yang dapat berpindah status.
    public function ubah_status($id, $status)
    {
        if (!is_string($id) || !preg_match('/^[1-9][0-9]{0,9}$/D', $id) || !in_array($status, array('diproses', 'ditolak'), true)) {
            return false;
        }
        $this->db->query("UPDATE usulan_opname SET status=? WHERE id_perusahaan=? AND id_usulan_opname=? AND status='menunggu'",
            array($status, $this->company(), (int) $id));
        return $this->db->affected_rows() === 1;
    }
}
