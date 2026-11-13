<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penyesuaian_akuntansi extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('penyesuaian_akuntansi_service');
        $this->load->library('lampiran_service');
        $this->load->library('pemindahan_laba_service');
        $this->load->model('M_akun', 'akun');
    }

    public function pratinjau_pemindahan()
    {
        if (!$this->can_post_transactions) {
            return $this->error('Hanya Akuntansi atau Admin Perusahaan yang dapat memindahkan saldo laba.', 403);
        }
        $tahun = $this->input->get('tahun');
        $this->respond(function () use ($tahun) {
            return $this->pemindahan_laba_service->pratinjau($tahun);
        });
    }

    public function buat_pemindahan()
    {
        if (!$this->post_only()) return;
        if (!$this->can_post_transactions) {
            return $this->error('Hanya Akuntansi atau Admin Perusahaan yang dapat memindahkan saldo laba.', 403);
        }
        $tahun = $this->input->post('tahun');
        $this->respond(function () use ($tahun) {
            return $this->pemindahan_laba_service->generate($tahun);
        });
    }

    public function index()
    {
        $transaction = null;
        if ($this->input->get('id') !== null) {
            try {
                $transaction = $this->penyesuaian_akuntansi_service->get($this->input->get('id'));
            } catch (InvalidArgumentException $e) {
                show_404();
                return;
            }
            if (!$transaction) {
                show_404();
                return;
            }
        }
        $transfer_years = array();
        $transfer_message = '';
        if ($this->can_post_transactions) {
            if (!$this->pemindahan_laba_service->tabel_tersedia()) {
                $transfer_message = 'Jalankan migrasi application/sql/20261114_pemindahan_laba.sql untuk memakai fitur ini.';
            } else {
                try {
                    $transfer_years = $this->pemindahan_laba_service->tahun_tersedia();
                    if (!$transfer_years) {
                        $transfer_message = 'Belum ada transaksi Saldo Laba (Rugi) Berjalan pada tahun sebelum tahun berjalan.';
                    }
                } catch (DomainException $e) {
                    $transfer_message = $e->getMessage();
                }
            }
        }
        $data = array(
            'transaction' => $transaction,
            'transactions' => $this->penyesuaian_akuntansi_service->daftar(),
            'accounts' => $this->akun->get_display_options(),
            'transfer_years' => $transfer_years,
            'transfer_message' => $transfer_message
        );
        $this->load->view('template/header');
        $this->load->view('penyesuaian_akuntansi_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) return;
        $raw = $this->input->post('details');
        $details = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($details) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Rincian penyesuaian tidak valid.', 400);
        }
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $description = $this->input->post('deskripsi');
        $hapus = $this->input->post('hapus_lampiran') === '1';
        $this->perform(function () use ($id, $tanggal, $description, $details, $hapus) {
            return $this->penyesuaian_akuntansi_service->simpan($id, $tanggal, $description, $details,
                $this->lampiran_service->dari_unggahan('lampiran'), $hapus);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            return $this->penyesuaian_akuntansi_service->posting($id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) return;
        $id = $this->input->post('id');
        $tanggal = $this->input->post('tanggal');
        $alasan = $this->input->post('alasan');
        $this->perform(function () use ($id, $tanggal, $alasan) {
            return $this->penyesuaian_akuntansi_service->batalkan($id, $tanggal, $alasan);
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
            log_message('error', 'Penyesuaian akuntansi: ' . $e->getMessage());
            $this->error('Penyesuaian akuntansi gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function respond(callable $action)
    {
        try {
            $result = $action();
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(array('status' => true, 'data' => $result)));
        } catch (DomainException $e) {
            $this->error($e->getMessage(), 400);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            log_message('error', 'Pemindahan laba: ' . $e->getMessage());
            $this->error('Pemindahan saldo laba gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
