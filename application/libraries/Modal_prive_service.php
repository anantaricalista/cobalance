<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Modal_prive_service
{
    private $types = array('setoran_modal' => 'Setoran modal', 'prive' => 'Prive');
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
    }

    public function jenis()
    {
        return $this->types;
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal,
            t.jenis_transaksi, t.status, t.total, t.deskripsi, t.lampiran_nama,
            c.kode_akun AS kode_kas, c.nama_akun AS nama_kas,
            a.kode_akun AS kode_lawan, a.nama_akun AS nama_lawan
            FROM transaksi t
            JOIN akun c ON c.id_perusahaan=t.id_perusahaan AND c.id_akun=t.id_akun_kas
            JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            WHERE t.id_perusahaan=? AND t.jenis_transaksi IN ('setoran_modal','prive')
            ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $this->id($id);
        $company = $this->company();
        $query = $this->ci->db->query("SELECT " . Lampiran_service::KOLOM_TRANSAKSI . ",
            c.kode_akun AS kode_kas, c.nama_akun AS nama_kas,
            d.id_akun, d.subtotal, a.kode_akun AS kode_lawan, a.nama_akun AS nama_lawan
            FROM transaksi t
            JOIN akun c ON c.id_perusahaan=t.id_perusahaan AND c.id_akun=t.id_akun_kas
            JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN akun a ON a.id_perusahaan=d.id_perusahaan AND a.id_akun=d.id_akun
            WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi IN ('setoran_modal','prive')",
            array($company, $id));
        $this->ok($query);
        return $query->row();
    }

    public function simpan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if (!is_array($data) || !isset($data['jenis'], $data['id_akun_kas'], $data['jumlah'])) {
            throw new InvalidArgumentException('Data transaksi Modal dan Prive tidak lengkap.');
        }
        if (!is_string($data['jenis']) || !isset($this->types[$data['jenis']])) {
            throw new InvalidArgumentException('Jenis transaksi Modal dan Prive tidak valid.');
        }
        $this->id($data['id_akun_kas']);
        if ($data['jenis'] === 'prive') {
            $this->id(isset($data['id_akun_modal']) ? $data['id_akun_modal'] : null);
        }
        $amount = $this->money_input($data['jumlah']);
        if ($this->cents($amount) < 1) {
            throw new InvalidArgumentException('Jumlah transaksi harus lebih dari nol.');
        }
        $description = isset($data['deskripsi']) && is_string($data['deskripsi']) ? trim($data['deskripsi']) : '';
        if ($this->length($description) > 255) {
            throw new InvalidArgumentException('Keterangan maksimal 255 karakter.');
        }
        if ($id === null || $id === '') {
            return $this->ci->transaksi_service->buat_draft($data['jenis'], $tanggal,
                $description === '' ? $this->types[$data['jenis']] : $description,
                function ($transactionId, $company, $db) use ($data, $amount, $description, $lampiran) {
                    $cash = $this->account($company, $data['id_akun_kas'], '1100', null);
                    $counter = $data['jenis'] === 'setoran_modal'
                        ? $this->setting($company, 'modal', '3100')
                        : $this->equity_account($company, $data['id_akun_modal'], true);
                    if ((string) $cash === (string) $counter) {
                        throw new DomainException('Akun kas/bank dan akun lawan harus berbeda.');
                    }
                    $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transactionId)
                        ->update('transaksi', array('id_akun_kas' => $cash, 'total_sebelum_pajak' => $amount,
                            'total_pajak' => '0.00', 'total' => $amount,
                            'deskripsi' => $description === '' ? $this->types[$data['jenis']] : $description)));
                    $this->ok($db->insert('detail_transaksi', array(
                        'id_perusahaan' => $company, 'id_transaksi' => $transactionId,
                        'id_akun' => $counter, 'deskripsi' => $this->types[$data['jenis']],
                        'jumlah' => '1.0000', 'harga' => $amount, 'nilai_sebelum_pajak' => $amount,
                        'nilai_pajak' => '0.00', 'subtotal' => $amount
                    )));
                    $this->ci->lampiran_service->simpan($company, $transactionId, $lampiran, false);
                });
        }

        $this->id($id);
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $data, $amount, $description, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT id_transaksi, nomor_transaksi, status FROM transaksi
                WHERE id_perusahaan=? AND id_transaksi=? AND jenis_transaksi IN ('setoran_modal','prive') FOR UPDATE",
                array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft transaksi milik perusahaan ini yang dapat diubah.');
            }
            if ($transaction->jenis_transaksi !== $data['jenis']) {
                throw new DomainException('Jenis transaksi draft tidak dapat diubah.');
            }
            $cash = $this->account($company, $data['id_akun_kas'], '1100', null);
            $counter = $data['jenis'] === 'setoran_modal'
                ? $this->setting($company, 'modal', '3100')
                : $this->equity_account($company, $data['id_akun_modal'], true);
            if ((string) $cash === (string) $counter) {
                throw new DomainException('Akun kas/bank dan akun lawan harus berbeda.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                ->update('transaksi', array('tanggal' => $tanggal, 'id_akun_kas' => $cash,
                    'total_sebelum_pajak' => $amount, 'total_pajak' => '0.00', 'total' => $amount,
                    'deskripsi' => $description === '' ? $this->types[$data['jenis']] : $description)));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('detail_transaksi'));
            $this->ok($db->insert('detail_transaksi', array(
                'id_perusahaan' => $company, 'id_transaksi' => $id,
                'id_akun' => $counter, 'deskripsi' => $this->types[$data['jenis']],
                'jumlah' => '1.0000', 'harga' => $amount, 'nilai_sebelum_pajak' => $amount,
                'nilai_pajak' => '0.00', 'subtotal' => $amount
            )));
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            $type = $transaction->jenis_transaksi;
            if (!isset($this->types[$type]) || !$transaction->id_akun_kas) {
                throw new DomainException('Transaksi Modal dan Prive tidak ditemukan.');
            }
            $company = $transaction->id_perusahaan;
            $query = $db->query('SELECT id_akun, subtotal FROM detail_transaksi
                WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE', array($company, $transaction->id_transaksi));
            $this->ok($query);
            $detail = $query->row();
            if (!$detail || $query->num_rows() !== 1) {
                throw new DomainException('Transaksi harus memiliki satu rincian.');
            }
            $this->account($company, $transaction->id_akun_kas, '1100', null);
            $counter = $type === 'setoran_modal'
                ? $this->account($company, $detail->id_akun, '3100', null)
                : $this->equity_account($company, $detail->id_akun);
            $amount = $this->cents($detail->subtotal);
            if ($amount < 1 || $amount !== $this->cents($transaction->total)
                || $amount !== $this->cents($transaction->total_sebelum_pajak)
                || $this->cents($transaction->total_pajak) !== 0) {
                throw new DomainException('Jumlah transaksi tidak sesuai dengan rincian.');
            }
            if ((string) $counter === (string) $transaction->id_akun_kas) {
                throw new DomainException('Akun kas/bank dan akun lawan harus berbeda.');
            }
            return $type === 'setoran_modal'
                ? array(
                    array('id_akun' => $transaction->id_akun_kas, 'debit' => $this->money($amount), 'kredit' => '0.00'),
                    array('id_akun' => $counter, 'debit' => '0.00', 'kredit' => $this->money($amount))
                )
                : array(
                    array('id_akun' => $counter, 'debit' => $this->money($amount), 'kredit' => '0.00'),
                    array('id_akun' => $transaction->id_akun_kas, 'debit' => '0.00', 'kredit' => $this->money($amount))
                );
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction) {
            if (!isset($this->types[$transaction->jenis_transaksi])) {
                throw new DomainException('Transaksi Modal dan Prive tidak ditemukan.');
            }
        });
    }

    private function equity_account($company, $id, $check_modal = false)
    {
        $this->account($company, $id, null, 'Ekuitas');
        if ($check_modal) {
            $query = $this->ci->db->query("SELECT id_akun FROM pengaturan_akun
                WHERE id_perusahaan=? AND jenis_pengaturan='modal' FOR UPDATE", array($company));
            $this->ok($query);
            if ($query->row() && (string) $query->row()->id_akun === (string) $id) {
                throw new DomainException('Akun Prive harus berbeda dari akun Modal Usaha.');
            }
        }
        return $id;
    }

    private function setting($company, $key, $root)
    {
        $query = $this->ci->db->query('SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan=? FOR UPDATE', array($company, $key));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengaturan akun ' . $key . ' belum tersedia.');
        }
        return $this->account($company, $query->row()->id_akun, $root, null);
    }

    private function account($company, $id, $root, $type)
    {
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account || ($type !== null && $account->tipe_akun !== $type)) {
            throw new DomainException('Akun transaksi tidak ditemukan atau tipenya tidak sesuai.');
        }
        $children = $this->ci->db->query('SELECT id_akun FROM akun
            WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE', array($company, $id));
        $this->ok($children);
        if ($children->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan pada transaksi.');
        }
        $found = false;
        $seen = array();
        while ($account) {
            if (isset($seen[$account->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$account->id_akun] = true;
            $found = $found || $account->kode_akun === $root;
            if (!$account->id_akun_induk) {
                break;
            }
            $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
                array($company, $account->id_akun_induk));
            $this->ok($query);
            $account = $query->row();
            if (!$account) {
                throw new DomainException('Induk akun tidak ditemukan.');
            }
        }
        if ($root !== null && !$found) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
        return $id;
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
            throw new InvalidArgumentException('Jumlah transaksi tidak valid.');
        }
        $value = (string) $value;
        if (strpos($value, ',') !== false) {
            if (!preg_match('/^(?:\d{1,3}(?:\.\d{3})*|\d{1,13}),\d{1,2}$/D', $value)) {
                throw new InvalidArgumentException('Jumlah transaksi tidak valid.');
            }
            $value = str_replace(array('.', ','), array('', '.'), $value);
        } elseif (preg_match('/^\d{1,3}(?:\.\d{3})+$/D', $value)) {
            $value = str_replace('.', '', $value);
        } elseif (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException('Jumlah transaksi tidak valid.');
        }
        return $this->money($this->cents($value));
    }

    private function cents($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount)) {
            throw new DomainException('Jumlah transaksi melebihi batas database.');
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
            throw new RuntimeException('Operasi database Modal dan Prive gagal.');
        }
    }
}
