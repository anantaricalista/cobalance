<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Transaksi_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
    }

    public function buat_draft($jenis, $tanggal, $deskripsi, callable $simpan_detail)
    {
        $jenis_valid = array('penjualan', 'pembelian', 'penerimaan_piutang', 'pembayaran_utang',
            'kas_masuk', 'kas_keluar', 'transfer_kas', 'retur_penjualan', 'retur_pembelian',
            'saldo_awal', 'saldo_awal_piutang', 'saldo_awal_utang', 'saldo_awal_persediaan',
            'penyesuaian_akuntansi', 'penyesuaian_persediaan', 'setoran_modal', 'prive',
            'perolehan_aset', 'penyusutan_aset', 'pelepasan_aset');
        if (!in_array($jenis, $jenis_valid, true) || !$this->tanggal_valid($tanggal)
            || !is_string($deskripsi) || strlen($deskripsi) > 255) {
            throw new InvalidArgumentException('Data transaksi tidak valid.');
        }
        return $this->atomic(function ($perusahaan, $pengguna) use ($jenis, $tanggal, $deskripsi, $simpan_detail) {
            $db = $this->ci->db;
            $uuid = $db->query('SELECT UUID() AS nomor');
            if (!$uuid) {
                throw new RuntimeException('Gagal membuat nomor transaksi.');
            }
            $this->check($db->insert('transaksi', array(
                'id_perusahaan' => $perusahaan, 'jenis_transaksi' => $jenis,
                'nomor_transaksi' => 'DRAFT-' . $uuid->row()->nomor,
                'tanggal' => $tanggal, 'deskripsi' => $deskripsi,
                'status' => 'draft', 'id_pengguna_pembuat' => $pengguna
            )));
            $id = $db->insert_id();
            $nomor = 'TRX-' . str_pad((string) $id, 10, '0', STR_PAD_LEFT);
            $this->check($db->where('id_transaksi', $id)->where('id_perusahaan', $perusahaan)
                ->update('transaksi', array('nomor_transaksi' => $nomor)));
            $simpan_detail($id, $perusahaan, $db);
            return array('id_transaksi' => $id, 'nomor_transaksi' => $nomor);
        });
    }

    public function posting($id, callable $siapkan_jurnal)
    {
        return $this->atomic(function ($perusahaan, $pengguna) use ($id, $siapkan_jurnal) {
            $transaksi = $this->lock_transaksi($id, $perusahaan);
            if ($transaksi->status !== 'draft') {
                throw new DomainException('Hanya transaksi draft yang dapat diposting.');
            }
            $detail = $siapkan_jurnal($transaksi, $this->ci->db);
            $this->validasi_jurnal($detail, $perusahaan);
            $id_jurnal = $this->simpan_jurnal($transaksi, $transaksi->tanggal, $detail, 'posting');
            $this->check($this->ci->db->where('id_transaksi', $transaksi->id_transaksi)
                ->where('id_perusahaan', $perusahaan)->where('status', 'draft')
                ->update('transaksi', array('status' => 'diposting', 'id_pengguna_posting' => $pengguna,
                    'waktu_posting' => date('Y-m-d H:i:s'))));
            if ($this->ci->db->affected_rows() !== 1) {
                throw new RuntimeException('Status transaksi gagal diperbarui.');
            }
            return $id_jurnal;
        });
    }

    public function batalkan($id, $tanggal, $alasan, callable $batalkan_dampak)
    {
        if (!$this->tanggal_valid($tanggal) || !is_string($alasan) || trim($alasan) === '' || strlen($alasan) > 255) {
            throw new InvalidArgumentException('Tanggal dan alasan pembatalan wajib valid.');
        }
        return $this->atomic(function ($perusahaan, $pengguna) use ($id, $tanggal, $alasan, $batalkan_dampak) {
            $db = $this->ci->db;
            $transaksi = $this->lock_transaksi($id, $perusahaan);
            if ($transaksi->status !== 'diposting' || $tanggal < $transaksi->tanggal) {
                throw new DomainException('Transaksi tidak dapat dibatalkan pada tanggal tersebut.');
            }
            $query = $db->query("SELECT j.id_jurnal FROM transaksi_jurnal tj
                JOIN jurnal_transaksi j ON j.id_jurnal = tj.id_jurnal AND j.id_perusahaan = tj.id_perusahaan
                WHERE tj.id_perusahaan = ? AND tj.id_transaksi = ? AND tj.jenis_jurnal = 'posting' FOR UPDATE",
                array($perusahaan, $transaksi->id_transaksi));
            if (!$query || !$query->row()) {
                throw new RuntimeException('Jurnal posting tidak ditemukan.');
            }
            $rows = $db->where('id_jurnal', $query->row()->id_jurnal)->get('detail_jurnal');
            if (!$rows) {
                throw new RuntimeException('Detail jurnal tidak ditemukan.');
            }
            $detail = array();
            foreach ($rows->result() as $row) {
                $detail[] = array('id_akun' => $row->id_akun, 'debit' => $row->kredit, 'kredit' => $row->debit);
            }
            $this->validasi_jurnal($detail, $perusahaan, true);
            $batalkan_dampak($transaksi, $db);
            $id_jurnal = $this->simpan_jurnal($transaksi, $tanggal, $detail, 'pembalik');
            $this->check($db->where('id_transaksi', $transaksi->id_transaksi)
                ->where('id_perusahaan', $perusahaan)->where('status', 'diposting')
                ->update('transaksi', array('status' => 'dibatalkan', 'id_pengguna_pembatal' => $pengguna,
                    'waktu_pembatalan' => date('Y-m-d H:i:s'), 'alasan_pembatalan' => trim($alasan))));
            if ($db->affected_rows() !== 1) {
                throw new RuntimeException('Status pembatalan gagal diperbarui.');
            }
            return $id_jurnal;
        });
    }

    private function atomic(callable $operation)
    {
        $db = $this->ci->db;
        $perusahaan = $this->ci->session->userdata('id_perusahaan');
        $pengguna = $this->ci->session->userdata('id_pengguna');
        if (!$perusahaan || !$pengguna || PHP_INT_SIZE < 8) {
            throw new RuntimeException('Sesi perusahaan atau dukungan angka tidak valid.');
        }
        if ($db->trans_active()) {
            throw new RuntimeException('Layanan transaksi harus memulai transaksi database sendiri.');
        }
        $debug = $db->db_debug;
        $db->db_debug = false;
        if (!$db->trans_begin()) {
            $db->db_debug = $debug;
            throw new RuntimeException('Gagal memulai transaksi database.');
        }
        try {
            $user = $db->where('id_pengguna', $pengguna)->where('id_perusahaan', $perusahaan)->get('pengguna');
            if (!$user || !$user->row()) {
                throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
            }
            $result = $operation($perusahaan, $pengguna);
            if (!$db->trans_status() || !$db->trans_commit()) {
                throw new RuntimeException('Gagal menyimpan transaksi database.');
            }
            $db->db_debug = $debug;
            return $result;
        } catch (Throwable $e) {
            if ($db->trans_active() && !$db->trans_rollback()) {
                log_message('error', 'Rollback transaksi gagal.');
            }
            $db->db_debug = $debug;
            if (!($e instanceof DomainException) && !($e instanceof InvalidArgumentException)) {
                log_message('error', 'Transaksi gagal: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    private function lock_transaksi($id, $perusahaan)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id <= 0) {
            throw new InvalidArgumentException('ID transaksi tidak valid.');
        }
        $query = $this->ci->db->query('SELECT * FROM transaksi WHERE id_transaksi = ? AND id_perusahaan = ? FOR UPDATE', array($id, $perusahaan));
        if (!$query) {
            throw new RuntimeException('Gagal membaca transaksi.');
        }
        if (!$query->row()) {
            throw new DomainException('Transaksi tidak ditemukan.');
        }
        return $query->row();
    }

    private function validasi_jurnal($detail, $perusahaan, $pembalik = false)
    {
        if (!is_array($detail) || count($detail) < 2) {
            throw new DomainException('Jurnal harus memiliki minimal dua baris.');
        }
        $debit = 0;
        $kredit = 0;
        foreach ($detail as $row) {
            if (!is_array($row) || !isset($row['id_akun'], $row['debit'], $row['kredit'])
                || !is_scalar($row['id_akun']) || !ctype_digit((string) $row['id_akun'])) {
                throw new DomainException('Detail jurnal tidak valid.');
            }
            $d = $this->sen($row['debit']);
            $k = $this->sen($row['kredit']);
            if (($d === 0) === ($k === 0) || $debit > PHP_INT_MAX - $d || $kredit > PHP_INT_MAX - $k) {
                throw new DomainException('Nominal jurnal tidak valid.');
            }
            $query = $this->ci->db->query('SELECT id_akun FROM akun WHERE id_akun = ? AND id_perusahaan = ? FOR UPDATE', array($row['id_akun'], $perusahaan));
            if (!$query) {
                throw new RuntimeException('Gagal membaca akun.');
            }
            if (!$query->row()) {
                throw new DomainException('Akun jurnal tidak ditemukan pada perusahaan ini.');
            }
            if (!$pembalik) {
                $children = $this->ci->db->where('id_perusahaan', $perusahaan)->where('id_akun_induk', $row['id_akun'])->count_all_results('akun');
                if ($children > 0) {
                    throw new DomainException('Akun induk tidak dapat digunakan untuk jurnal.');
                }
            }
            $debit += $d;
            $kredit += $k;
        }
        if ($debit !== $kredit) {
            throw new DomainException('Total debit dan kredit harus seimbang.');
        }
    }

    private function sen($value)
    {
        if (!is_string($value) && !is_int($value)) {
            throw new DomainException('Nominal harus berupa angka desimal.');
        }
        $value = (string) $value;
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new DomainException('Nominal jurnal tidak valid.');
        }
        $parts = explode('.', $value);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }

    private function simpan_jurnal($transaksi, $tanggal, $detail, $jenis)
    {
        $db = $this->ci->db;
        $this->check($db->insert('jurnal_transaksi', array(
            'id_perusahaan' => $transaksi->id_perusahaan, 'tanggal' => $tanggal,
            'deskripsi' => ($jenis === 'pembalik' ? 'Pembatalan ' : '') . $transaksi->nomor_transaksi,
            'referensi' => $transaksi->nomor_transaksi
        )));
        $id_jurnal = $db->insert_id();
        foreach ($detail as $row) {
            $this->check($db->insert('detail_jurnal', array('id_jurnal' => $id_jurnal,
                'id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit'])));
        }
        $this->check($db->insert('transaksi_jurnal', array('id_perusahaan' => $transaksi->id_perusahaan,
            'id_transaksi' => $transaksi->id_transaksi, 'id_jurnal' => $id_jurnal, 'jenis_jurnal' => $jenis)));
        return $id_jurnal;
    }

    private function tanggal_valid($tanggal)
    {
        if (!is_string($tanggal)) {
            return false;
        }
        $date = DateTime::createFromFormat('!Y-m-d', $tanggal);
        return $date && $date->format('Y-m-d') === $tanggal;
    }

    private function check($success)
    {
        if (!$success) {
            throw new RuntimeException('Operasi database transaksi gagal.');
        }
    }
}
