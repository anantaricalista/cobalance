<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_persediaan_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
        $this->ci->load->library('retur_persediaan_kalkulasi');
    }

    public function sumber()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal,
            t.jenis_transaksi, t.cara_pembayaran, d.id_detail_transaksi, d.jumlah,
            d.deskripsi, p.kode_produk, p.satuan, h.tipe_produk
            FROM transaksi t JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            LEFT JOIN riwayat_akun_transaksi h ON h.id_perusahaan=d.id_perusahaan AND h.id_detail_transaksi=d.id_detail_transaksi
            WHERE t.id_perusahaan=? AND t.jenis_transaksi IN ('penjualan','pembelian') AND t.status='diposting'
            ORDER BY t.id_transaksi DESC, d.id_detail_transaksi", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function daftar()
    {
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.jenis_transaksi,
            t.status, t.total, t.lampiran_nama, a.nomor_transaksi AS nomor_asal
            FROM transaksi t JOIN transaksi a ON a.id_perusahaan=t.id_perusahaan AND a.id_transaksi=t.id_transaksi_asal
            WHERE t.id_perusahaan=? AND t.jenis_transaksi IN ('retur_penjualan','retur_pembelian')
            ORDER BY t.id_transaksi DESC", array($this->company()));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $this->id($id);
        $query = $this->ci->db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . ",
            a.nomor_transaksi AS nomor_asal, d.jumlah, d.nilai_sebelum_pajak, d.nilai_pajak,
            d.subtotal, d.deskripsi AS nama_produk, p.kode_produk, p.satuan
            FROM transaksi t JOIN transaksi a ON a.id_perusahaan=t.id_perusahaan AND a.id_transaksi=t.id_transaksi_asal
            JOIN detail_transaksi d ON d.id_perusahaan=t.id_perusahaan AND d.id_transaksi=t.id_transaksi
            JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi IN ('retur_penjualan','retur_pembelian')",
            array($this->company(), $id));
        $this->ok($query);
        return $query->row();
    }

    public function simpan($tanggal, $data, $lampiran = null)
    {
        if (!is_array($data) || !isset($data['id_detail_asal'], $data['jumlah'])) {
            throw new InvalidArgumentException('Rincian retur tidak lengkap.');
        }
        $this->id($data['id_detail_asal']);
        $quantity = $this->decimal($data['jumlah'], 14, 4);
        if ($this->units($quantity) <= 0) {
            throw new InvalidArgumentException('Jumlah retur harus lebih dari nol.');
        }
        $counter = isset($data['id_akun_selisih']) && $data['id_akun_selisih'] !== '' ? $data['id_akun_selisih'] : null;
        if ($counter !== null) {
            $this->id($counter);
        }
        $description = isset($data['deskripsi']) && is_string($data['deskripsi']) ? trim($data['deskripsi']) : '';
        if ($description === '' || strlen($description) > 255) {
            throw new InvalidArgumentException('Keterangan retur wajib diisi, maksimal 255 byte.');
        }
        $company = $this->company();
        $source = $this->source($company, $data['id_detail_asal'], false);
        $type = $source->jenis_transaksi === 'penjualan' ? 'retur_penjualan' : 'retur_pembelian';
        return $this->ci->transaksi_service->buat_draft($type, $tanggal, $description,
            function ($id, $company, $db) use ($data, $quantity, $counter, $lampiran, $type, $tanggal) {
                $source = $this->source($company, $data['id_detail_asal'], true);
                $this->validate_source($source, $type, $tanggal);
                if ($counter !== null) {
                    $this->counter_account($company, $counter);
                }
                $this->calculate($company, $source, $quantity, $type);
                $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
                    'id_transaksi_asal' => $source->id_transaksi, 'cara_pembayaran' => $source->cara_pembayaran,
                    'id_pelanggan' => $source->id_pelanggan, 'id_pemasok' => $source->id_pemasok,
                    'id_akun_kas' => $source->id_akun_kas
                )));
                $this->ok($db->insert('detail_transaksi', array(
                    'id_perusahaan' => $company, 'id_transaksi' => $id,
                    'id_detail_asal' => $source->id_detail_transaksi, 'id_produk' => $source->id_produk,
                    'id_akun' => $counter, 'deskripsi' => $source->deskripsi, 'jumlah' => $quantity,
                    'harga' => $source->harga, 'mode_pajak' => $source->mode_pajak,
                    'id_pajak' => $source->id_pajak, 'tarif_pajak' => $source->tarif_pajak
                )));
                $this->ci->lampiran_service->simpan($company, $id, $lampiran, false);
            });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            $company = $transaction->id_perusahaan;
            $type = $transaction->jenis_transaksi;
            if (!in_array($type, array('retur_penjualan', 'retur_pembelian'), true)) {
                throw new DomainException('Transaksi retur tidak ditemukan.');
            }
            $query = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                array($company, $transaction->id_transaksi));
            $this->ok($query);
            $rows = $query->result();
            if (count($rows) !== 1 || !$rows[0]->id_detail_asal) {
                throw new DomainException('Rincian retur tidak valid.');
            }
            $detail = $rows[0];
            $source = $this->source($company, $detail->id_detail_asal, true);
            $this->validate_source($source, $type, $transaction->tanggal);
            if ((string) $source->id_transaksi !== (string) $transaction->id_transaksi_asal
                || (string) $source->id_produk !== (string) $detail->id_produk) {
                throw new DomainException('Rincian retur tidak sesuai transaksi asal.');
            }
            $amounts = $this->calculate($company, $source, $detail->jumlah, $type);
            $total = $this->cents($amounts['subtotal']);
            if ($total === 0) {
                throw new DomainException('Nilai retur harus lebih dari nol untuk membuat jurnal.');
            }
            if ($source->cara_pembayaran === 'kredit') {
                $paymentType = $type === 'retur_penjualan' ? 'penerimaan_piutang' : 'pembayaran_utang';
                $query = $db->query("SELECT COALESCE(SUM(a.jumlah),0) AS terbayar FROM alokasi_transaksi a
                    JOIN transaksi t ON t.id_perusahaan=a.id_perusahaan AND t.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND t.status='diposting'
                    AND t.jenis_transaksi=?", array($company, $source->id_transaksi, $paymentType));
                $this->ok($query);
                $paid = $this->cents($query->row()->terbayar);
                $query = $db->query("SELECT COALESCE(SUM(total),0) AS diretur FROM transaksi WHERE id_perusahaan=?
                    AND id_transaksi_asal=? AND jenis_transaksi=? AND status='diposting'",
                    array($company, $source->id_transaksi, $type));
                $this->ok($query);
                if ($total > $this->cents($source->total) - $paid - $this->cents($query->row()->diretur)) {
                    throw new DomainException('Nilai retur melebihi sisa tagihan setelah pembayaran dan retur.');
                }
            }
            $sale = $type === 'retur_penjualan';
            $journal = array();
            $net = $this->cents($amounts['nilai_sebelum_pajak']);
            $tax = $this->cents($amounts['nilai_pajak']);
            $this->journal($journal, $source->id_akun_nilai, $sale ? $net : -$net);
            if ($tax) {
                if (!$source->id_akun_pajak) {
                    throw new DomainException('Akun pajak historis tidak tersedia.');
                }
                $this->journal($journal, $source->id_akun_pajak, $sale ? $tax : -$tax);
            }
            $this->journal($journal, $source->id_akun_penyelesaian, $sale ? -$total : $total);
            if ($source->tipe_produk === 'barang') {
                if (!$source->id_akun_persediaan || ($sale && !$source->id_akun_beban_pokok)) {
                    throw new DomainException('Akun persediaan historis tidak lengkap.');
                }
                $cost = $this->stock($transaction, $detail, $sale ? $this->cents($amounts['harga_pokok']) : null);
                if ($sale) {
                    $this->journal($journal, $source->id_akun_persediaan, $cost);
                    $this->journal($journal, $source->id_akun_beban_pokok, -$cost);
                } else {
                    $difference = $cost - $net;
                    $this->journal($journal, $source->id_akun_persediaan, -$difference);
                    if ($difference !== 0) {
                        if (!$detail->id_akun) {
                            throw new DomainException('Pilih akun laba/rugi selisih persediaan.');
                        }
                        $this->counter_account($company, $detail->id_akun);
                        $this->journal($journal, $detail->id_akun, $difference);
                    }
                }
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_detail_transaksi', $detail->id_detail_transaksi)
                ->update('detail_transaksi', array('diskon' => $amounts['diskon'],
                    'nilai_sebelum_pajak' => $amounts['nilai_sebelum_pajak'],
                    'nilai_pajak' => $amounts['nilai_pajak'], 'subtotal' => $amounts['subtotal'])));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transaction->id_transaksi)
                ->update('transaksi', array('total_sebelum_pajak' => $amounts['nilai_sebelum_pajak'],
                    'total_pajak' => $amounts['nilai_pajak'], 'total' => $amounts['subtotal'])));
            $normalized = array();
            foreach ($journal as $account => $value) {
                if ($value !== 0) {
                    $normalized[] = array('id_akun' => $account, 'debit' => $value > 0 ? $this->money($value) : '0.00',
                        'kredit' => $value < 0 ? $this->money(-$value) : '0.00');
                }
            }
            return $normalized;
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction, $db) {
            if (!in_array($transaction->jenis_transaksi, array('retur_penjualan', 'retur_pembelian'), true)) {
                throw new DomainException('Transaksi retur tidak ditemukan.');
            }
            $company = $transaction->id_perusahaan;
            $query = $db->query('SELECT h.tipe_produk FROM detail_transaksi d
                JOIN riwayat_akun_transaksi h ON h.id_perusahaan=d.id_perusahaan AND h.id_detail_transaksi=d.id_detail_asal
                WHERE d.id_perusahaan=? AND d.id_transaksi=? FOR UPDATE',
                array($company, $transaction->id_transaksi));
            $this->ok($query);
            $details = $query->result();
            if (count($details) !== 1) {
                throw new DomainException('Riwayat akun retur tidak lengkap.');
            }
            $query = $db->query("SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_transaksi=?
                AND jenis_mutasi IN ('masuk','keluar') FOR UPDATE", array($company, $transaction->id_transaksi));
            $this->ok($query);
            $moves = $query->result();
            if (count($moves) !== ($details[0]->tipe_produk === 'barang' ? 1 : 0)) {
                throw new DomainException('Riwayat mutasi retur tidak valid.');
            }
            if (!$moves) {
                return;
            }
            $move = $moves[0];
            $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan
                WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan>? LIMIT 1 FOR UPDATE',
                array($company, $move->id_produk, $move->id_mutasi_persediaan));
            $this->ok($later);
            if ($later->row()) {
                throw new DomainException('Persediaan sudah berubah setelah retur sehingga pembatalan tidak aman.');
            }
            $query = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
                array($company, $move->id_produk));
            $this->ok($query);
            $balance = $query->row();
            if (!$balance || $balance->jumlah !== $move->saldo_jumlah || $balance->nilai !== $move->saldo_nilai
                || $balance->harga_pokok_rata_rata !== $move->harga_pokok_rata_rata) {
                throw new DomainException('Saldo persediaan tidak sesuai dengan riwayat retur.');
            }
            $prior = $db->query('SELECT harga_pokok_rata_rata FROM mutasi_persediaan
                WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan<?
                ORDER BY id_mutasi_persediaan DESC LIMIT 1 FOR UPDATE',
                array($company, $move->id_produk, $move->id_mutasi_persediaan));
            $this->ok($prior);
            $sign = $move->jenis_mutasi === 'masuk' ? -1 : 1;
            $units = $this->units($balance->jumlah) + $sign * $this->units($move->jumlah);
            $value = $this->cents($balance->nilai) + $sign * $this->cents($move->nilai);
            if ($units < 0 || $value < 0 || ($units === 0 && $value !== 0) || ($units > 0 && !$prior->row())) {
                throw new DomainException('Saldo persediaan sebelum retur tidak dapat dipulihkan.');
            }
            $average = $units === 0 ? '0.000000' : $prior->row()->harga_pokok_rata_rata;
            $quantity = $this->quantity($units);
            $money = $this->money($value);
            $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $move->id_produk)
                ->update('saldo_persediaan', array('jumlah' => $quantity, 'nilai' => $money,
                    'harga_pokok_rata_rata' => $average)));
            $this->ok($db->insert('mutasi_persediaan', array(
                'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                'id_detail_transaksi' => $move->id_detail_transaksi, 'id_produk' => $move->id_produk,
                'jenis_mutasi' => $sign < 0 ? 'pembalik_masuk' : 'pembalik_keluar',
                'jumlah' => $move->jumlah, 'nilai' => $move->nilai,
                'saldo_jumlah' => $quantity, 'saldo_nilai' => $money, 'harga_pokok_rata_rata' => $average
            )));
        });
    }

    private function source($company, $id, $lock)
    {
        $query = $this->ci->db->query("SELECT d.*, t.jenis_transaksi, t.status, t.tanggal, t.cara_pembayaran,
            t.total, t.id_pelanggan, t.id_pemasok, t.id_akun_kas,
            h.tipe_produk, h.id_akun_nilai, h.id_akun_pajak, h.id_akun_persediaan,
            h.id_akun_beban_pokok, h.id_akun_penyelesaian
            FROM detail_transaksi d JOIN transaksi t ON t.id_perusahaan=d.id_perusahaan AND t.id_transaksi=d.id_transaksi
            LEFT JOIN riwayat_akun_transaksi h ON h.id_perusahaan=d.id_perusahaan AND h.id_detail_transaksi=d.id_detail_transaksi
            WHERE d.id_perusahaan=? AND d.id_detail_transaksi=? AND t.jenis_transaksi IN ('penjualan','pembelian')"
            . ($lock ? ' FOR UPDATE' : ''), array($company, $id));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Rincian transaksi asal tidak ditemukan.');
        }
        return $query->row();
    }

    private function validate_source($source, $type, $date)
    {
        if ($source->status !== 'diposting'
            || ($source->jenis_transaksi === 'penjualan' ? 'retur_penjualan' : 'retur_pembelian') !== $type
            || $date < $source->tanggal) {
            throw new DomainException('Transaksi asal tidak terposting atau tanggal retur sebelum transaksi asal.');
        }
        if (!$source->tipe_produk || !$source->id_akun_nilai || !$source->id_akun_penyelesaian) {
            throw new DomainException('Riwayat akun transaksi asal tidak tersedia; retur tidak dapat diproses dengan aman.');
        }
    }

    private function calculate($company, $source, $quantity, $type)
    {
        $db = $this->ci->db;
        $query = $db->query("SELECT COALESCE(SUM(d.jumlah),0) AS jumlah,
            COALESCE(SUM(d.nilai_sebelum_pajak),0) AS nilai_sebelum_pajak,
            COALESCE(SUM(d.nilai_pajak),0) AS nilai_pajak, COALESCE(SUM(d.diskon),0) AS diskon,
            COALESCE(SUM(m.nilai),0) AS harga_pokok
            FROM detail_transaksi d JOIN transaksi t ON t.id_perusahaan=d.id_perusahaan AND t.id_transaksi=d.id_transaksi
            LEFT JOIN mutasi_persediaan m ON m.id_perusahaan=d.id_perusahaan AND m.id_detail_transaksi=d.id_detail_transaksi
                AND m.jenis_mutasi='masuk' AND t.jenis_transaksi='retur_penjualan'
            WHERE d.id_perusahaan=? AND d.id_detail_asal=? AND t.status='diposting' AND t.jenis_transaksi=?",
            array($company, $source->id_detail_transaksi, $type));
        $this->ok($query);
        $previous = $query->row_array();
        $cost = '0.00';
        if ($type === 'retur_penjualan' && $source->tipe_produk === 'barang') {
            $query = $db->query("SELECT nilai FROM mutasi_persediaan WHERE id_perusahaan=? AND id_detail_transaksi=?
                AND jenis_mutasi='keluar' FOR UPDATE", array($company, $source->id_detail_transaksi));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Harga pokok penjualan asal tidak tersedia.');
            }
            $cost = $query->row()->nilai;
        }
        return $this->ci->retur_persediaan_kalkulasi->hitung(array(
            'jumlah' => $source->jumlah, 'nilai_sebelum_pajak' => $source->nilai_sebelum_pajak,
            'nilai_pajak' => $source->nilai_pajak, 'diskon' => $source->diskon, 'harga_pokok' => $cost
        ), $previous, $quantity);
    }

    private function stock($transaction, $detail, $saleCost)
    {
        $db = $this->ci->db;
        $company = $transaction->id_perusahaan;
        $sale = $transaction->jenis_transaksi === 'retur_penjualan';
        $query = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
            array($company, $detail->id_produk));
        $this->ok($query);
        $balance = $query->row();
        $oldUnits = $balance ? $this->units($balance->jumlah) : 0;
        $oldValue = $balance ? $this->cents($balance->nilai) : 0;
        $units = $this->units($detail->jumlah);
        if (!$sale && (!$balance || $units > $oldUnits)) {
            throw new DomainException('Stok tidak mencukupi untuk retur pembelian.');
        }
        if ($sale) {
            $cost = $saleCost;
        } elseif ($units === $oldUnits) {
            $cost = $oldValue;
        } else {
            $query = $db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(18,4)) *
                CAST(? AS DECIMAL(19,6)),2) AS CHAR) AS nilai', array($detail->jumlah, $balance->harga_pokok_rata_rata));
            $this->ok($query);
            $cost = $this->cents($query->row()->nilai);
        }
        $newUnits = $oldUnits + ($sale ? $units : -$units);
        $newValue = $oldValue + ($sale ? $cost : -$cost);
        if ($newUnits > 999999999999999999 || $newValue < 0 || $newValue > 999999999999999
            || ($newUnits === 0 && $newValue !== 0)) {
            throw new DomainException('Saldo persediaan tidak valid atau melebihi batas database.');
        }
        $quantity = $this->quantity($newUnits);
        $value = $this->money($newValue);
        if (!$newUnits) {
            $average = '0.000000';
        } elseif ($sale) {
            $query = $db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) / CAST(? AS DECIMAL(18,4)),6) AS CHAR) AS rata',
                array($value, $quantity));
            $this->ok($query);
            $average = $this->decimal($query->row()->rata, 13, 6);
        } else {
            $average = $balance->harga_pokok_rata_rata;
        }
        $data = array('jumlah' => $quantity, 'nilai' => $value, 'harga_pokok_rata_rata' => $average);
        if ($balance) {
            $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $detail->id_produk)->update('saldo_persediaan', $data));
        } else {
            $this->ok($db->insert('saldo_persediaan', $data + array('id_perusahaan' => $company, 'id_produk' => $detail->id_produk)));
        }
        $this->ok($db->insert('mutasi_persediaan', array('id_perusahaan' => $company,
            'id_transaksi' => $transaction->id_transaksi, 'id_detail_transaksi' => $detail->id_detail_transaksi,
            'id_produk' => $detail->id_produk, 'jenis_mutasi' => $sale ? 'masuk' : 'keluar',
            'jumlah' => $detail->jumlah, 'nilai' => $this->money($cost), 'saldo_jumlah' => $quantity,
            'saldo_nilai' => $value, 'harga_pokok_rata_rata' => $average)));
        return $cost;
    }

    private function counter_account($company, $id)
    {
        $query = $this->ci->db->query("SELECT id_akun FROM akun WHERE id_perusahaan=? AND id_akun=?
            AND tipe_akun IN ('Pendapatan','Beban') FOR UPDATE", array($company, $id));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Akun selisih harus berupa akun pendapatan atau beban.');
        }
        $query = $this->ci->db->query('SELECT id_akun FROM akun WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        if ($query->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan untuk selisih persediaan.');
        }
    }

    private function journal(&$journal, $account, $amount)
    {
        if (!$account && $amount !== 0) {
            throw new DomainException('Akun historis retur tidak lengkap.');
        }
        if ($amount !== 0) {
            $key = (string) $account;
            $journal[$key] = (isset($journal[$key]) ? $journal[$key] : 0) + $amount;
        }
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $this->id($company);
        $this->id($user);
        $query = $this->ci->db->query('SELECT id_pengguna FROM pengguna WHERE id_perusahaan=? AND id_pengguna=?',
            array($company, $user));
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

    private function decimal($value, $digits, $scale)
    {
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,' . $scale . '})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Jumlah atau nilai desimal tidak valid.');
        }
        $parts = explode('.', (string) $value);
        return $parts[0] . '.' . str_pad(isset($parts[1]) ? $parts[1] : '', $scale, '0');
    }

    private function units($value)
    {
        $parts = explode('.', $this->decimal($value, 14, 4));
        return (int) $parts[0] * 10000 + (int) $parts[1];
    }

    private function cents($value)
    {
        $parts = explode('.', $this->decimal($value, 13, 2));
        return (int) $parts[0] * 100 + (int) $parts[1];
    }

    private function quantity($value)
    {
        return intdiv($value, 10000) . '.' . str_pad((string) ($value % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function money($value)
    {
        return intdiv($value, 100) . '.' . str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database retur gagal.');
        }
    }
}
