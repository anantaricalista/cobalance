<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Aset_tetap extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('aset_tetap_service');
        $this->load->library('lampiran_service');
        $this->load->model('M_akun', 'akun');
    }

    public function index()
    {
        $asset = null;
        $transaction = null;
        if ($this->input->get('id') !== null) {
            try {
                $asset = $this->aset_tetap_service->get($this->input->get('id'));
            } catch (InvalidArgumentException $e) {
                show_404();
                return;
            }
            if (!$asset) {
                show_404();
                return;
            }
        }
        if ($this->input->get('trx') !== null) {
            try {
                $transaction = $this->aset_tetap_service->get_transaksi($this->input->get('trx'));
            } catch (InvalidArgumentException $e) {
                show_404();
                return;
            }
            if (!$transaction) {
                show_404();
                return;
            }
        }
        $company = $this->session->userdata('id_perusahaan');
        $data = array(
            'assets' => $this->aset_tetap_service->daftar(),
            'transactions' => $this->aset_tetap_service->daftar_transaksi(),
            'asset' => $asset,
            'transaction' => $transaction,
            'accounts_asset' => $this->akun->get_display_options(array(), array('1800')),
            'accounts_accumulated' => $this->akun->get_display_options(array(), array('1900')),
            'accounts_expense' => $this->akun->get_display_options(array('Beban'), array()),
            'cash_accounts' => $this->akun->get_display_options(array(), array('1100')),
            'suppliers' => $this->db->where('id_perusahaan', $company)->where('aktif', 1)
                ->order_by('nama_pemasok')->get('pemasok')->result(),
            'customers' => $this->db->where('id_perusahaan', $company)->where('aktif', 1)
                ->order_by('nama_pelanggan')->get('pelanggan')->result()
        );
        $this->load->view('template/header');
        $this->load->view('aset_tetap_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) {
            return;
        }
        $data = $this->posted_data();
        if ($data === false) {
            return;
        }
        $id = $this->input->post('id');
        $date = $this->input->post('tanggal');
        $remove = $this->input->post('hapus_lampiran') === '1';
        $this->perform(function () use ($id, $date, $data, $remove) {
            $file = $this->lampiran_service->dari_unggahan('lampiran');
            return $this->aset_tetap_service->simpan_perolehan($id, $date, $data, $file, $remove);
        });
    }

    public function daftarkan_lama()
    {
        if (!$this->post_only()) {
            return;
        }
        $data = $this->posted_data();
        if ($data === false) {
            return;
        }
        $this->perform(function () use ($data) {
            return $this->aset_tetap_service->daftarkan_lama($data);
        });
    }

    public function susut()
    {
        if (!$this->post_only()) {
            return;
        }
        $date = $this->input->post('periode');
        $this->perform(function () use ($date) {
            return $this->aset_tetap_service->buat_penyusutan($date);
        });
    }

    public function lepas()
    {
        if (!$this->post_only()) {
            return;
        }
        $data = $this->posted_data();
        if ($data === false) {
            return;
        }
        $id = $this->input->post('id');
        $date = $this->input->post('tanggal');
        $remove = $this->input->post('hapus_lampiran') === '1';
        $this->perform(function () use ($id, $date, $data, $remove) {
            $file = $this->lampiran_service->dari_unggahan('lampiran');
            return $this->aset_tetap_service->simpan_pelepasan($id, $date, $data, $file, $remove);
        });
    }

    public function posting()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            return $this->aset_tetap_service->posting($id);
        });
    }

    public function batalkan()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $date = $this->input->post('tanggal');
        $reason = $this->input->post('alasan');
        $this->perform(function () use ($id, $date, $reason) {
            return $this->aset_tetap_service->batalkan($id, $date, $reason);
        });
    }

    private function posted_data()
    {
        $raw = $this->input->post('data');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Data aset tidak valid.', 400);
            return false;
        }
        return $data;
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
            log_message('error', 'Aset tetap: ' . $e->getMessage());
            $this->error('Transaksi aset tetap gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
