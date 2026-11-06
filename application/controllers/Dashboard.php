<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		// Load model untuk mengambil data summary
		$this->load->model('M_akun', 'akun');
		$this->load->model('M_jurnal', 'jurnal');
	}

	public function index()
	{
		// Ambil data user dari session
		$data['user'] = array(
			'username' => $this->session->userdata('username'),
			'nama_lengkap' => $this->session->userdata('nama_lengkap'),
			'role' => $this->session->userdata('role'),
			'nama_perusahaan' => $this->session->userdata('nama_perusahaan')
		);

		// Ambil total akun dan total jurnal
		$data['total_akun'] = $this->akun->count_all();
		$data['total_jurnal'] = $this->jurnal->count_all();

		$this->load->view('template/header');
		$this->load->view('dashboard_view', $data);
		$this->load->view('template/footer');
	}
}
