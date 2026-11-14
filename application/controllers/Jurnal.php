<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Jurnal extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_jurnal', 'jurnal');
        $this->load->helper('url');
    }

    // Tampilkan halaman dengan template header & footer
    public function index()
    {
        $this->load->view('template/header');
        $this->load->view('jurnal_view');
        $this->load->view('template/footer');
    }

    // Menampilkan data jurnal transaksi untuk DataTable (server side)
    public function ajax_list()
    {
        $list = $this->jurnal->get_datatables();
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $jurnal) {
            $no++;
            $info = $this->journal_info($jurnal);
            $row = array();
            $row[] = $no;
            $row[] = $jurnal->tanggal;
            $row[] = html_escape($info['nomor']);
            $row[] = html_escape($info['jenis_transaksi']);
            $row[] = $jurnal->total_debit === null ? '-' : 'Rp ' . html_escape(format_nominal($jurnal->total_debit));
            $row[] = html_escape($info['pelaku_jurnal']) .
                '<br><small class="text-muted">' . html_escape(format_tanggal_indonesia($info['waktu_jurnal'])) . '</small>';
            $row[] = '<div class="journal-actions">' .
                '<button type="button" class="btn btn-sm btn-primary view-journal" data-jurnal-id="' .
                (int) $jurnal->id_jurnal . '"><i class="glyphicon glyphicon-list-alt"></i> Detail</button>' .
                '<a class="btn btn-sm btn-default" target="_blank" href="' .
                site_url('jurnal/export_pdf/' . (int) $jurnal->id_jurnal) .
                '" title="Buka jurnal sebagai PDF"><i class="glyphicon glyphicon-file"></i> PDF</a></div>';
            $data[] = $row;
        }
        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->jurnal->count_all(),
            "recordsFiltered" => $this->jurnal->count_filtered(),
            "data" => $data,
        );
        echo json_encode($output);
    }

    // Endpoint edit lama dipertahankan agar klien lama menerima respons yang jelas.
    public function ajax_edit($id)
    {
        return $this->read_only();
    }

    public function ajax_detail($id)
    {
        if ($this->input->method() !== 'get' || !is_string($id) || !ctype_digit($id) || (int) $id < 1) {
            return $this->output->set_status_header(400)->set_content_type('application/json')
                ->set_output(json_encode(array('status' => false, 'message' => 'ID jurnal tidak valid.')));
        }
        $journal = $this->jurnal->get_by_id($id);
        if (!$journal) {
            return $this->output->set_status_header(404)->set_content_type('application/json')
                ->set_output(json_encode(array('status' => false, 'message' => 'Jurnal tidak ditemukan.')));
        }
        $details = array();
        foreach ($this->jurnal->get_details($id) as $detail) {
            $details[] = array(
                'kode_akun' => $detail->kode_akun,
                'nama_akun' => $detail->nama_akun,
                'debit' => $detail->debit,
                'kredit' => $detail->kredit
            );
        }
        return $this->output->set_content_type('application/json')->set_output(json_encode(array_merge($this->journal_info($journal), array(
            'tanggal' => $journal->tanggal,
            'deskripsi' => $journal->deskripsi,
            'referensi' => $journal->referensi,
            'details' => $details
        ))));
    }

    // Endpoint mutasi lama ditutup: jurnal harus berasal dari modul transaksi.
    public function ajax_add()
    {
        return $this->read_only();
    }

    public function ajax_update()
    {
        return $this->read_only();
    }

    public function ajax_delete($id)
    {
        return $this->read_only();
    }

    // Fitur Export PDF untuk data jurnal transaksi
    public function export_pdf($id)
    {
        // Ambil data jurnal (header dan detail)
        $jurnal = $this->jurnal->get_by_id($id);
        if (!$jurnal) {
            show_404();
            return;
        }
        $jurnal->detail = $this->jurnal->get_details($id);
        $data['jurnal'] = $jurnal;
        $data['info'] = $this->journal_info($jurnal);

        // Render view sebagai HTML
        $html = $this->load->view('jurnal_pdf', $data, true);

        // Gunakan library Pdf untuk membuat file PDF
        $this->load->library('pdf');
        $this->pdf->loadHtml($html);
        $this->pdf->setPaper('A4', 'portrait');
        $this->pdf->render();
        $this->pdf->stream("jurnal_{$id}.pdf", array("Attachment" => 0));
    }

    private function journal_info($journal)
    {
        $user = function ($id, $name, $username) {
            if (!$id) {
                return 'Tidak tercatat';
            }
            if (!$name && !$username) {
                return 'Pengguna ID ' . $id . ' (nama tidak tersedia)';
            }
            return $name ? $name . ($username ? ' (@' . $username . ')' : '') : '@' . $username;
        };
        $creator = $user($journal->id_pengguna_pembuat, $journal->nama_pembuat, $journal->username_pembuat);
        $poster = $user($journal->id_pengguna_posting, $journal->nama_poster, $journal->username_poster);
        $canceller = $user($journal->id_pengguna_pembatal, $journal->nama_pembatal, $journal->username_pembatal);
        $kind = $journal->jenis_jurnal === 'posting' ? 'Posting'
            : ($journal->jenis_jurnal === 'pembalik' ? 'Pembalik' : 'Tidak tercatat');
        return array(
            'nomor' => $journal->nomor_transaksi ?: ($journal->referensi ?: '-'),
            'jenis_transaksi' => $journal->jenis_transaksi ? ucwords(str_replace('_', ' ', $journal->jenis_transaksi)) : 'Tidak tercatat',
            'pihak' => $journal->nama_pelanggan ?: ($journal->nama_pemasok ?: '-'),
            'jenis_jurnal' => $kind,
            'status_transaksi' => $journal->status_transaksi ? ucfirst($journal->status_transaksi) : 'Tidak tercatat',
            'deskripsi_transaksi' => $journal->deskripsi_transaksi ?: '-',
            'pembuat' => $creator,
            'poster' => $poster,
            'waktu_posting' => $journal->waktu_posting ?: 'Tidak tercatat',
            'pembatal' => $canceller,
            'waktu_pembatalan' => $journal->waktu_pembatalan ?: 'Tidak tercatat',
            'alasan_pembatalan' => $journal->alasan_pembatalan ?: '-',
            'pelaku_jurnal' => $journal->jenis_jurnal === 'pembalik' ? $canceller
                : ($journal->jenis_jurnal === 'posting' ? $poster : 'Tidak tercatat'),
            'waktu_jurnal' => $journal->jenis_jurnal === 'pembalik'
                ? ($journal->waktu_pembatalan ?: 'Tidak tercatat')
                : ($journal->jenis_jurnal === 'posting' ? ($journal->waktu_posting ?: 'Tidak tercatat') : 'Tidak tercatat')
        );
    }

    private function read_only()
    {
        $this->output->set_status_header(410)->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status' => false,
                'message' => 'Jurnal Transaksi hanya untuk melihat dan mengekspor jurnal. Buat penyesuaian melalui modul Penyesuaian Akuntansi.'
            )));
    }
}
