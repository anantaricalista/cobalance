<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kontrol_periode extends MY_Controller
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
                show_error('Bulan pemeriksaan tidak valid.', 400);
                return;
            }
            $period = $month . '-01';
        }
        if ($period === null) {
            $period = date('Y-m-01');
        }
        try {
            $date = $this->kontrol->valid_period($period);
        } catch (InvalidArgumentException $e) {
            show_error($e->getMessage(), 400);
            return;
        }
        $year = $this->input->get('tahun');
        if ($year !== null && (!is_string($year) || !preg_match('/^[1-9]\d{3}$/D', $year))) {
            show_error('Tahun pemeriksaan tidak valid.', 400);
            return;
        }
        $year = is_string($year) && preg_match('/^[1-9]\d{3}$/D', $year) ? (int) $year : (int) $date->format('Y');
        $data = array(
            'period' => $period,
            'year' => $year,
            'is_admin' => $this->session->userdata('role') === 'admin',
            'can_manage' => $this->can_post_transactions,
            'schema_ready' => $this->kontrol->schema_ready(),
            'message' => $this->session->flashdata('kontrol_message'),
            'message_type' => $this->session->flashdata('kontrol_message_type')
        );
        $data['periods'] = array();
        $data['cash_accounts'] = array();
        $data['checks'] = null;
        $data['latest_closed'] = null;
        $data['selected_closed'] = false;
        $data['history'] = array();
        if ($data['schema_ready']) {
            try {
                $data['periods'] = $this->kontrol->periods($year);
                $data['cash_accounts'] = $this->kontrol->cash_accounts();
                $data['checks'] = $this->kontrol->checks($period);
                $data['latest_closed'] = $this->kontrol->latest_closed();
                $data['history'] = $this->kontrol->history($period);
                $data['selected_closed'] = $this->kontrol->is_closed($period);
            } catch (Throwable $e) {
                log_message('error', 'Kontrol periode: ' . $e->getMessage());
                $data['checks'] = null;
                $data['message'] = 'Data kontrol periode gagal dibaca. Periksa log aplikasi.';
                $data['message_type'] = 'danger';
            }
        }
        $this->load->view('template/header');
        $this->load->view('kontrol_periode_view', $data);
        $this->load->view('template/footer');
    }

    public function klasifikasikan_akun()
    {
        if (!$this->post_only()) return;
        if (!$this->can_post_transactions) {
            return show_error('Hanya Akuntansi atau Admin Perusahaan yang dapat mengklasifikasikan akun kas/bank.', 403);
        }
        $id = $this->input->post('id_akun');
        $type = $this->input->post('jenis');
        $this->perform(function () use ($id, $type) {
            $this->kontrol->set_cash_account($id, $type, true);
        });
    }

    public function tutup()
    {
        if (!$this->post_only()) return;
        if ($this->session->userdata('role') !== 'admin') {
            return show_error('Hanya Admin Perusahaan yang dapat menutup periode.', 403);
        }
        $period = $this->input->post('periode');
        $note = $this->input->post('catatan');
        $this->perform(function () use ($period, $note) {
            $this->kontrol->close($period, $note);
        }, $period);
    }

    public function buka_kembali()
    {
        if (!$this->post_only()) return;
        if ($this->session->userdata('role') !== 'admin') {
            return show_error('Hanya Admin Perusahaan yang dapat membuka kembali periode.', 403);
        }
        $period = $this->input->post('periode');
        $reason = $this->input->post('alasan');
        $this->perform(function () use ($period, $reason) {
            $this->kontrol->reopen($period, $reason);
        }, $period);
    }

    private function perform(callable $action, $period = null)
    {
        try {
            if (!$this->kontrol->schema_ready()) {
                throw new DomainException('Terapkan migrasi application/sql/20261116_pengendalian_periode_rekonsiliasi.sql terlebih dahulu.');
            }
            $action();
            $this->session->set_flashdata('kontrol_message', 'Perubahan berhasil disimpan.');
            $this->session->set_flashdata('kontrol_message_type', 'success');
        } catch (DomainException $e) {
            $this->session->set_flashdata('kontrol_message', $e->getMessage());
            $this->session->set_flashdata('kontrol_message_type', 'warning');
        } catch (InvalidArgumentException $e) {
            $this->session->set_flashdata('kontrol_message', $e->getMessage());
            $this->session->set_flashdata('kontrol_message_type', 'warning');
        } catch (Throwable $e) {
            log_message('error', 'Kontrol periode: ' . $e->getMessage());
            $this->session->set_flashdata('kontrol_message', 'Perubahan gagal diproses. Periksa log aplikasi.');
            $this->session->set_flashdata('kontrol_message_type', 'danger');
        }
        $query = is_string($period) && preg_match('/^[1-9]\d{3}-(?:0[1-9]|1[0-2])-01$/D', $period)
            ? '?periode=' . rawurlencode($period) : '';
        redirect('kontrol_periode' . $query);
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
