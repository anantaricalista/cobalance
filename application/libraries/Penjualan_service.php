<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjualan_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
    }

    public function daftar()
    {
        $perusahaan = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.nomor_transaksi, t.tanggal, t.status,
            t.cara_pembayaran, t.total, t.id_pelanggan, p.nama_pelanggan, t.lampiran_nama
            FROM transaksi t JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='penjualan'
            ORDER BY t.tanggal DESC, t.id_transaksi DESC", array($perusahaan));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $perusahaan = $this->company();
        $this->id($id);
        $query = $this->ci->db->query("SELECT " . Lampiran_service::KOLOM_TRANSAKSI . ", p.nama_pelanggan FROM transaksi t
            JOIN pelanggan p ON p.id_perusahaan=t.id_perusahaan AND p.id_pelanggan=t.id_pelanggan
            WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi='penjualan'",
            array($perusahaan, $id));
        $this->ok($query);
        $transaction = $query->row();
        if (!$transaction) {
            return null;
        }
        $query = $this->ci->db->query('SELECT d.*, p.nama_produk, p.satuan, tx.kode_pajak, tx.nama_pajak
            FROM detail_transaksi d JOIN produk p ON p.id_perusahaan=d.id_perusahaan AND p.id_produk=d.id_produk
            LEFT JOIN pajak tx ON tx.id_perusahaan=d.id_perusahaan AND tx.id_pajak=d.id_pajak
            WHERE d.id_perusahaan=? AND d.id_transaksi=? ORDER BY d.id_detail_transaksi',
            array($perusahaan, $id));
        $this->ok($query);
        $transaction->details = $query->result_array();
        return $transaction;
    }

    public function simpan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data penjualan tidak valid.');
        }
        if ($id !== null && $id !== '') {
            $this->id($id);
        } else {
            $id = null;
        }
        if ($id === null) {
            $description = isset($data['deskripsi']) && is_string($data['deskripsi'])
                ? trim($data['deskripsi']) : 'Penjualan';
            return $this->ci->transaksi_service->buat_draft('penjualan', $tanggal, $description,
                function ($transaction_id, $company, $db) use ($tanggal, $data, $lampiran) {
                    $clean = $this->validate($company, $tanggal, $data);
                    $this->write_draft($transaction_id, $company, $clean, $db);
                    $this->ci->lampiran_service->simpan($company, $transaction_id, $lampiran, false);
                });
        }
        return $this->ci->transaksi_service->atomic(function ($perusahaan) use ($id, $tanggal, $data, $lampiran, $hapus_lampiran) {
            $data = $this->validate($perusahaan, $tanggal, $data);
            $db = $this->ci->db;
            $query = $db->query("SELECT * FROM transaksi WHERE id_perusahaan=? AND id_transaksi=?
                AND jenis_transaksi='penjualan' FOR UPDATE", array($perusahaan, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft penjualan milik perusahaan ini yang dapat diubah.');
            }
            $this->ok($db->where('id_perusahaan', $perusahaan)->where('id_transaksi', $id)->update('transaksi',
                array('tanggal' => $tanggal, 'jatuh_tempo' => $data['jatuh_tempo'],
                    'cara_pembayaran' => $data['cara_pembayaran'], 'id_pelanggan' => $data['id_pelanggan'],
                    'id_akun_kas' => $data['id_akun_kas'], 'referensi' => $data['referensi'],
                    'deskripsi' => $data['deskripsi'], 'total_sebelum_pajak' => $data['total_sebelum_pajak'],
                    'total_pajak' => $data['total_pajak'], 'total' => $data['total'])));
            $this->ok($db->where('id_perusahaan', $perusahaan)->where('id_transaksi', $id)->delete('detail_transaksi'));
            $this->write_details($id, $perusahaan, $data, $db);
            $this->ci->lampiran_service->simpan($perusahaan, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            $company = $transaction->id_perusahaan;
            if ($transaction->jenis_transaksi !== 'penjualan') {
                throw new DomainException('Transaksi penjualan tidak ditemukan.');
            }
            $query = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=?
                ORDER BY id_detail_transaksi FOR UPDATE', array($company, $transaction->id_transaksi));
            $this->ok($query);
            $details = $query->result();
            if (!$details) {
                throw new DomainException('Penjualan harus memiliki minimal satu rincian.');
            }

            $journal = array();
            $total = 0;
            $net_total = 0;
            $tax_total = 0;
            $account_history = array();
            foreach ($details as $detail) {
                $product = $this->product($company, $detail->id_produk);
                $revenue = $product->id_akun_pendapatan
                    ? $product->id_akun_pendapatan
                    : $this->setting($company, 'pendapatan_default', '4000');
                $this->account($company, $revenue, '4000');
                $this->add_journal($journal, $revenue, 0, $this->cents($detail->nilai_sebelum_pajak));
                $net_total = $this->add_cents($net_total, $this->cents($detail->nilai_sebelum_pajak));
                $tax_total = $this->add_cents($tax_total, $this->cents($detail->nilai_pajak));
                $total = $this->add_cents($total, $this->cents($detail->subtotal));
                $tax_account = null;
                $inventory = null;
                $cogs = null;

                if ($this->cents($detail->nilai_pajak) > 0) {
                    $tax = $this->tax($company, $detail->id_pajak);
                    $tax_account = $tax->id_akun_pajak_keluaran
                        ? $tax->id_akun_pajak_keluaran
                        : $this->setting($company, 'pajak_keluaran', '2200');
                    $this->account($company, $tax_account, '2200');
                    $this->add_journal($journal, $tax_account, 0, $this->cents($detail->nilai_pajak));
                }

                if ($product->tipe_produk === 'barang') {
                    $inventory = $product->id_akun_persediaan
                        ? $product->id_akun_persediaan
                        : $this->setting($company, 'persediaan', '1700');
                    $cogs = $product->id_akun_beban_pokok
                        ? $product->id_akun_beban_pokok
                        : $this->setting($company, 'beban_pokok_penjualan', '5100');
                    $this->account($company, $inventory, '1700');
                    $this->account($company, $cogs, '5100');
                    $this->ok($db->where('id_perusahaan', $company)->where('id_detail_transaksi', $detail->id_detail_transaksi)
                        ->update('detail_transaksi', array('id_akun' => $inventory)));

                    $balance = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
                        array($company, $product->id_produk));
                    $this->ok($balance);
                    $balance = $balance->row();
                    $quantity = $this->quantity_units($detail->jumlah);
                    $available = $balance ? $this->quantity_units($balance->jumlah) : 0;
                    if ($available < $quantity || !$balance) {
                        throw new DomainException('Stok ' . $product->nama_produk . ' tidak mencukupi untuk penjualan.');
                    }
                    $cost = $quantity === $available
                        ? $this->cents($balance->nilai)
                        : $this->multiply_quantity_money($detail->jumlah, $balance->harga_pokok_rata_rata);
                    $new_quantity = $available - $quantity;
                    $new_value = $this->cents($balance->nilai) - $cost;
                    if ($new_quantity < 0 || $new_value < 0) {
                        throw new DomainException('Nilai persediaan tidak mencukupi untuk penjualan.');
                    }
                    $new_quantity_value = $this->quantity($new_quantity);
                    $new_value_money = $this->money($new_value);
                    $average = $new_quantity === 0 ? '0.000000' : $balance->harga_pokok_rata_rata;
                    $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $product->id_produk)
                        ->update('saldo_persediaan', array('jumlah' => $new_quantity_value,
                            'nilai' => $new_value_money, 'harga_pokok_rata_rata' => $average)));
                    $this->ok($db->insert('mutasi_persediaan', array(
                        'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                        'id_detail_transaksi' => $detail->id_detail_transaksi, 'id_produk' => $product->id_produk,
                        'jenis_mutasi' => 'keluar', 'jumlah' => $this->quantity($quantity),
                        'nilai' => $this->money($cost), 'saldo_jumlah' => $new_quantity_value,
                        'saldo_nilai' => $new_value_money, 'harga_pokok_rata_rata' => $average
                    )));
                    $this->add_journal($journal, $cogs, $cost, 0);
                    $this->add_journal($journal, $inventory, 0, $cost);
                }
                $account_history[] = array(
                    'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                    'id_detail_transaksi' => $detail->id_detail_transaksi, 'tipe_produk' => $product->tipe_produk,
                    'id_akun_nilai' => $revenue, 'id_akun_pajak' => $tax_account,
                    'id_akun_persediaan' => $inventory, 'id_akun_beban_pokok' => $cogs
                );
            }

            if ($total !== $this->cents($transaction->total)
                || $net_total !== $this->cents($transaction->total_sebelum_pajak)
                || $tax_total !== $this->cents($transaction->total_pajak)) {
                throw new DomainException('Total penjualan tidak sesuai dengan rincian yang tersimpan.');
            }
            if ($transaction->cara_pembayaran === 'tunai') {
                $debit_account = $transaction->id_akun_kas;
                $this->account($company, $debit_account, '1100');
            } else {
                $debit_account = $this->setting($company, 'piutang_usaha', '1200');
            }
            $this->add_journal($journal, $debit_account, $total, 0);
            foreach ($account_history as $row) {
                $row['id_akun_penyelesaian'] = $debit_account;
                $this->ok($db->insert('riwayat_akun_transaksi', $row));
            }

            $normalized = array();
            foreach ($journal as $row) {
                $net = $row['debit_cents'] - $row['credit_cents'];
                if ($net !== 0) {
                    $normalized[] = array('id_akun' => $row['id_akun'],
                        'debit' => $net > 0 ? $this->money($net) : '0.00',
                        'kredit' => $net < 0 ? $this->money(-$net) : '0.00');
                }
            }
            return $normalized;
        });
    }

    public function batalkan($id, $date, $reason)
    {
        return $this->ci->transaksi_service->batalkan($id, $date, $reason,
            function ($transaction, $db) {
                if ($transaction->jenis_transaksi !== 'penjualan') {
                    throw new DomainException('Transaksi penjualan tidak ditemukan.');
                }
                $company = $transaction->id_perusahaan;
                $query = $db->query('SELECT a.id_alokasi_transaksi FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND r.status=\'diposting\' LIMIT 1 FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($query);
                if ($query->row()) {
                    throw new DomainException('Penjualan sudah memiliki alokasi pembayaran dan tidak dapat dibatalkan.');
                }
                $query = $db->query("SELECT id_transaksi FROM transaksi WHERE id_perusahaan=?
                    AND id_transaksi_asal=? AND jenis_transaksi='retur_penjualan' AND status='diposting'
                    LIMIT 1 FOR UPDATE", array($company, $transaction->id_transaksi));
                $this->ok($query);
                if ($query->row()) {
                    throw new DomainException('Penjualan memiliki retur terposting dan tidak dapat dibatalkan.');
                }
                $query = $db->query('SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_transaksi=?
                    AND jenis_mutasi=\'keluar\' ORDER BY id_mutasi_persediaan DESC FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($query);
                $moves = $query->result();
                $last_moves = array();
                foreach ($moves as $move) {
                    if (!isset($last_moves[$move->id_produk])
                        || $move->id_mutasi_persediaan > $last_moves[$move->id_produk]) {
                        $last_moves[$move->id_produk] = $move->id_mutasi_persediaan;
                    }
                }
                foreach ($last_moves as $product_id => $last_move_id) {
                    $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan
                        WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan>? LIMIT 1 FOR UPDATE',
                        array($company, $product_id, $last_move_id));
                    $this->ok($later);
                    if ($later->row()) {
                        throw new DomainException('Persediaan sudah berubah setelah penjualan sehingga pembatalan tidak aman.');
                    }
                }
                foreach ($moves as $move) {
                    $balance_query = $db->query('SELECT * FROM saldo_persediaan
                        WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($company, $move->id_produk));
                    $this->ok($balance_query);
                    $balance = $balance_query->row();
                    if (!$balance || $balance->jumlah !== $move->saldo_jumlah
                        || $balance->nilai !== $move->saldo_nilai
                        || $balance->harga_pokok_rata_rata !== $move->harga_pokok_rata_rata) {
                        throw new DomainException('Persediaan sudah berubah setelah penjualan sehingga pembatalan tidak aman.');
                    }
                    $prior = $db->query('SELECT harga_pokok_rata_rata FROM mutasi_persediaan
                        WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan<? ORDER BY id_mutasi_persediaan DESC LIMIT 1 FOR UPDATE',
                        array($company, $move->id_produk, $move->id_mutasi_persediaan));
                    $this->ok($prior);
                    if (!$prior->row()) {
                        throw new DomainException('Riwayat persediaan sebelum penjualan tidak ditemukan.');
                    }
                    $quantity = $this->quantity_units($balance->jumlah) + $this->quantity_units($move->jumlah);
                    $value = $this->cents($balance->nilai) + $this->cents($move->nilai);
                    $quantity_value = $this->quantity($quantity);
                    $value_money = $this->money($value);
                    $average = $prior->row()->harga_pokok_rata_rata;
                    $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $move->id_produk)
                        ->update('saldo_persediaan', array('jumlah' => $quantity_value, 'nilai' => $value_money,
                            'harga_pokok_rata_rata' => $average)));
                    $this->ok($db->insert('mutasi_persediaan', array(
                        'id_perusahaan' => $company, 'id_transaksi' => $transaction->id_transaksi,
                        'id_detail_transaksi' => $move->id_detail_transaksi, 'id_produk' => $move->id_produk,
                        'jenis_mutasi' => 'pembalik_keluar', 'jumlah' => $move->jumlah, 'nilai' => $move->nilai,
                        'saldo_jumlah' => $quantity_value, 'saldo_nilai' => $value_money,
                        'harga_pokok_rata_rata' => $average
                    )));
                }
            });
    }

    private function write_draft($id, $company, $data, $db)
    {
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi',
            array('jatuh_tempo' => $data['jatuh_tempo'], 'cara_pembayaran' => $data['cara_pembayaran'],
                'id_pelanggan' => $data['id_pelanggan'], 'id_akun_kas' => $data['id_akun_kas'],
                'referensi' => $data['referensi'], 'deskripsi' => $data['deskripsi'],
                'total_sebelum_pajak' => $data['total_sebelum_pajak'], 'total_pajak' => $data['total_pajak'],
                'total' => $data['total'])));
        $this->write_details($id, $company, $data, $db);
    }

    private function write_details($id, $company, $data, $db)
    {
        foreach ($data['details'] as $row) {
            $this->ok($db->insert('detail_transaksi', array(
                'id_perusahaan' => $company, 'id_transaksi' => $id,
                'id_produk' => $row['product']->id_produk, 'deskripsi' => $row['product']->nama_produk,
                'jumlah' => $row['jumlah'], 'harga' => $row['harga'], 'diskon' => $row['diskon'],
                'mode_pajak' => $row['mode_pajak'], 'id_pajak' => $row['id_pajak'],
                'tarif_pajak' => $row['tarif_pajak'], 'nilai_sebelum_pajak' => $row['net'],
                'nilai_pajak' => $row['tax_amount'], 'subtotal' => $row['subtotal']
            )));
        }
    }

    private function validate($company, $date, $data)
    {
        if (!is_array($data) || !isset($data['id_pelanggan'], $data['cara_pembayaran'], $data['details'])
            || !is_array($data['details']) || !$data['details'] || count($data['details']) > 200) {
            throw new InvalidArgumentException('Data penjualan harus memuat pelanggan dan 1 sampai 200 rincian.');
        }
        $this->id($data['id_pelanggan']);
        $customer = $this->ci->db->query('SELECT id_pelanggan FROM pelanggan
            WHERE id_perusahaan=? AND id_pelanggan=? AND aktif=1 FOR UPDATE',
            array($company, $data['id_pelanggan']));
        $this->ok($customer);
        if (!$customer->row()) {
            throw new DomainException('Pelanggan tidak ditemukan atau tidak aktif.');
        }
        if (!in_array($data['cara_pembayaran'], array('tunai', 'kredit'), true)) {
            throw new InvalidArgumentException('Cara pembayaran tidak valid.');
        }
        $due = isset($data['jatuh_tempo']) ? $data['jatuh_tempo'] : '';
        if ($data['cara_pembayaran'] === 'kredit') {
            $this->date($due);
            if ($due < $date) {
                throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal penjualan.');
            }
        } else {
            $due = null;
        }
        $cash = null;
        if ($data['cara_pembayaran'] === 'tunai') {
            $this->id(isset($data['id_akun_kas']) ? $data['id_akun_kas'] : null);
            $cash = (int) $data['id_akun_kas'];
            $this->account($company, $cash, '1100');
        }
        if ((isset($data['referensi']) && !is_string($data['referensi']))
            || (isset($data['deskripsi']) && !is_string($data['deskripsi']))) {
            throw new InvalidArgumentException('Referensi atau keterangan tidak valid.');
        }
        $reference = isset($data['referensi']) ? trim($data['referensi']) : '';
        $description = isset($data['deskripsi']) ? trim($data['deskripsi']) : '';
        if ($this->length($reference) > 100 || $this->length($description) > 255) {
            throw new InvalidArgumentException('Referensi maksimal 100 karakter dan keterangan maksimal 255 karakter.');
        }

        $details = array();
        $total_net = 0;
        $total_tax = 0;
        $total = 0;
        foreach ($data['details'] as $row) {
            if (!is_array($row) || !isset($row['id_produk'], $row['jumlah'], $row['harga'], $row['diskon'], $row['mode_pajak'])) {
                throw new InvalidArgumentException('Rincian penjualan tidak lengkap.');
            }
            $this->id($row['id_produk']);
            $product = $this->product($company, $row['id_produk']);
            $quantity = $this->decimal($row['jumlah'], 4, 14, false);
            $price = $this->decimal($row['harga'], 2, 13, true);
            $discount = $this->decimal($row['diskon'], 2, 13, true);
            $mode = $row['mode_pajak'];
            if (!in_array($mode, array('tidak_dikenakan', 'termasuk', 'belum_termasuk'), true)) {
                throw new InvalidArgumentException('Mode pajak tidak valid.');
            }
            $tax_id = isset($row['id_pajak']) && $row['id_pajak'] !== '' ? $row['id_pajak'] : null;
            $tax = null;
            if ($mode === 'tidak_dikenakan') {
                if ($tax_id !== null) {
                    throw new DomainException('Pilih mode tanpa pajak atau pilih metode pajak.');
                }
                $rate = '0.0000';
            } else {
                $this->id($tax_id);
                $tax = $this->tax($company, $tax_id);
                $rate = $tax->tarif_persen;
            }
            $amounts = $this->calculate($quantity, $price, $discount, $mode, $rate);
            $total_net = $this->add_cents($total_net, $amounts['net_cents']);
            $total_tax = $this->add_cents($total_tax, $amounts['tax_cents']);
            $total = $this->add_cents($total, $amounts['total_cents']);
            $details[] = array(
                'product' => $product, 'jumlah' => $quantity, 'harga' => $price, 'diskon' => $discount,
                'mode_pajak' => $mode, 'id_pajak' => $tax ? (int) $tax->id_pajak : null,
                'tarif_pajak' => $rate, 'net' => $this->money($amounts['net_cents']),
                'tax_amount' => $this->money($amounts['tax_cents']),
                'subtotal' => $this->money($amounts['total_cents'])
            );
        }
        if ($total <= 0) {
            throw new DomainException('Total penjualan harus lebih besar dari nol.');
        }
        return array(
            'id_pelanggan' => (int) $data['id_pelanggan'],
            'cara_pembayaran' => $data['cara_pembayaran'], 'jatuh_tempo' => $due,
            'id_akun_kas' => $cash, 'referensi' => $reference === '' ? null : $reference,
            'deskripsi' => $description === '' ? 'Penjualan' : $description, 'details' => $details,
            'total_sebelum_pajak' => $this->money($total_net), 'total_pajak' => $this->money($total_tax),
            'total' => $this->money($total)
        );
    }

    private function calculate($quantity, $price, $discount, $mode, $rate)
    {
        $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(18,4)) * CAST(? AS DECIMAL(15,2)), 2) AS CHAR) AS gross',
            array($quantity, $price));
        $this->ok($query);
        $gross = $this->cents($query->row()->gross);
        $discount_cents = $this->cents($discount);
        if ($discount_cents > $gross) {
            throw new DomainException('Diskon tidak boleh melebihi harga total pada baris tersebut.');
        }
        $base = $gross - $discount_cents;
        if ($mode === 'tidak_dikenakan' || $this->rate_units($rate) === 0) {
            return array('net_cents' => $base, 'tax_cents' => 0, 'total_cents' => $base);
        }
        if ($mode === 'termasuk') {
            $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) /
                (1 + CAST(? AS DECIMAL(7,4)) / 100), 2) AS CHAR) AS net', array($this->money($base), $rate));
            $this->ok($query);
            $net = $this->cents($query->row()->net);
            return array('net_cents' => $net, 'tax_cents' => $base - $net, 'total_cents' => $base);
        }
        $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) *
            CAST(? AS DECIMAL(7,4)) / 100, 2) AS CHAR) AS tax', array($this->money($base), $rate));
        $this->ok($query);
        $tax = $this->cents($query->row()->tax);
        return array('net_cents' => $base, 'tax_cents' => $tax,
            'total_cents' => $this->add_cents($base, $tax));
    }

    private function product($company, $id)
    {
        $query = $this->ci->db->query('SELECT id_produk, nama_produk, tipe_produk, aktif, id_akun_pendapatan,
            id_akun_persediaan, id_akun_beban_pokok FROM produk WHERE id_perusahaan=? AND id_produk=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $product = $query->row();
        if (!$product || !$product->aktif) {
            throw new DomainException('Produk tidak ditemukan atau tidak aktif.');
        }
        return $product;
    }

    private function tax($company, $id)
    {
        $query = $this->ci->db->query('SELECT * FROM pajak WHERE id_perusahaan=? AND id_pajak=? AND aktif=1 FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $tax = $query->row();
        if (!$tax) {
            throw new DomainException('Pajak tidak ditemukan atau tidak aktif.');
        }
        return $tax;
    }

    private function setting($company, $key, $root)
    {
        $query = $this->ci->db->query('SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan=? FOR UPDATE', array($company, $key));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengaturan akun ' . $key . ' belum tersedia.');
        }
        $account = $query->row()->id_akun;
        $this->account($company, $account, $root);
        return $account;
    }

    private function account($company, $id, $root)
    {
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account) {
            throw new DomainException('Akun transaksi tidak ditemukan.');
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
            if ($account->kode_akun === $root) {
                $found = true;
            }
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
        if (!$found) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
    }

    private function add_journal(&$journal, $account, $debit, $credit)
    {
        if ($debit === 0 && $credit === 0) {
            return;
        }
        $key = (string) $account;
        if (!isset($journal[$key])) {
            $journal[$key] = array('id_akun' => $account, 'debit_cents' => 0, 'credit_cents' => 0);
        }
        $journal[$key]['debit_cents'] = $this->add_cents($journal[$key]['debit_cents'], $debit);
        $journal[$key]['credit_cents'] = $this->add_cents($journal[$key]['credit_cents'], $credit);
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

    private function decimal($value, $scale, $digits, $allow_zero)
    {
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,' . $scale . '})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Nominal atau jumlah desimal tidak valid.');
        }
        $value = (string) $value;
        $parts = explode('.', $value);
        $fraction = isset($parts[1]) ? rtrim($parts[1], '0') : '';
        if (strlen($fraction) > $scale || (!$allow_zero && (float) $value <= 0)) {
            throw new DomainException('Jumlah harus lebih besar dari nol dan sesuai skala.');
        }
        if ($scale === 2) {
            return $this->money($this->cents($value));
        }
        $fraction = isset($parts[1]) ? substr($parts[1], 0, $scale) : '';
        return $parts[0] . '.' . str_pad($fraction, $scale, '0');
    }

    private function cents($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount)) {
            throw new DomainException('Nominal transaksi melebihi batas database.');
        }
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }

    private function add_cents($left, $right)
    {
        if ($right < 0 || $left > 999999999999999 - $right) {
            throw new DomainException('Total transaksi melebihi batas database.');
        }
        return $left + $right;
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function quantity_units($quantity)
    {
        $parts = explode('.', (string) $quantity);
        return (int) $parts[0] * 10000 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 4, '0');
    }

    private function quantity($units)
    {
        return intdiv($units, 10000) . '.' . str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function multiply_quantity_money($quantity, $unit_price)
    {
        $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(18,4)) *
            CAST(? AS DECIMAL(19,6)), 2) AS CHAR) AS amount', array($quantity, $unit_price));
        $this->ok($query);
        return $this->cents($query->row()->amount);
    }

    private function rate_units($rate)
    {
        $parts = explode('.', (string) $rate);
        return (int) $parts[0] * 10000 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 4, '0');
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database penjualan gagal.');
        }
    }
}
