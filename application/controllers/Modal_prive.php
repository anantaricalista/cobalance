<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Modal_prive extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('modal_prive_service');
        $this->load->library('lampiran_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $id = $this->input->get('id');
        $transaction = null;
        if ($id !== null) {
            try {
                $transaction = $this->modal_prive_service->get($id);
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
            'transaction' => $transaction,
            'transactions' => $this->modal_prive_service->daftar(),
            'cash_accounts' => $this->akun->get_display_options(array(), array('1100')),
            'equity_accounts' => $this->akun->get_display_options(array('Ekuitas'), array())
        );
        $this->load->view('template/header');
        $this->load->view('modal_prive_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $data = array(
            'jenis' => $this->input->post('jenis'),
            'id_akun_kas' => $this->input->post('id_akun_kas'),
            'id_akun_modal' => $this->input->post('id_akun_modal'),
            'jumlah' => $this->input->post('jumlah'),
            'deskripsi' => $this->input->post('deskripsi')
        );
        $hapus = $this->input->post('hapus_lampiran') === '1';
        $this->perform(function () use ($id, $tanggal, $data, $hapus) {
            return $this->modal_prive_service->simpan($id, $tanggal, $data,
                $this->lampiran_service->dari_unggahan('lampiran'), $hapus);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            return $this->modal_prive_service->posting($id);
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
            return $this->modal_prive_service->batalkan($id, $tanggal, $alasan);
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
            log_message('error', 'Modal dan Prive: ' . $e->getMessage());
            $this->error('Transaksi Modal dan Prive gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
