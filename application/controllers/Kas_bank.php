<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kas_bank extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('kas_bank_service');
        $this->load->library('lampiran_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $id = $this->input->get('id');
        $transaction = null;
        if ($id !== null) {
            try {
                $transaction = $this->kas_bank_service->get($id);
            } catch (InvalidArgumentException $e) {
                show_404();
                return;
            }
            if (!$transaction) {
                show_404();
                return;
            }
        }
        $data = array(
            'transactions' => $this->kas_bank_service->daftar(),
            'transaction' => $transaction,
            'types' => $this->kas_bank_service->jenis(),
            'cash_accounts' => $this->akun->get_display_options(array(), array('1100')),
            'all_accounts' => $this->akun->get_display_options(array(), array())
        );
        $this->load->view('template/header');
        $this->load->view('kas_bank_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) {
            return;
        }
        $raw = $this->input->post('data');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Rincian kas dan bank tidak valid.', 400);
        }
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $hapus = $this->input->post('hapus_lampiran') === '1';
        $this->perform(function () use ($id, $tanggal, $data, $hapus) {
            $lampiran = $this->lampiran_service->dari_unggahan('lampiran');
            return $this->kas_bank_service->simpan($id, $tanggal, $data, $lampiran, $hapus);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            return $this->kas_bank_service->posting($id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $alasan = $this->input->post('alasan');
        $this->perform(function () use ($id, $tanggal, $alasan) {
            return $this->kas_bank_service->batalkan($id, $tanggal, $alasan);
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
            log_message('error', 'Kas dan bank: ' . $e->getMessage());
            $this->error('Transaksi kas dan bank gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
