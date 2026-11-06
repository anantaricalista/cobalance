<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// Sertakan file utama TCPDF
require_once(APPPATH . 'third_party/tcpdf/tcpdf.php');

class Pdf
{
    protected $tcpdf;
    protected $html;

    public function __construct()
    {
        // Inisialisasi TCPDF dengan default:
        // Orientation: Portrait (P), Unit: mm, Format: A4, Unicode: true, Encoding: UTF-8
        $this->tcpdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set margin, auto page break, dan default font sesuai kebutuhan
        $this->tcpdf->SetMargins(15, 27, 15);
        $this->tcpdf->SetAutoPageBreak(TRUE, 25);
        $this->tcpdf->SetFont('dejavusans', '', 10);
    }

    /**
     * Memuat HTML yang akan dikonversi ke PDF.
     *
     * @param string $html
     */
    public function loadHtml($html)
    {
        $this->html = $html;
    }

    /**
     * Mengatur ukuran kertas dan orientasi.
     * Karena format TCPDF sudah ditetapkan pada konstruktor, jika ingin mengubah,
     * kita buat ulang instance TCPDF dengan parameter baru.
     *
     * @param string $paper_size (contoh: 'A4', 'LETTER', dll.)
     * @param string $orientation ('portrait' atau 'landscape')
     */
    public function setPaper($paper_size = 'A4', $orientation = 'portrait')
    {
        $ori = ($orientation == 'portrait') ? 'P' : 'L';
        // Buat ulang instance TCPDF dengan pengaturan baru
        $this->tcpdf = new TCPDF($ori, 'mm', $paper_size, true, 'UTF-8', false);
        $this->tcpdf->SetMargins(15, 15);
        $this->tcpdf->SetAutoPageBreak(TRUE, 25);
        $this->tcpdf->SetFont('dejavusans', '', 10);
    }

    /**
     * Merender dokumen PDF dari HTML yang telah dimuat.
     */
    public function render()
    {
        // Tambah halaman dan tulis HTML
        $this->tcpdf->AddPage();
        $this->tcpdf->writeHTML($this->html, true, false, true, false, '');
    }

    /**
     * Menampilkan atau menyimpan PDF.
     *
     * @param string $filename Nama file PDF.
     * @param array  $options  Opsi tambahan. Gunakan array("Attachment" => 0) untuk preview di browser,
     *                         atau array("Attachment" => 1) untuk download.
     */
    public function stream($filename = "document.pdf", $options = array("Attachment" => 1))
    {
        // Jika Attachment = 0 maka tampilkan inline ('I'), jika 1 download ('D')
        $dest = ($options["Attachment"] == 0) ? 'I' : 'D';
        $this->tcpdf->Output($filename, $dest);
    }
}
