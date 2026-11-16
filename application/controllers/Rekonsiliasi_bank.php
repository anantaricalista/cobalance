<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Rekonsiliasi_bank extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_kontrol_periode', 'kontrol');
    }

    public function index()
    {
        $period = $this->input->get('periode');
        $month = $this->input->get('bulan');
        if ($period === null && $month !== null) {
            if (!is_string($month) || !preg_match('/^[1-9]\d{3}-(?:0[1-9]|1[0-2])$/D', $month)) {
                show_error('Bulan rekonsiliasi tidak valid.', 400);
                return;
            }
            $period = $month . '-01';
        }
        if ($period === null) {
            $period = date('Y-m-01');
        }
        try {
            $this->kontrol->valid_period($period);
        } catch (InvalidArgumentException $e) {
            show_error($e->getMessage(), 400);
            return;
        }
        $accountId = $this->input->get('akun');
        if ($accountId !== null && (!is_string($accountId) || !preg_match('/^[1-9]\d{0,9}$/D', $accountId))) {
            show_error('Akun bank tidak valid.', 400);
            return;
        }
        $data = array(
            'period' => $period,
            'accounts' => array(),
            'reconciliation' => null,
            'account_id' => $accountId,
            'can_edit' => in_array($this->session->userdata('role'), array('admin', 'akuntansi', 'keuangan'), true),
            'period_closed' => false,
            'schema_ready' => $this->kontrol->schema_ready(),
            'message' => $this->session->flashdata('bank_message'),
            'message_type' => $this->session->flashdata('bank_message_type')
        );
        if ($data['schema_ready']) {
            try {
                $data['period_closed'] = $this->kontrol->is_closed($period);
                $data['accounts'] = $this->kontrol->get_bank_reconciliations($period);
                $validAccount = false;
                foreach ($data['accounts'] as $account) {
                    if ((string) $account->id_akun === (string) $data['account_id']) {
                        $validAccount = true;
                        break;
                    }
                }
                if (!$validAccount && $data['accounts']) {
                    $data['account_id'] = (string) $data['accounts'][0]->id_akun;
                }
                if ($data['account_id']) {
                    $data['reconciliation'] = $this->kontrol->bank_reconciliation($period, $data['account_id']);
                    if (!$data['reconciliation']) {
                        foreach ($data['accounts'] as $account) {
                            if ((string) $account->id_akun === (string) $data['account_id']) {
                                $data['reconciliation'] = (object) array(
                                    'id_akun' => $account->id_akun, 'periode' => $period,
                                    'kode_akun' => $account->kode_akun, 'nama_akun' => $account->nama_akun,
                                    'saldo_awal' => '0.00', 'saldo_akhir' => '0.00',
                                    'status' => 'draft', 'belum_tersimpan' => true,
                                    'mutasi' => array(), 'jurnal_tersedia' => array()
                                );
                                break;
                            }
                        }
                    } else {
                        foreach ($data['accounts'] as $account) {
                            if ((string) $account->id_akun === (string) $data['account_id']) {
                                $data['reconciliation']->kode_akun = $account->kode_akun;
                                $data['reconciliation']->nama_akun = $account->nama_akun;
                                break;
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                log_message('error', 'Rekonsiliasi bank: ' . $e->getMessage());
                $data['message'] = 'Data rekonsiliasi bank gagal dibaca. Periksa log aplikasi.';
                $data['message_type'] = 'danger';
            }
        }
        $this->load->view('template/header');
        $this->load->view('rekonsiliasi_bank_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only() || !$this->can_edit()) return;
        $period = $this->input->post('periode');
        $accountId = $this->input->post('id_akun');
        $opening = $this->input->post('saldo_awal');
        $closing = $this->input->post('saldo_akhir');
        $lines = $this->input->post('mutasi');
        $count = $this->input->post('jumlah_baris');
        $this->perform(function () use ($period, $accountId, $opening, $closing, $lines, $count) {
            if (($lines !== null && !is_array($lines))
                || !is_string($count) || !ctype_digit($count) || (int) $count > 200
                || (int) $count !== (is_array($lines) ? count($lines) : 0)) {
                throw new InvalidArgumentException('Daftar mutasi tidak lengkap. Batasi maksimal 200 baris dan periksa batas input server.');
            }
            $this->kontrol->save_bank_reconciliation($period, $accountId, $opening, $closing,
                is_array($lines) ? $lines : array());
        }, $period, $accountId);
    }

    public function cocokkan()
    {
        if (!$this->post_only() || !$this->can_edit()) return;
        $period = $this->input->post('periode');
        $lineId = $this->input->post('id_mutasi');
        $detailId = $this->input->post('id_detail');
        $accountId = $this->input->post('id_akun');
        $this->perform(function () use ($period, $lineId, $detailId) {
            $this->kontrol->match_bank_line($period, $lineId, $detailId);
        }, $period, $accountId);
    }

    public function lepas()
    {
        if (!$this->post_only() || !$this->can_edit()) return;
        $period = $this->input->post('periode');
        $lineId = $this->input->post('id_mutasi');
        $accountId = $this->input->post('id_akun');
        $this->perform(function () use ($period, $lineId) {
            $this->kontrol->unmatch_bank_line($period, $lineId);
        }, $period, $accountId);
    }

    public function selesaikan()
    {
        if (!$this->post_only() || !$this->can_edit()) return;
        $period = $this->input->post('periode');
        $accountId = $this->input->post('id_akun');
        $this->perform(function () use ($period, $accountId) {
            $this->kontrol->complete_bank_reconciliation($period, $accountId);
        }, $period, $accountId);
    }

    private function can_edit()
    {
        if (in_array($this->session->userdata('role'), array('admin', 'akuntansi', 'keuangan'), true)) {
            return true;
        }
        show_error('Role ini hanya dapat melihat hasil rekonsiliasi bank.', 403);
        return false;
    }

    private function perform(callable $action, $period, $accountId)
    {
        try {
            if (!$this->kontrol->schema_ready()) {
                throw new DomainException('Terapkan migrasi application/sql/20261116_pengendalian_periode_rekonsiliasi.sql terlebih dahulu.');
            }
            $action();
            $this->session->set_flashdata('bank_message', 'Perubahan berhasil disimpan.');
            $this->session->set_flashdata('bank_message_type', 'success');
        } catch (DomainException $e) {
            $this->session->set_flashdata('bank_message', $e->getMessage());
            $this->session->set_flashdata('bank_message_type', 'warning');
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('bank_message', $e->getMessage());
            $this->session->set_flashdata('bank_message_type', 'warning');
        } catch (Throwable $e) {
            log_message('error', 'Rekonsiliasi bank: ' . $e->getMessage());
            $this->session->set_flashdata('bank_message', 'Perubahan gagal diproses. Periksa log aplikasi.');
            $this->session->set_flashdata('bank_message_type', 'danger');
        }
        $period = is_string($period) && preg_match('/^[1-9]\d{3}-(?:0[1-9]|1[0-2])-01$/D', $period) ? $period : date('Y-m-01');
        $accountId = is_scalar($accountId) ? (string) $accountId : '';
        $query = '?periode=' . rawurlencode($period);
        if (preg_match('/^[1-9]\d{0,9}$/D', $accountId)) {
            $query .= '&akun=' . rawurlencode($accountId);
        }
        redirect('rekonsiliasi_bank' . $query);
    }

    private function post_only()
    {
        if ($this->input->method() !== 'post') {
            show_error('Metode tidak diizinkan.', 405);
            return false;
        }
        return true;
    }
}
