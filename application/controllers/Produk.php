<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/Master_Controller.php';

class Produk extends Master_Controller
{
    const MAKS_GAMBAR = 2097152;

    protected $config_master = array(
        'table' => 'produk', 'id' => 'id_produk', 'title' => 'Produk dan Jasa', 'route' => 'produk',
        'search' => array('kode_produk', 'nama_produk', 'tipe_produk', 'satuan'),
        'list' => array('kode_produk', 'nama_produk', 'tipe_produk', 'satuan', 'harga_jual', 'aktif'),
        'fields' => array(
            'kode_produk' => array('label' => 'Kode', 'type' => 'text', 'maxlength' => 50, 'required' => true),
            'nama_produk' => array('label' => 'Nama', 'type' => 'text', 'maxlength' => 255, 'required' => true),
            'tipe_produk' => array('label' => 'Tipe', 'type' => 'select', 'options' => array('barang' => 'Barang', 'jasa' => 'Jasa'), 'required' => true),
            'satuan' => array('label' => 'Satuan', 'type' => 'text', 'maxlength' => 30, 'required' => true),
            'harga_jual' => array('label' => 'Harga Jual', 'type' => 'decimal', 'pattern' => '/^\d{1,13}(\.\d{1,2})?$/D', 'step' => '0.01', 'required' => true),
            'id_akun_pendapatan' => array('label' => 'Akun Pendapatan', 'type' => 'account', 'account_types' => array('Pendapatan')),
            'id_akun_pembelian' => array('label' => 'Akun Pembelian / Beban', 'type' => 'account', 'account_types' => array('Beban'), 'account_roots' => array('1700')),
            'id_akun_persediaan' => array('label' => 'Akun Persediaan', 'type' => 'account', 'account_roots' => array('1700')),
            'id_akun_beban_pokok' => array('label' => 'Akun Beban Pokok', 'type' => 'account', 'account_roots' => array('5100')),
            'aktif' => array('label' => 'Status', 'type' => 'select', 'options' => array('1' => 'Aktif', '0' => 'Tidak Aktif'), 'required' => true)
        )
    );

    protected function prepare_data(array $data)
    {
        $remove = $this->input->post('hapus_gambar');
        if ($remove !== null && $remove !== '1') {
            throw new InvalidArgumentException('Pilihan hapus gambar tidak valid.');
        }
        if (!isset($_FILES['gambar_produk'])) {
            $file = null;
        } elseif (!is_array($_FILES['gambar_produk']) || !isset($_FILES['gambar_produk']['error'])
            || is_array($_FILES['gambar_produk']['error'])) {
            throw new InvalidArgumentException('Unggahan gambar tidak valid.');
        } else {
            $file = $_FILES['gambar_produk'];
        }

        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($remove === '1') {
                throw new InvalidArgumentException('Pilih gambar baru atau hapus gambar, bukan keduanya.');
            }
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                throw new InvalidArgumentException('Ukuran gambar melebihi batas unggahan server.');
            }
            if ($file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name'], $file['size'])
                || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new InvalidArgumentException('Gambar gagal diunggah.');
            }
            if (!is_int($file['size']) || $file['size'] < 1 || $file['size'] > self::MAKS_GAMBAR) {
                throw new InvalidArgumentException('Gambar harus berukuran maksimal 2 MB.');
            }
            $types = array('image/jpeg', 'image/png', 'image/webp');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $dimensions = @getimagesize($file['tmp_name']);
            if (!in_array($mime, $types, true) || !$dimensions || $dimensions['mime'] !== $mime) {
                throw new InvalidArgumentException('Gambar harus berupa JPG, PNG, atau WebP yang valid.');
            }
            if ($dimensions[0] > 4096 || $dimensions[1] > 4096) {
                throw new InvalidArgumentException('Dimensi gambar maksimal 4096 x 4096 piksel.');
            }
            $content = file_get_contents($file['tmp_name']);
            if ($content === false || strlen($content) !== (int) $file['size']) {
                throw new InvalidArgumentException('Gambar gagal dibaca.');
            }
            $data['gambar'] = $content;
            $data['gambar_tipe'] = $mime;
        } elseif ($remove === '1') {
            $data['gambar'] = null;
            $data['gambar_tipe'] = null;
        }
        return $data;
    }

    public function gambar($id)
    {
        if ($this->input->method() !== 'get' || !is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            show_404();
            return;
        }
        $query = $this->db->select('gambar, gambar_tipe')->where('id_produk', $id)
            ->where('id_perusahaan', $this->session->userdata('id_perusahaan'))->get('produk');
        if (!$query) {
            log_message('error', 'Gagal membaca gambar produk.');
            show_error('Gagal membaca gambar produk.', 500);
            return;
        }
        $product = $query->row();
        if (!$product || $product->gambar === null) {
            show_404();
            return;
        }
        if (!in_array($product->gambar_tipe, array('image/jpeg', 'image/png', 'image/webp'), true)) {
            log_message('error', 'Tipe gambar produk tidak valid untuk ID ' . (int) $id);
            show_error('Tipe gambar produk tidak valid.', 500);
            return;
        }
        $this->output->set_content_type($product->gambar_tipe)
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Cache-Control: private, no-store')
            ->set_output($product->gambar);
    }
}
