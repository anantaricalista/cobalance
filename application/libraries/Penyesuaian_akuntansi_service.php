<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penyesuaian_akuntansi_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
        $this->ci->load->library('pemindahan_laba_service');
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status,
            t.total, t.deskripsi, t.lampiran_nama, COUNT(d.id_detail_penyesuaian) AS jumlah_baris,
            COALESCE(SUM(d.debit), 0) AS total_debit, COALESCE(SUM(d.kredit), 0) AS total_kredit
            FROM transaksi t
            LEFT JOIN detail_penyesuaian d
                ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penyesuaian_akuntansi'
            GROUP BY t.id_transaksi ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $this->id($id);
        $company = $this->company();
        $query = $this->ci->db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . '
            FROM transaksi t WHERE t.id_perusahaan=? AND t.id_transaksi=?
            AND t.jenis_transaksi=\'penyesuaian_akuntansi\'', array($company, $id));
        $this->ok($query);
        $transaction = $query->row();
        if (!$transaction) {
            return null;
        }
        $query = $this->ci->db->query('SELECT d.id_akun, d.debit, d.kredit, d.deskripsi,
            a.kode_akun, a.nama_akun FROM detail_penyesuaian d
            JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            WHERE d.id_perusahaan=? AND d.id_transaksi=? ORDER BY d.id_detail_penyesuaian',
            array($company, $id));
        $this->ok($query);
        $transaction->details = $query->result();
        $transfer = $this->ci->pemindahan_laba_service->untuk_transaksi($id);
        $transaction->pemindahan_tahun = $transfer ? (int) $transfer->tahun : null;
        return $transaction;
    }

    public function simpan($id, $tanggal, $description, $details, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        $description = is_string($description) ? trim($description) : '';
        if ($this->length($description) > 255) {
            throw new InvalidArgumentException('Keterangan maksimal 255 karakter.');
        }
        $details = $this->normalize_details($details);

        if ($id === null || $id === '') {
            return $this->ci->transaksi_service->buat_draft('penyesuaian_akuntansi', $tanggal,
                $description === '' ? 'Penyesuaian akuntansi' : $description,
                function ($transactionId, $company, $db) use ($description, $details, $lampiran) {
                    $this->save_details($company, $transactionId, $description, $details);
                    $this->ci->lampiran_service->simpan($company, $transactionId, $lampiran, false);
                });
        }

        $this->id($id);
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $description, $details, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT nomor_transaksi, status FROM transaksi
                WHERE id_perusahaan=? AND id_transaksi=? AND jenis_transaksi='penyesuaian_akuntansi' FOR UPDATE",
                array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft penyesuaian milik perusahaan ini yang dapat diubah.');
            }
            if ($this->ci->pemindahan_laba_service->untuk_transaksi($id)) {
                throw new DomainException('Draft pemindahan laba dibuat otomatis. Perbarui melalui tombol Pindahkan Saldo Laba Berjalan.');
            }
            $this->validate_accounts($company, $details);
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi',
                array('tanggal' => $tanggal, 'deskripsi' => $description === '' ? 'Penyesuaian akuntansi' : $description)));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('detail_penyesuaian'));
            $this->insert_details($company, $id, $description, $details);
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function posting($id)
    {
        $transfer = $this->ci->pemindahan_laba_service->untuk_transaksi($id);
        $parents = $transfer ? array((int) $transfer->id_akun_berjalan) : array();
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) use ($transfer) {
            if ($transaction->jenis_transaksi !== 'penyesuaian_akuntansi') {
                throw new DomainException('Transaksi penyesuaian akuntansi tidak ditemukan.');
            }
            $query = $db->query('SELECT id_akun, debit, kredit FROM detail_penyesuaian
                WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_detail_penyesuaian FOR UPDATE',
                array($transaction->id_perusahaan, $transaction->id_transaksi));
            $this->ok($query);
            $details = $this->normalize_details($query->result_array());
            if ($transfer) {
                $this->ci->pemindahan_laba_service->validasi_posting($transaction->id_perusahaan, $transaction->id_transaksi);
            } else {
                $this->validate_accounts($transaction->id_perusahaan, $details);
            }
            return $details;
        }, $parents);
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction) {
            if ($transaction->jenis_transaksi !== 'penyesuaian_akuntansi') {
                throw new DomainException('Transaksi penyesuaian akuntansi tidak ditemukan.');
            }
        });
    }

    private function save_details($company, $id, $description, $details)
    {
        $this->validate_accounts($company, $details);
        $this->insert_details($company, $id, $description, $details);
    }

    private function insert_details($company, $id, $description, $details)
    {
        foreach ($details as $row) {
            $this->ok($this->ci->db->insert('detail_penyesuaian', array(
                'id_perusahaan' => $company, 'id_transaksi' => $id,
                'id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit'],
                'deskripsi' => $row['deskripsi'] === '' ? ($description === '' ? null : $description) : $row['deskripsi']
            )));
        }
    }

    private function normalize_details($details)
    {
        if (!is_array($details) || count($details) < 2) {
            throw new InvalidArgumentException('Penyesuaian harus memiliki minimal dua baris rincian.');
        }
        $normalized = array();
        $debit = 0;
        $credit = 0;
        foreach ($details as $row) {
            if (!is_array($row) || !isset($row['id_akun'], $row['debit'], $row['kredit'])) {
                throw new InvalidArgumentException('Rincian penyesuaian tidak lengkap.');
            }
            $this->id($row['id_akun']);
            $d = $this->money_input($row['debit']);
            $k = $this->money_input($row['kredit']);
            $dCents = $this->cents($d);
            $kCents = $this->cents($k);
            if (($dCents > 0) === ($kCents > 0)) {
                throw new InvalidArgumentException('Setiap baris harus berisi debit atau kredit, bukan keduanya.');
            }
            if ($debit > 999999999999999 - $dCents || $credit > 999999999999999 - $kCents) {
                throw new InvalidArgumentException('Total penyesuaian melebihi batas database.');
            }
            $debit += $dCents;
            $credit += $kCents;
            $lineDescription = isset($row['deskripsi']) && is_string($row['deskripsi']) ? trim($row['deskripsi']) : '';
            if ($this->length($lineDescription) > 255) {
                throw new InvalidArgumentException('Keterangan rincian maksimal 255 karakter.');
            }
            $normalized[] = array('id_akun' => $row['id_akun'], 'debit' => $d, 'kredit' => $k,
                'deskripsi' => $lineDescription);
        }
        if ($debit < 1 || $debit !== $credit) {
            throw new InvalidArgumentException('Total debit dan kredit harus sama dan lebih dari nol.');
        }
        return $normalized;
    }

    private function validate_accounts($company, $details)
    {
        foreach ($details as $row) {
            $query = $this->ci->db->query('SELECT id_akun FROM akun
                WHERE id_perusahaan=? AND id_akun=? FOR UPDATE', array($company, $row['id_akun']));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Akun tidak ditemukan pada perusahaan ini.');
            }
            $query = $this->ci->db->query('SELECT id_akun FROM akun
                WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE', array($company, $row['id_akun']));
            $this->ok($query);
            if ($query->row()) {
                throw new DomainException('Akun induk tidak dapat digunakan pada penyesuaian.');
            }
        }
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $this->id($company);
        $this->id($user);
        $query = $this->ci->db->query('SELECT id_pengguna FROM pengguna
            WHERE id_perusahaan=? AND id_pengguna=?', array($company, $user));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return $company;
    }

    private function id($id)
    {
        if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id)
            || (int) $id < 1 || (int) $id > 2147483647) {
            throw new InvalidArgumentException('ID tidak valid.');
        }
    }

    private function date($date)
    {
        if (!is_string($date) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
            || !($value = DateTime::createFromFormat('!Y-m-d', $date)) || $value->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }
    }

    private function money_input($value)
    {
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('Nominal tidak valid.');
        }
        $value = (string) $value;
        if (strpos($value, ',') !== false) {
            if (!preg_match('/^(?:\d{1,3}(?:\.\d{3})*|\d{1,13}),\d{1,2}$/D', $value)) {
                throw new InvalidArgumentException('Nominal tidak valid.');
            }
            $value = str_replace(array('.', ','), array('', '.'), $value);
        } elseif (preg_match('/^\d{1,3}(?:\.\d{3})+$/D', $value)) {
            $value = str_replace('.', '', $value);
        } elseif (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException('Nominal tidak valid.');
        }
        return $this->money($this->cents($value));
    }

    private function cents($amount)
    {
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount)) {
            throw new InvalidArgumentException('Nominal melebihi batas database.');
        }
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database penyesuaian akuntansi gagal.');
        }
    }
}
