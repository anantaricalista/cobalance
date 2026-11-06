<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_laba_rugi extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_income_statement($start_date, $end_date)
    {
        // Ambil data pendapatan
        $this->db->select('a.kode_akun, a.nama_akun, (IFNULL(SUM(d.kredit),0) - IFNULL(SUM(d.debit),0)) as amount');
        $this->db->from('akun a');
        $this->db->join('detail_jurnal d', 'a.id_akun = d.id_akun', 'left');
        $this->db->join('jurnal_transaksi jt', 'd.id_jurnal = jt.id_jurnal', 'left');
        $this->db->where('a.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->where('a.tipe_akun', 'Pendapatan');
        $this->db->where('jt.tanggal >=', $start_date);
        $this->db->where('jt.tanggal <=', $end_date);
        $this->db->group_by('a.id_akun');
        $this->db->order_by('a.kode_akun', 'asc');
        $pendapatan = $this->db->get()->result();

        $total_pendapatan = 0;
        foreach ($pendapatan as $row) {
            $total_pendapatan += $row->amount;
        }

        // Ambil data beban
        $this->db->select('a.kode_akun, a.nama_akun, (IFNULL(SUM(d.debit),0) - IFNULL(SUM(d.kredit),0)) as amount');
        $this->db->from('akun a');
        $this->db->join('detail_jurnal d', 'a.id_akun = d.id_akun', 'left');
        $this->db->join('jurnal_transaksi jt', 'd.id_jurnal = jt.id_jurnal', 'left');
        $this->db->where('a.id_perusahaan', $this->session->userdata('id_perusahaan'));
        $this->db->where('a.tipe_akun', 'Beban');
        $this->db->where('jt.tanggal >=', $start_date);
        $this->db->where('jt.tanggal <=', $end_date);
        $this->db->group_by('a.id_akun');
        $this->db->order_by('a.kode_akun', 'asc');
        $beban = $this->db->get()->result();

        $total_beban = 0;
        foreach ($beban as $row) {
            $total_beban += $row->amount;
        }

        $laba_bersih = $total_pendapatan - $total_beban;

        return array(
            'pendapatan' => $pendapatan,
            'total_pendapatan' => $total_pendapatan,
            'beban' => $beban,
            'total_beban' => $total_beban,
            'laba_bersih' => $laba_bersih
        );
    }
}
