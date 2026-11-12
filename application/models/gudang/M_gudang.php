<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_gudang extends CI_Model
{
    const HALAMAN = 100;

    private function company()
    {
        return (int) $this->session->userdata('id_perusahaan');
    }

    // Stok hanya jumlah; nilai rupiah sengaja tidak diambil untuk area gudang.
    public function stok($search = '')
    {
        $sql = "SELECT p.id_produk, p.kode_produk, p.nama_produk, p.satuan, p.aktif,
            COALESCE(s.jumlah, 0) AS jumlah
            FROM produk p LEFT JOIN saldo_persediaan s
                ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.tipe_produk='barang'";
        $params = array($this->company());
        if ($search !== '') {
            $like = '%' . $this->db->escape_like_str($search) . '%';
            $sql .= ' AND (p.kode_produk LIKE ? OR p.nama_produk LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        return $this->db->query($sql . ' ORDER BY p.kode_produk, p.id_produk', $params)->result();
    }

    public function ringkasan()
    {
        $row = $this->db->query("SELECT COUNT(*) AS total_produk,
            COALESCE(SUM(COALESCE(s.jumlah, 0) <= 0), 0) AS stok_habis
            FROM produk p LEFT JOIN saldo_persediaan s
                ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.tipe_produk='barang' AND p.aktif=1", array($this->company()))->row();
        $waiting = $this->db->query("SELECT COUNT(*) AS jumlah FROM usulan_opname
            WHERE id_perusahaan=? AND status='menunggu'", array($this->company()))->row();
        return array('total_produk' => (int) $row->total_produk, 'stok_habis' => (int) $row->stok_habis,
            'usulan_menunggu' => (int) $waiting->jumlah);
    }

    public function produk($id)
    {
        if ((!is_string($id) && !is_int($id)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $id)
            || (int) $id > 2147483647) {
            return null;
        }
        return $this->db->query("SELECT p.id_produk, p.kode_produk, p.nama_produk, p.satuan, p.aktif,
            COALESCE(s.jumlah, 0) AS jumlah
            FROM produk p LEFT JOIN saldo_persediaan s
                ON s.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk
            WHERE p.id_perusahaan=? AND p.id_produk=? AND p.tipe_produk='barang'",
            array($this->company(), (int) $id))->row();
    }

    public function kartu_stok($productId, $before = null)
    {
        $sql = "SELECT m.id_mutasi_persediaan, m.jenis_mutasi, m.jumlah, m.saldo_jumlah,
            t.nomor_transaksi, t.jenis_transaksi, t.tanggal, t.status
            FROM mutasi_persediaan m JOIN transaksi t
                ON t.id_perusahaan=m.id_perusahaan AND t.id_transaksi=m.id_transaksi
            WHERE m.id_perusahaan=? AND m.id_produk=?";
        $params = array($this->company(), (int) $productId);
        if ($before !== null) {
            $sql .= ' AND m.id_mutasi_persediaan<?';
            $params[] = (int) $before;
        }
        $rows = $this->db->query($sql . ' ORDER BY m.id_mutasi_persediaan DESC LIMIT ' . (self::HALAMAN + 1), $params)->result();
        $next = null;
        if (count($rows) > self::HALAMAN) {
            array_pop($rows);
            $next = $rows[count($rows) - 1]->id_mutasi_persediaan;
        }
        return array('mutasi' => $rows, 'berikutnya' => $next);
    }

    public function usulan()
    {
        return $this->db->query("SELECT u.id_usulan_opname, u.jumlah_sistem, u.jumlah_fisik, u.catatan, u.status,
            u.dibuat_pada, p.kode_produk, p.nama_produk, p.satuan, g.nama_lengkap
            FROM usulan_opname u
            JOIN produk p ON p.id_perusahaan=u.id_perusahaan AND p.id_produk=u.id_produk
            LEFT JOIN pengguna g ON g.id_perusahaan=u.id_perusahaan AND g.id_pengguna=u.id_pengguna
            WHERE u.id_perusahaan=? ORDER BY u.id_usulan_opname DESC LIMIT 200", array($this->company()))->result();
    }

    public function simpan_usulan($productId, $physical, $note)
    {
        if (!is_string($physical) || !preg_match('/^\d{1,14}(?:\.\d{1,4})?$/D', $physical)) {
            throw new InvalidArgumentException('Jumlah fisik tidak valid.');
        }
        $note = is_string($note) ? trim($note) : '';
        if ($note === '' || strlen($note) > 255) {
            throw new InvalidArgumentException('Catatan wajib diisi dan maksimal 255 byte.');
        }
        if (!is_string($productId) || !preg_match('/^[1-9][0-9]{0,9}$/D', $productId) || (int) $productId > 2147483647) {
            throw new InvalidArgumentException('Produk tidak valid.');
        }
        $company = $this->company();
        $this->db->trans_begin();
        $product = $this->db->query("SELECT id_produk FROM produk WHERE id_perusahaan=? AND id_produk=?
            AND tipe_produk='barang' AND aktif=1 FOR UPDATE", array($company, (int) $productId))->row();
        if (!$product) {
            $this->db->trans_rollback();
            throw new DomainException('Produk barang tidak ditemukan atau tidak aktif.');
        }
        $system = $this->db->query('SELECT jumlah FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=?',
            array($company, (int) $product->id_produk))->row();
        $ok = $this->db->insert('usulan_opname', array(
            'id_perusahaan' => $company, 'id_produk' => (int) $product->id_produk,
            'id_pengguna' => (int) $this->session->userdata('id_pengguna'),
            'jumlah_sistem' => $system ? $system->jumlah : '0',
            'jumlah_fisik' => $physical, 'catatan' => $note, 'dibuat_pada' => date('Y-m-d H:i:s')
        ));
        if (!$ok || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            throw new RuntimeException('Usulan opname gagal disimpan.');
        }
        $this->db->trans_commit();
    }
}
