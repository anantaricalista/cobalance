<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penyesuaian_persediaan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('penyesuaian_persediaan_service');
        $this->load->library('lampiran_service');
        $this->load->model('M_akun', 'akun');
        $this->load->model('M_usulan_opname', 'usulan');
    }

    public function index()
    {
        $id = $this->input->get('id');
        $transaction = null;
        if ($id !== null) {
            try {
                $transaction = $this->penyesuaian_persediaan_service->get($id);
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
        $token = $this->session->userdata('usulan_form_token');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set_userdata('usulan_form_token', $token);
        }
        $data = array(
            'transaction' => $transaction,
            'transactions' => $this->penyesuaian_persediaan_service->daftar(),
            'proposals' => $this->usulan->daftar(),
            'proposal_form_token' => $token,
            'products' => $this->db->select('id_produk, kode_produk, nama_produk')->where('id_perusahaan', $company)->where('tipe_produk', 'barang')
                ->where('aktif', 1)->order_by('kode_produk')->get('produk')->result(),
            'accounts' => $this->akun->get_display_options(array('Pendapatan', 'Beban'), array())
        );
        $this->load->view('template/header');
        $this->load->view('penyesuaian_persediaan_view', $data);
        $this->load->view('template/footer');
    }

    public function simpan()
    {
        if (!$this->post_only()) {
            return;
        }
        $data = array(
            'produk' => $this->input->post('produk'),
            'akun' => $this->input->post('akun'),
            'jenis' => $this->input->post('jenis'),
            'jumlah' => $this->input->post('jumlah'),
            'nilai' => $this->input->post('nilai'),
            'deskripsi' => $this->input->post('deskripsi'),
            'usulan' => $this->input->post('usulan')
        );
        $tanggal = $this->input->post('tanggal');
        $this->perform(function () use ($tanggal, $data) {
            return $this->penyesuaian_persediaan_service->simpan($tanggal, $data,
                $this->lampiran_service->dari_unggahan('lampiran'));
        });
    }

    public function posting()
    {
        if (!$this->post_only()) {
            return;
        }
        $id = $this->input->post('id');
        $this->perform(function () use ($id) {
            $this->penyesuaian_persediaan_service->posting($id);
            $this->session->set_flashdata('sukses', 'Penyesuaian berhasil diposting; stok dan jurnal telah diperbarui.');
            return array('id_transaksi' => (int) $id);
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
            $this->penyesuaian_persediaan_service->batalkan($id, $tanggal, $alasan);
            $this->session->set_flashdata('sukses', 'Penyesuaian berhasil dibatalkan.');
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
            log_message('error', 'Penyesuaian persediaan: ' . $e->getMessage());
            $this->error('Penyesuaian gagal diproses. Periksa log aplikasi.', 500);
        }
    }

    private function error($message, $status)
    {
        $this->output->set_status_header($status)->set_content_type('application/json')
            ->set_output(json_encode(array('status' => false, 'message' => $message)));
    }
}
