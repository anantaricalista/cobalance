<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_neraca extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
    }

    /**
     * Mengambil balance sheet untuk kategori tertentu (Aset, Kewajiban, atau Ekuitas)
     * dengan filter transaksi hingga end_date.
     * Pendekatan ini menggunakan Recursive CTE untuk mengumpulkan seluruh subtree dari akun parent.
     *
     * @param string $tipe Contoh: 'Aset', 'Kewajiban', 'Ekuitas'
     * @param string $end_date Format YYYY-MM-DD
     * @return array Hasil query sebagai array objek
     */
    public function get_balance_sheet_by_category($tipe, $end_date)
    {
        $id_perusahaan = $this->session->userdata('id_perusahaan');

        // Menggunakan Recursive CTE untuk mengumpulkan seluruh subtree dari akun parent.
        // Akun parent didefinisikan sebagai akun yang memiliki id_akun_induk IS NULL dan memenuhi tipe akun.
        $sql = "
            WITH RECURSIVE subtree (id_akun, root_id) AS (
                SELECT id_akun, id_akun
                FROM akun
                WHERE id_akun_induk IS NULL 
                  AND id_perusahaan = ? 
                  AND tipe_akun = ?
                UNION ALL
                SELECT a.id_akun, s.root_id
                FROM akun a
                JOIN subtree s ON a.id_akun_induk = s.id_akun
                WHERE a.id_perusahaan = ?
            )
            SELECT s.root_id AS id_akun, a.kode_akun, a.nama_akun,
                (
                    SELECT IFNULL(SUM(d.debit) - SUM(d.kredit), 0)
                    FROM detail_jurnal d
                    JOIN jurnal_transaksi j ON d.id_jurnal = j.id_jurnal
                    WHERE d.id_akun IN (
                        SELECT id_akun FROM subtree WHERE root_id = s.root_id
                    )
                      AND j.tanggal <= ?
                ) AS total_balance
            FROM subtree s
            JOIN akun a ON s.root_id = a.id_akun
            GROUP BY s.root_id, a.kode_akun, a.nama_akun
            ORDER BY a.kode_akun ASC
        ";

        $query = $this->db->query($sql, array($id_perusahaan, $tipe, $id_perusahaan, $end_date));
        return $query->result();
    }

    /**
     * Mengembalikan seluruh balance sheet untuk kategori aset, liabilitas, dan ekuitas.
     *
     * @param string $end_date Format YYYY-MM-DD
     * @return array Array dengan kunci: aset, liabilitas, dan ekuitas
     */
    public function get_all_balance_sheet($end_date)
    {
        return array(
            'aset' => $this->get_balance_sheet_by_category('Aset', $end_date),
            'liabilitas' => $this->get_balance_sheet_by_category('Kewajiban', $end_date),
            'ekuitas' => $this->get_balance_sheet_by_category('Ekuitas', $end_date)
        );
    }
}
