<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('role') !== 'kasir') {
            show_error('Halaman ini hanya tersedia untuk Kasir.', 403);
        }
        $this->load->library('penjualan_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $company = $this->session->userdata('id_perusahaan');
        $token = $this->session->userdata('pos_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('pos_form_token', $token);
        }
        $data = array(
            'token' => $token,
            'storage_key' => 'pos-pending-' . $company . '-' . $this->session->userdata('id_pengguna'),
            'customers' => $this->db->where('id_perusahaan', $company)->where('aktif', 1)->order_by('nama_pelanggan')->get('pelanggan')->result(),
            'taxes' => $this->db->where('id_perusahaan', $company)->where('aktif', 1)->order_by('kode_pajak')->get('pajak')->result(),
            'accounts' => $this->akun->get_display_options(array(), array('1100'))
        );
        $this->load->view('template/header');
        $this->load->view('kasir/pos', $data);
        $this->load->view('template/footer');
    }

    public function produk()
    {
        $search = $this->input->get('q');
        if ($search !== null && (!is_string($search) || strlen($search) > 100)) {
            return $this->error('Pencarian tidak valid.', 400);
        }
        $db = $this->db;
        $db->select('p.id_produk, p.kode_produk, p.nama_produk, p.tipe_produk, p.satuan, p.harga_jual, COALESCE(s.jumlah, 0) AS stok, CASE WHEN p.gambar IS NULL THEN 0 ELSE 1 END AS memiliki_gambar', false)
            ->from('produk p')->join('saldo_persediaan s', 's.id_perusahaan=p.id_perusahaan AND s.id_produk=p.id_produk', 'left')
            ->where('p.id_perusahaan', $this->session->userdata('id_perusahaan'))->where('p.aktif', 1);
        if ($search !== null && trim($search) !== '') {
            $db->group_start()->like('p.kode_produk', trim($search))->or_like('p.nama_produk', trim($search))->group_end();
            $db->order_by('p.kode_produk = ' . $db->escape(trim($search)), 'DESC', false);
        }
        $query = $db->order_by('p.nama_produk')->order_by('p.id_produk')->limit(61)->get();
        if (!$query) {
            return $this->error('Produk gagal dimuat.', 500);
        }
        $products = $query->result();
        $more = count($products) > 60;
        if ($more) {
            array_pop($products);
        }
        $this->json(array('status' => true, 'products' => $products, 'more' => $more));
    }

    public function bayar()
    {
        if ($this->input->method() !== 'post') {
            return $this->error('Metode tidak diizinkan.', 405);
        }
        $token = $this->input->post('pos_form_token');
        $expected = $this->session->userdata('pos_form_token');
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            return $this->error('Sesi formulir tidak valid. Muat ulang halaman.', 403);
        }
        $raw = $this->input->post('data');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Data kasir tidak valid.', 400);
        }
        try {
            $result = $this->penjualan_service->selesaikan_kasir($this->input->post('kunci'), $data);
            $this->json(array('status' => true, 'receipt_url' => site_url('pos/struk/' . (int) $result['id_transaksi'])));
        } catch (DomainException $e) {
            $this->error($e->getMessage(), 400);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            log_message('error', 'Kasir: ' . $e->getMessage());
            $this->error('Transaksi kasir gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    public function struk($id = null)
    {
        try {
            $transaction = $this->penjualan_service->get($id);
        } catch (InvalidArgumentException $e) {
            show_404();
            return;
        }
        $payment = $this->db->where('id_perusahaan', $this->session->userdata('id_perusahaan'))
            ->where('id_transaksi', $id)->get('pembayaran_kasir');
        if (!$payment) {
            show_error('Struk gagal dimuat.', 500);
        }
        if (!$transaction || !$payment->row()) {
            show_404();
            return;
        }
        $this->load->view('template/header');
        $this->load->view('kasir/struk', array('transaction' => $transaction, 'payment' => $payment->row()));
        $this->load->view('template/footer');
    }

    public function riwayat()
    {
        $page = $this->input->get('halaman');
        if ($page !== null && (!is_string($page) || !preg_match('/^[1-9][0-9]{0,5}$/D', $page))) {
            show_error('Halaman tidak valid.', 400);
        }
        $page = $page === null ? 1 : (int) $page;
        $query = $this->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status, t.total,
                p.nama_pelanggan, pk.metode
            FROM pembayaran_kasir pk
            JOIN transaksi t ON t.id_perusahaan=pk.id_perusahaan AND t.id_transaksi=pk.id_transaksi
            JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE pk.id_perusahaan=? ORDER BY t.id_transaksi DESC LIMIT 51 OFFSET " . (($page - 1) * 50),
            array($this->session->userdata('id_perusahaan')));
        if (!$query) {
            show_error('Riwayat kasir gagal dimuat.', 500);
        }
        $rows = $query->result();
        $more = count($rows) > 50;
        if ($more) {
            array_pop($rows);
        }
        $this->load->view('template/header');
        $this->load->view('kasir/riwayat', array('rows' => $rows, 'page' => $page, 'more' => $more));
        $this->load->view('template/footer');
    }

    private function json($data)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status);
        $this->json(array('status' => false, 'message' => $message));
    }
}
