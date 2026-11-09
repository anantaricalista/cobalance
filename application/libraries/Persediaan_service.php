<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Persediaan_service
{
    const HALAMAN_MUTASI = 100;

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
    }

    public function saldo()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT p.id_produk, p.kode_produk, p.nama_produk, p.satuan, p.aktif,
            COALESCE(s.jumlah, 0) AS jumlah, COALESCE(s.nilai, 0) AS nilai,
            COALESCE(s.harga_pokok_rata_rata, 0) AS harga_pokok_rata_rata
            FROM produk p LEFT JOIN saldo_persediaan s
                ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.tipe_produk='barang'
            ORDER BY p.kode_produk, p.id_produk", array($company));
        $this->check($query);
        return $query->result();
    }

    public function riwayat($productId, $before = null)
    {
        $company = $this->company();
        $this->validate_id($productId, 'Produk');
        if ($before !== null) {
            $this->validate_id($before, 'Penanda riwayat');
        }
        $product = $this->ci->db->query("SELECT id_produk, kode_produk, nama_produk, satuan
            FROM produk WHERE id_perusahaan=? AND id_produk=? AND tipe_produk='barang'",
            array($company, $productId));
        $this->check($product);
        if (!$product->row()) {
            throw new DomainException('Produk barang tidak ditemukan.');
        }
        $sql = "SELECT m.id_mutasi_persediaan, m.jenis_mutasi, m.jumlah,
            m.nilai, m.saldo_jumlah, m.saldo_nilai, m.harga_pokok_rata_rata,
            t.id_transaksi, t.nomor_transaksi, t.jenis_transaksi, t.tanggal, t.status
            FROM mutasi_persediaan m JOIN transaksi t
                ON t.id_perusahaan=m.id_perusahaan AND t.id_transaksi=m.id_transaksi
            WHERE m.id_perusahaan=? AND m.id_produk=?";
        $params = array($company, $productId);
        if ($before !== null) {
            $sql .= ' AND m.id_mutasi_persediaan<?';
            $params[] = $before;
        }
        $sql .= ' ORDER BY m.id_mutasi_persediaan DESC LIMIT ' . (self::HALAMAN_MUTASI + 1);
        $moves = $this->ci->db->query($sql, $params);
        $this->check($moves);
        $rows = $moves->result();
        $next = null;
        if (count($rows) > self::HALAMAN_MUTASI) {
            array_pop($rows);
            $next = $rows[count($rows) - 1]->id_mutasi_persediaan;
        }
        return array('produk' => $product->row(), 'mutasi' => $rows, 'sebelum' => $before, 'berikutnya' => $next);
    }

    private function validate_id($id, $label)
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $id)
            || (int) $id > 2147483647) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $query = $this->ci->db->query('SELECT id_pengguna FROM pengguna WHERE id_perusahaan=? AND id_pengguna=?',
            array($company, $user));
        $this->check($query);
        if (!$query->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return $company;
    }

    private function check($result)
    {
        if (!$result) {
            throw new RuntimeException('Gagal membaca data persediaan.');
        }
    }
}
