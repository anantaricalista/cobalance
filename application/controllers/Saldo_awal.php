<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Saldo_awal extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('saldo_awal_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $perusahaan = $this->session->userdata('id_perusahaan');
        $data = array(
            'opening' => $this->saldo_awal_service->get(),
            'accounts' => $this->akun->get_display_options(),
            'control_accounts' => $this->akun->get_selectable(array(), array('1200', '2100', '1700')),
            'equity_accounts' => $this->akun->get_display_options(array('Ekuitas')),
            'customers' => $this->db->where('id_perusahaan', $perusahaan)->order_by('nama_pelanggan')->get('pelanggan')->result(),
            'suppliers' => $this->db->where('id_perusahaan', $perusahaan)->order_by('nama_pemasok')->get('pemasok')->result(),
            'products' => $this->db->where('id_perusahaan', $perusahaan)->where('tipe_produk', 'barang')->order_by('nama_produk')->get('produk')->result()
        );
        $this->load->view('template/header');
        $this->load->view('saldo_awal_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) return;
        $raw = $this->input->post('data');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Rincian saldo awal tidak valid.', 400);
        }
        $tanggal = $this->input->post('tanggal');
        $this->perform(function () use ($tanggal, $data) {
            return $this->saldo_awal_service->simpan($tanggal, $data);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            return $this->saldo_awal_service->posting($id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $alasan = $this->input->post('alasan');
        $this->perform(function () use ($id, $tanggal, $alasan) {
            return $this->saldo_awal_service->batalkan($id, $tanggal, $alasan);
        });
    }

    private function post_only()
    {
        if ($this->input->method() !== 'post') {
            $this->error('Metode tidak diizinkan.', 405);
            return false;
        }
        return true;
    }

    private function perform(callable $action)
    {
        try {
            $action();
            $this->output->set_content_type('application/json')->set_output(json_encode(array('status' => true)));
        } catch (DomainException $e) {
            $this->error($e->getMessage(), 400);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            log_message('error', 'Saldo awal: ' . $e->getMessage());
            $this->error('Saldo awal gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
