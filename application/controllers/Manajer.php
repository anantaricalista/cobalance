<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manajer extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('role') !== 'manajer') {
            show_error('Halaman ini hanya tersedia untuk Manajer/Pemilik.', 403);
            exit;
        }
        if ($this->input->method() !== 'get') {
            show_error('Metode tidak diizinkan.', 405);
            exit;
        }
        $this->load->model('manajer/M_manajer', 'manajer');
        $this->load->model('keuangan/M_keuangan', 'keuangan');
    }

    public function index()
    {
        $today = date('Y-m-d');
        $this->load->model('M_laba_rugi', 'laba_rugi');
        $finance = $this->keuangan->ringkasan();
        $cash = '0.00';
        foreach ($finance['kas'] as $account) {
            $cash = jumlah_nominal($cash, $account->saldo);
        }
        $this->render('manajer/beranda', array(
            'nama' => $this->session->userdata('nama_lengkap'),
            'today' => $today, 'kas' => $cash, 'keuangan' => $finance,
            'laba' => $this->laba_rugi->get_income_statement(date('Y-m-01'), $today),
            'penjualan' => $this->manajer->penjualan(date('Y-m-01'), $today, 1),
            'stok' => $this->manajer->stok_minimum()
        ));
    }

    public function kas()
    {
        list($start, $end) = $this->periode();
        $page = $this->halaman();
        $this->render('manajer/kas', array('start' => $start, 'end' => $end, 'page' => $page,
            'data' => $this->manajer->kas($start, $end, $page)));
    }

    public function penjualan()
    {
        list($start, $end) = $this->periode();
        $page = $this->halaman();
        $this->render('manajer/penjualan', array('start' => $start, 'end' => $end, 'page' => $page,
            'data' => $this->manajer->penjualan($start, $end, $page)));
    }

    public function tagihan($type = '')
    {
        if (!in_array($type, array('utang', 'piutang'), true)) {
            show_404();
            return;
        }
        $page = $this->halaman();
        $data = $this->keuangan->daftar_tagihan($type, $page);
        $more = count($data['tagihan']) > 50;
        if ($more) {
            array_pop($data['tagihan']);
        }
        $this->render('manajer/tagihan', array('type' => $type, 'page' => $page, 'more' => $more,
            'today' => date('Y-m-d'), 'data' => $data));
    }

    private function periode()
    {
        $start = $this->input->get('mulai');
        $end = $this->input->get('akhir');
        $start = $start === null ? date('Y-m-01') : $start;
        $end = $end === null ? date('Y-m-d') : $end;
        foreach (array($start, $end) as $date) {
            if (!is_string($date) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
                || !($parsed = DateTime::createFromFormat('!Y-m-d', $date)) || $parsed->format('Y-m-d') !== $date) {
                show_error('Tanggal periode tidak valid.', 400);
                exit;
            }
        }
        if ($start > $end) {
            show_error('Tanggal mulai tidak boleh setelah tanggal akhir.', 400);
            exit;
        }
        return array($start, $end);
    }

    private function halaman()
    {
        $page = $this->input->get('halaman');
        if ($page !== null && (!is_string($page) || !preg_match('/^[1-9][0-9]{0,5}$/D', $page))) {
            show_error('Halaman tidak valid.', 400);
            exit;
        }
        return $page === null ? 1 : (int) $page;
    }

    private function render($view, $data)
    {
        $this->load->view('template/header');
        $this->load->view($view, $data);
        $this->load->view('template/footer');
    }
}
