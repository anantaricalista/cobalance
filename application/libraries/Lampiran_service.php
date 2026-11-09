<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Lampiran bukti transaksi yang disimpan langsung di database (kolom transaksi.lampiran, LONGBLOB).
 * Kolom lampiran tidak boleh ikut dalam SELECT daftar atau JSON; gunakan KOLOM_TRANSAKSI.
 */
class Lampiran_service
{
    const MAKS_BYTE = 5242880;
    const KOLOM_TRANSAKSI = 't.id_transaksi, t.id_perusahaan, t.jenis_transaksi, t.nomor_transaksi, t.tanggal,
        t.jatuh_tempo, t.cara_pembayaran, t.status, t.id_pelanggan, t.id_pemasok, t.id_akun_kas,
        t.id_transaksi_asal, t.referensi, t.deskripsi, t.total_sebelum_pajak, t.total_pajak, t.total,
        t.alasan_pembatalan, t.lampiran_nama, t.lampiran_tipe';

    private $ci;
    private $allowed = array('application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png');

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
    }

    /** Mengembalikan null bila tidak ada file yang dipilih. */
    public function dari_unggahan($field)
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || !isset($_FILES[$field]['error'])
            || is_array($_FILES[$field]['error'])) {
            return null;
        }
        $file = $_FILES[$field];
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('Ukuran lampiran melebihi batas 5 MB.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Lampiran gagal diunggah.');
        }
        if ($file['size'] < 1 || $file['size'] > self::MAKS_BYTE) {
            throw new InvalidArgumentException('Lampiran harus berukuran 1 byte sampai 5 MB.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($file['tmp_name']);
        if (!isset($this->allowed[$type])) {
            throw new InvalidArgumentException('Lampiran harus berupa PDF, JPG, atau PNG.');
        }
        $content = file_get_contents($file['tmp_name']);
        if ($content === false || strlen($content) !== (int) $file['size']) {
            throw new InvalidArgumentException('Lampiran gagal dibaca.');
        }
        return array('isi' => $content, 'nama' => $this->name($file['name'], $type), 'tipe' => $type);
    }

    /** Harus dipanggil di dalam transaksi database layanan transaksi. */
    public function simpan($company, $id, $lampiran, $hapus)
    {
        $db = $this->ci->db;
        if ($lampiran !== null) {
            $data = array('lampiran' => $lampiran['isi'], 'lampiran_nama' => $lampiran['nama'],
                'lampiran_tipe' => $lampiran['tipe']);
        } elseif ($hapus) {
            $data = array('lampiran' => null, 'lampiran_nama' => null, 'lampiran_tipe' => null);
        } else {
            return;
        }
        if (!$db->where('id_perusahaan', $company)->where('id_transaksi', $id)->where('status', 'draft')
            ->update('transaksi', $data)) {
            throw new RuntimeException('Lampiran gagal disimpan.');
        }
    }

    public function ambil($id)
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1 || !$company) {
            return null;
        }
        $query = $this->ci->db->query('SELECT lampiran, lampiran_nama, lampiran_tipe FROM transaksi
            WHERE id_perusahaan=? AND id_transaksi=? AND lampiran IS NOT NULL', array($company, $id));
        if (!$query) {
            throw new RuntimeException('Gagal membaca lampiran.');
        }
        return $query->row();
    }

    private function name($original, $type)
    {
        $name = trim((string) preg_replace('/[^\p{L}\p{N}._ -]/u', '_', basename((string) $original)));
        $extension = $this->allowed[$type];
        if ($name === '' || $name === '.' || strlen($name) > 200) {
            $name = 'lampiran.' . $extension;
        }
        return $name;
    }
}
