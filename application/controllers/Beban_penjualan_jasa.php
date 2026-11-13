<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Beban_penjualan_jasa extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('beban_penjualan_jasa_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $company = $this->session->userdata('id_perusahaan');
        $query = $this->db->where('id_perusahaan', $company)->where('aktif', 1)
            ->order_by('nama_pemasok')->get('pemasok');
        if (!$query) {
            log_message('error', 'Gagal membaca pemasok untuk beban penjualan jasa.');
            show_error('Gagal membaca pemasok.', 500);
            return;
        }
        $data = array(
            'sales' => $this->beban_penjualan_jasa_service->jasa_terjual(),
            'transactions' => $this->beban_penjualan_jasa_service->daftar(),
            'suppliers' => $query->result(),
            'expense_accounts' => $this->akun->get_display_options(array('Beban')),
            'cash_accounts' => $this->akun->get_display_options(array(), array('1100'))
        );
        $this->load->view('template/header');
        $this->load->view('beban_penjualan_jasa_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) return;
        $raw = $this->input->post('data');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Data beban jasa tidak valid.', 400);
        }
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $this->perform(function () use ($id, $tanggal, $data) {
            $this->beban_penjualan_jasa_service->simpan($id, $tanggal, $data);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            $this->beban_penjualan_jasa_service->posting($id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $alasan = $this->input->post('alasan');
        $this->perform(function () use ($id, $tanggal, $alasan) {
            $this->beban_penjualan_jasa_service->batalkan($id, $tanggal, $alasan);
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
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(array('status' => true)));
        } catch (DomainException $e) {
            $this->error($e->getMessage(), 400);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            log_message('error', 'Beban penjualan jasa: ' . $e->getMessage());
            $this->error('Beban penjualan jasa gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
