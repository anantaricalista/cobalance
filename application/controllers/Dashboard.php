<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('M_dashboard', 'dashboard');
	}

	public function index()
	{
		$data['user'] = array(
			'username' => $this->session->userdata('username'),
			'nama_lengkap' => $this->session->userdata('nama_lengkap'),
			'role' => $this->session->userdata('role'),
			'nama_perusahaan' => $this->session->userdata('nama_perusahaan')
		);
		$data['today'] = date('Y-m-d');
		$data['summary'] = $this->dashboard->get_summary($data['today']);

		$this->load->view('template/header');
		$this->load->view('dashboard_view', $data);
		$this->load->view('template/footer');
	}
}
