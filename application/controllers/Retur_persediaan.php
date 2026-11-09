<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_persediaan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('retur_persediaan_service');
        $this->load->library('lampiran_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $id = $this->input->get('id');
        $transaction = null;
        if ($id !== null) {
            try {
                $transaction = $this->retur_persediaan_service->get($id);
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
            'transactions' => $this->retur_persediaan_service->daftar(),
            'sources' => $this->retur_persediaan_service->sumber(),
            'accounts' => $this->akun->get_display_options(array('Pendapatan', 'Beban'), array())
        );
        $this->load->view('template/header');
        $this->load->view('retur_persediaan_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) return;
        $tanggal = $this->input->post('tanggal');
        $data = array('id_detail_asal' => $this->input->post('id_detail_asal'),
            'jumlah' => $this->input->post('jumlah'), 'id_akun_selisih' => $this->input->post('id_akun_selisih'),
            'deskripsi' => $this->input->post('deskripsi'));
        $this->perform(function () use ($tanggal, $data) {
            return $this->retur_persediaan_service->simpan($tanggal, $data,
                $this->lampiran_service->dari_unggahan('lampiran'));
        });
    }

    public function posting()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            $this->retur_persediaan_service->posting($id);
            return array('id_transaksi' => (int) $id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $alasan = $this->input->post('alasan');
        $this->perform(function () use ($id, $tanggal, $alasan) {
            $this->retur_persediaan_service->batalkan($id, $tanggal, $alasan);
            return array('id_transaksi' => (int) $id);
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
            $result = $action();
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(array('status' => true, 'result' => $result)));
        } catch (DomainException $e) {
            $this->error($e->getMessage(), 400);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            log_message('error', 'Retur persediaan: ' . $e->getMessage());
            $this->error('Retur gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
