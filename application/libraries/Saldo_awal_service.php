<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Saldo_awal_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
    }

    public function get()
    {
        $p = $this->company();
        $db = $this->ci->db;
        $q = $db->query('SELECT t.*, s.id_akun_penyeimbang FROM saldo_awal s JOIN transaksi t
            ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi WHERE s.id_perusahaan=?', array($p));
        $this->ok($q);
        $t = $q->row();
        $result = array('transaksi' => $t ?: null, 'penyeimbang' => $t ? $t->id_akun_penyeimbang : null,
            'akun' => array(), 'piutang' => array(), 'utang' => array(), 'stok' => array());
        if (!$t) {
            return $result;
        }
        $q = $db->query('SELECT id_akun,debit,kredit FROM detail_penyesuaian WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_detail_penyesuaian', array($p, $t->id_transaksi));
        $this->ok($q);
        $result['akun'] = $q->result_array();
        $q = $db->query("SELECT jenis_transaksi,id_pelanggan,id_pemasok,referensi,jatuh_tempo,total FROM transaksi
            WHERE id_perusahaan=? AND id_transaksi_asal=? AND jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang') ORDER BY id_transaksi", array($p, $t->id_transaksi));
        $this->ok($q);
        foreach ($q->result() as $row) {
            $type = $row->jenis_transaksi === 'saldo_awal_piutang' ? 'piutang' : 'utang';
            $result[$type][] = array('id_pihak' => $type === 'piutang' ? $row->id_pelanggan : $row->id_pemasok,
                'nomor' => $row->referensi, 'jatuh_tempo' => $row->jatuh_tempo, 'jumlah' => $row->total);
        }
        $q = $db->query('SELECT id_produk,jumlah,subtotal AS nilai FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_detail_transaksi', array($p, $t->id_transaksi));
        $this->ok($q);
        $result['stok'] = $q->result_array();
        return $result;
    }

    public function simpan($tanggal, $data)
    {
        $this->date($tanggal);
        return $this->ci->transaksi_service->atomic(function ($p, $user) use ($tanggal, $data) {
            $db = $this->ci->db;
            $data = $this->validate($p, $data);
            $q = $db->query('SELECT t.* FROM saldo_awal s JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan
                AND t.id_transaksi=s.id_transaksi WHERE s.id_perusahaan=? FOR UPDATE', array($p));
            $this->ok($q);
            $t = $q->row();
            if ($t && $t->status !== 'draft') {
                throw new DomainException('Saldo awal yang sudah diposting atau dibatalkan tidak dapat diubah.');
            }
            if (!$t) {
                $q = $db->query('SELECT UUID() AS uuid');
                $this->ok($q);
                $this->ok($db->insert('transaksi', array('id_perusahaan' => $p, 'jenis_transaksi' => 'saldo_awal',
                    'nomor_transaksi' => 'DRAFT-' . $q->row()->uuid, 'tanggal' => $tanggal,
                    'deskripsi' => 'Saldo awal', 'status' => 'draft', 'id_pengguna_pembuat' => $user)));
                $id = $db->insert_id();
                $nomor = 'TRX-' . str_pad((string) $id, 10, '0', STR_PAD_LEFT);
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi', $id)->update('transaksi', array('nomor_transaksi' => $nomor)));
                $this->ok($db->insert('saldo_awal', array('id_perusahaan' => $p, 'id_transaksi' => $id,
                    'id_akun_penyeimbang' => $data['penyeimbang'])));
            } else {
                $id = $t->id_transaksi;
                $nomor = $t->nomor_transaksi;
                $q = $db->query("SELECT id_transaksi FROM transaksi WHERE id_perusahaan=? AND id_transaksi_asal=?
                    AND (jenis_transaksi NOT IN ('saldo_awal_piutang','saldo_awal_utang') OR status<>'draft') LIMIT 1 FOR UPDATE", array($p, $id));
                $this->ok($q);
                if ($q->row()) {
                    throw new DomainException('Rincian saldo awal sudah digunakan.');
                }
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi', $id)->update('transaksi', array('tanggal' => $tanggal)));
                $this->ok($db->where('id_perusahaan', $p)->update('saldo_awal', array('id_akun_penyeimbang' => $data['penyeimbang'])));
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi', $id)->delete('detail_penyesuaian'));
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi', $id)->delete('detail_transaksi'));
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi_asal', $id)->where_in('jenis_transaksi', array('saldo_awal_piutang', 'saldo_awal_utang'))->where('status', 'draft')->delete('transaksi'));
            }
            foreach ($data['akun'] as $row) {
                $this->ok($db->insert('detail_penyesuaian', array('id_perusahaan' => $p, 'id_transaksi' => $id,
                    'id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit'])));
            }
            foreach (array('piutang', 'utang') as $type) {
                foreach ($data[$type] as $row) {
                    $q = $db->query('SELECT UUID() AS uuid');
                    $this->ok($q);
                    $child = array('id_perusahaan' => $p, 'jenis_transaksi' => 'saldo_awal_' . $type,
                        'nomor_transaksi' => 'DRAFT-' . $q->row()->uuid, 'tanggal' => $tanggal,
                        'jatuh_tempo' => $row['jatuh_tempo'], 'referensi' => $row['nomor'],
                        'total' => $row['jumlah'], 'total_sebelum_pajak' => $row['jumlah'], 'status' => 'draft',
                        'id_transaksi_asal' => $id, 'id_pengguna_pembuat' => $user,
                        $type === 'piutang' ? 'id_pelanggan' : 'id_pemasok' => $row['id_pihak']);
                    $this->ok($db->insert('transaksi', $child));
                    $child_id = $db->insert_id();
                    $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi', $child_id)
                        ->update('transaksi', array('nomor_transaksi' => 'TRX-' . str_pad((string) $child_id, 10, '0', STR_PAD_LEFT))));
                }
            }
            foreach ($data['stok'] as $row) {
                $this->ok($db->insert('detail_transaksi', array('id_perusahaan' => $p, 'id_transaksi' => $id,
                    'id_produk' => $row['id_produk'], 'deskripsi' => 'Persediaan awal', 'jumlah' => $row['jumlah'],
                    'nilai_sebelum_pajak' => $row['nilai'], 'subtotal' => $row['nilai'])));
            }
            return array('id_transaksi' => $id, 'nomor_transaksi' => $nomor);
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($t, $db) {
            $p = $t->id_perusahaan;
            $data = $this->get();
            if ($t->jenis_transaksi !== 'saldo_awal' || !$data['transaksi'] || $data['transaksi']->id_transaksi != $t->id_transaksi) {
                throw new DomainException('Transaksi saldo awal tidak ditemukan.');
            }
            $data = $this->validate($p, $data);
            $q = $db->query('SELECT id_jurnal FROM jurnal_transaksi WHERE id_perusahaan=? LIMIT 1 FOR UPDATE', array($p));
            $this->ok($q);
            if ($q->row()) {
                throw new DomainException('Jurnal perusahaan harus kosong sebelum posting saldo awal.');
            }
            $q = $db->query('SELECT id_saldo_persediaan FROM saldo_persediaan WHERE id_perusahaan=? AND (jumlah<>0 OR nilai<>0) LIMIT 1 FOR UPDATE', array($p));
            $this->ok($q);
            $m = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan WHERE id_perusahaan=? LIMIT 1 FOR UPDATE', array($p));
            $this->ok($m);
            if ($q->row() || $m->row()) {
                throw new DomainException('Persediaan harus kosong dan belum memiliki mutasi.');
            }
            $journal = array();
            foreach ($data['akun'] as $row) {
                $journal[] = array('id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit']);
            }
            foreach (array('piutang', 'utang') as $type) {
                if (!$data[$type]) {
                    continue;
                }
                $account = $this->setting($p, $type === 'piutang' ? 'piutang_usaha' : 'utang_usaha', $type === 'piutang' ? '1200' : '2100');
                foreach ($data[$type] as $row) {
                    $journal[] = array('id_akun' => $account, 'debit' => $type === 'piutang' ? $row['jumlah'] : '0',
                        'kredit' => $type === 'utang' ? $row['jumlah'] : '0');
                }
            }
            $details = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_produk FOR UPDATE', array($p, $t->id_transaksi));
            $this->ok($details);
            foreach ($details->result() as $row) {
                $product = $this->product($p, $row->id_produk);
                $account = $product->id_akun_persediaan ?: $this->setting($p, 'persediaan', '1700');
                $this->account($p, $account, '1700');
                $this->ok($db->where('id_perusahaan', $p)->where('id_detail_transaksi', $row->id_detail_transaksi)->update('detail_transaksi', array('id_akun' => $account)));
                $existing = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($p, $row->id_produk));
                $this->ok($existing);
                $avg = $db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2))/CAST(? AS DECIMAL(18,4)),6) AS CHAR) AS rata', array($row->subtotal, $row->jumlah));
                $this->ok($avg);
                $rata = $avg->row()->rata;
                if (!$rata || !preg_match('/^\d{1,13}\.\d{6}$/D', $rata)) {
                    throw new DomainException('Harga pokok rata-rata melebihi batas.');
                }
                $balance = array('jumlah' => $row->jumlah, 'nilai' => $row->subtotal, 'harga_pokok_rata_rata' => $rata);
                if ($existing->row()) {
                    $this->ok($db->where('id_perusahaan', $p)->where('id_produk', $row->id_produk)->update('saldo_persediaan', $balance));
                } else {
                    $this->ok($db->insert('saldo_persediaan', $balance + array('id_perusahaan' => $p, 'id_produk' => $row->id_produk)));
                }
                $this->ok($db->insert('mutasi_persediaan', array('id_perusahaan' => $p, 'id_transaksi' => $t->id_transaksi,
                    'id_detail_transaksi' => $row->id_detail_transaksi, 'id_produk' => $row->id_produk, 'jenis_mutasi' => 'masuk',
                    'jumlah' => $row->jumlah, 'nilai' => $row->subtotal, 'saldo_jumlah' => $row->jumlah,
                    'saldo_nilai' => $row->subtotal, 'harga_pokok_rata_rata' => $rata)));
                if ($this->number($row->subtotal, 2, 13) > 0) {
                    $journal[] = array('id_akun' => $account, 'debit' => $row->subtotal, 'kredit' => '0');
                }
            }
            $net = 0;
            foreach ($journal as $row) {
                $d = $this->number($row['debit'], 2, 13);
                $k = $this->number($row['kredit'], 2, 13);
                if (($d && $net > PHP_INT_MAX - $d) || ($k && $net < -PHP_INT_MAX + $k)) {
                    throw new DomainException('Jumlah saldo awal terlalu besar.');
                }
                $net += $d - $k;
            }
            if (abs($net) > 999999999999999) {
                throw new DomainException('Saldo penyeimbang melebihi batas.');
            }
            if ($net !== 0) {
                $journal[] = array('id_akun' => $data['penyeimbang'], 'debit' => $net < 0 ? $this->money(-$net) : '0',
                    'kredit' => $net > 0 ? $this->money($net) : '0');
            }
            $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi_asal', $t->id_transaksi)
                ->where_in('jenis_transaksi', array('saldo_awal_piutang', 'saldo_awal_utang'))->where('status', 'draft')
                ->update('transaksi', array('status' => 'diposting', 'id_pengguna_posting' => $this->ci->session->userdata('id_pengguna'), 'waktu_posting' => date('Y-m-d H:i:s'))));
            return $journal;
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($t, $db) use ($alasan) {
            $p = $t->id_perusahaan;
            $q = $db->query('SELECT id_transaksi FROM saldo_awal WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE', array($p, $t->id_transaksi));
            $this->ok($q);
            if ($t->jenis_transaksi !== 'saldo_awal' || !$q->row()) {
                throw new DomainException('Transaksi saldo awal tidak ditemukan.');
            }
            $q = $db->query('SELECT id_aset_tetap FROM aset_tetap
                WHERE id_perusahaan=? AND id_transaksi_perolehan=? LIMIT 1 FOR UPDATE',
                array($p, $t->id_transaksi));
            $this->ok($q);
            if ($q->row()) {
                throw new DomainException('Saldo awal tidak dapat dibatalkan karena terdapat aset lama yang terdaftar.');
            }
            $children = $db->query("SELECT id_transaksi FROM transaksi WHERE id_perusahaan=? AND id_transaksi_asal=? AND jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang') FOR UPDATE", array($p, $t->id_transaksi));
            $this->ok($children);
            foreach ($children->result() as $child) {
                $q = $db->query("SELECT a.id_alokasi_transaksi FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND r.status='diposting' LIMIT 1 FOR UPDATE", array($p, $child->id_transaksi));
                $this->ok($q);
                if ($q->row()) {
                    throw new DomainException('Faktur saldo awal sudah dialokasikan.');
                }
                $q = $db->query('SELECT id_transaksi FROM transaksi WHERE id_perusahaan=? AND id_transaksi_asal=? LIMIT 1 FOR UPDATE', array($p, $child->id_transaksi));
                $this->ok($q);
                if ($q->row()) {
                    throw new DomainException('Faktur saldo awal sudah digunakan oleh transaksi lain.');
                }
            }
            $moves = $db->query("SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_transaksi=? AND jenis_mutasi='masuk' ORDER BY id_produk FOR UPDATE", array($p, $t->id_transaksi));
            $this->ok($moves);
            foreach ($moves->result() as $move) {
                $balance = $db->query('SELECT * FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($p, $move->id_produk));
                $this->ok($balance);
                $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan WHERE id_perusahaan=? AND id_produk=? AND id_mutasi_persediaan>? LIMIT 1 FOR UPDATE', array($p, $move->id_produk, $move->id_mutasi_persediaan));
                $this->ok($later);
                $b = $balance->row();
                if ($later->row() || !$b || $b->jumlah !== $move->saldo_jumlah || $b->nilai !== $move->saldo_nilai || $b->harga_pokok_rata_rata !== $move->harga_pokok_rata_rata) {
                    throw new DomainException('Persediaan sudah berubah setelah saldo awal.');
                }
                $this->ok($db->where('id_perusahaan', $p)->where('id_produk', $move->id_produk)->update('saldo_persediaan',
                    array('jumlah' => '0', 'nilai' => '0', 'harga_pokok_rata_rata' => '0')));
                $this->ok($db->insert('mutasi_persediaan', array('id_perusahaan' => $p, 'id_transaksi' => $t->id_transaksi,
                    'id_detail_transaksi' => $move->id_detail_transaksi, 'id_produk' => $move->id_produk, 'jenis_mutasi' => 'pembalik_masuk',
                    'jumlah' => $move->jumlah, 'nilai' => $move->nilai, 'saldo_jumlah' => '0', 'saldo_nilai' => '0', 'harga_pokok_rata_rata' => '0')));
            }
            $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi_asal', $t->id_transaksi)
                ->where_in('jenis_transaksi', array('saldo_awal_piutang', 'saldo_awal_utang'))->where('status', 'diposting')
                ->update('transaksi', array('status' => 'dibatalkan', 'id_pengguna_pembatal' => $this->ci->session->userdata('id_pengguna'),
                    'waktu_pembatalan' => date('Y-m-d H:i:s'), 'alasan_pembatalan' => trim($alasan))));
        });
    }

    private function validate($p, $data)
    {
        if (!is_array($data) || !isset($data['penyeimbang']) || !isset($data['akun'], $data['piutang'], $data['utang'], $data['stok'])) {
            throw new InvalidArgumentException('Data saldo awal tidak lengkap.');
        }
        foreach (array('akun', 'piutang', 'utang', 'stok') as $key) {
            if (!is_array($data[$key]) || count($data[$key]) > 200) {
                throw new InvalidArgumentException('Rincian saldo awal tidak valid atau melebihi 200 baris.');
            }
        }
        if (!$data['akun'] && !$data['piutang'] && !$data['utang'] && !$data['stok']) {
            throw new DomainException('Masukkan setidaknya satu rincian saldo awal.');
        }
        $this->id($data['penyeimbang']);
        $data['penyeimbang'] = (int) $data['penyeimbang'];
        $equity = $this->account($p, $data['penyeimbang']);
        if ($equity->tipe_akun !== 'Ekuitas') {
            throw new DomainException('Akun penyeimbang harus akun ekuitas.');
        }
        $seen = array();
        foreach ($data['akun'] as &$row) {
            if (!is_array($row) || !isset($row['id_akun'], $row['debit'], $row['kredit'])) {
                throw new InvalidArgumentException('Rincian akun tidak valid.');
            }
            $this->id($row['id_akun']);
            $row['id_akun'] = (int) $row['id_akun'];
            if (isset($seen[$row['id_akun']])) {
                throw new DomainException('Akun saldo awal tidak boleh berulang.');
            }
            $seen[$row['id_akun']] = true;
            $this->account($p, $row['id_akun'], null, true);
            $d = $this->number($row['debit'], 2, 13);
            $k = $this->number($row['kredit'], 2, 13);
            if (($d === 0) === ($k === 0)) {
                throw new DomainException('Setiap akun harus memiliki tepat satu nominal positif.');
            }
        }
        unset($row);
        foreach (array('piutang', 'utang') as $type) {
            $seen = array();
            $table = $type === 'piutang' ? 'pelanggan' : 'pemasok';
            $field = 'id_' . $table;
            foreach ($data[$type] as &$row) {
                if (!is_array($row) || !isset($row['id_pihak'], $row['nomor'], $row['jatuh_tempo'], $row['jumlah'])) {
                    throw new InvalidArgumentException('Rincian faktur tidak lengkap.');
                }
                $this->id($row['id_pihak']);
                $row['id_pihak'] = (int) $row['id_pihak'];
                if (!is_string($row['nomor']) || trim($row['nomor']) === '' || strlen($row['nomor']) > 100) {
                    throw new DomainException('Nomor faktur wajib diisi, maksimal 100 karakter.');
                }
                $this->date($row['jatuh_tempo']);
                if (!$this->number($row['jumlah'], 2, 13)) {
                    throw new DomainException('Jumlah faktur harus positif.');
                }
                $nomor = trim($row['nomor']);
                $key = $row['id_pihak'] . "\0" . (function_exists('mb_strtolower') ? mb_strtolower($nomor, 'UTF-8') : strtolower($nomor));
                if (isset($seen[$key])) {
                    throw new DomainException('Nomor faktur pihak yang sama tidak boleh berulang.');
                }
                $seen[$key] = true;
                $q = $this->ci->db->query("SELECT $field FROM $table WHERE id_perusahaan=? AND $field=? AND aktif=1 FOR UPDATE", array($p, $row['id_pihak']));
                $this->ok($q);
                if (!$q->row()) {
                    throw new DomainException('Pelanggan atau pemasok tidak ditemukan atau tidak aktif.');
                }
            }
            unset($row);
        }
        $seen = array();
        foreach ($data['stok'] as &$row) {
            if (!is_array($row) || !isset($row['id_produk'], $row['jumlah'], $row['nilai'])) {
                throw new InvalidArgumentException('Rincian persediaan tidak lengkap.');
            }
            $this->id($row['id_produk']);
            $row['id_produk'] = (int) $row['id_produk'];
            if (isset($seen[$row['id_produk']])) {
                throw new DomainException('Produk saldo awal tidak boleh berulang.');
            }
            $seen[$row['id_produk']] = true;
            $this->product($p, $row['id_produk']);
            if (!$this->number($row['jumlah'], 4, 14)) {
                throw new DomainException('Jumlah persediaan harus positif.');
            }
            $this->number($row['nilai'], 2, 13);
        }
        unset($row);
        return $data;
    }

    private function setting($p, $key, $root)
    {
        $q = $this->ci->db->query('SELECT id_akun FROM pengaturan_akun WHERE id_perusahaan=? AND jenis_pengaturan=? FOR UPDATE', array($p, $key));
        $this->ok($q);
        if (!$q->row()) {
            throw new DomainException('Pengaturan akun ' . $key . ' belum tersedia.');
        }
        $id = $q->row()->id_akun;
        $this->account($p, $id, $root);
        return $id;
    }

    private function product($p, $id)
    {
        $q = $this->ci->db->query("SELECT * FROM produk WHERE id_perusahaan=? AND id_produk=? FOR UPDATE", array($p, $id));
        $this->ok($q);
        $row = $q->row();
        if (!$row || $row->tipe_produk !== 'barang' || !$row->aktif) {
            throw new DomainException('Produk persediaan tidak ditemukan, tidak aktif, atau bukan barang.');
        }
        return $row;
    }

    private function account($p, $id, $root = null, $exclude_control = false)
    {
        $db = $this->ci->db;
        $q = $db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE', array($p, $id));
        $this->ok($q);
        $account = $q->row();
        if (!$account) {
            throw new DomainException('Akun tidak ditemukan.');
        }
        $q = $db->query('SELECT id_akun FROM akun WHERE id_perusahaan=? AND id_akun_induk=? LIMIT 1 FOR UPDATE', array($p, $id));
        $this->ok($q);
        if ($q->row()) {
            throw new DomainException('Akun induk tidak dapat digunakan.');
        }
        $current = $account;
        $seen = array();
        $found = false;
        while ($current) {
            if (isset($seen[$current->id_akun])) {
                throw new DomainException('Hierarki akun tidak valid.');
            }
            $seen[$current->id_akun] = true;
            if ($current->kode_akun === $root) {
                $found = true;
            }
            if ($exclude_control && in_array($current->kode_akun, array('1200', '2100', '1700'), true)) {
                throw new DomainException('Akun kontrol harus diisi melalui rincian piutang, utang, atau stok.');
            }
            if (!$current->id_akun_induk) {
                break;
            }
            $q = $db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE', array($p, $current->id_akun_induk));
            $this->ok($q);
            $current = $q->row();
            if (!$current) {
                throw new DomainException('Induk akun tidak ditemukan.');
            }
        }
        if ($root !== null && !$found) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
        return $account;
    }

    private function company()
    {
        $p = $this->ci->session->userdata('id_perusahaan');
        $u = $this->ci->session->userdata('id_pengguna');
        $this->id($p);
        $this->id($u);
        $q = $this->ci->db->query('SELECT id_pengguna FROM pengguna WHERE id_perusahaan=? AND id_pengguna=?', array($p, $u));
        $this->ok($q);
        if (!$q->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return $p;
    }

    private function id($id)
    {
        if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id) || (int) $id <= 0 || (int) $id > 2147483647) {
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

    private function number($value, $scale, $digits)
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,' . $scale . '})?$/D', (string) $value)) {
            throw new DomainException('Angka desimal tidak valid.');
        }
        $parts = explode('.', (string) $value);
        return (int) $parts[0] * (10 ** $scale) + (int) str_pad(isset($parts[1]) ? $parts[1] : '', $scale, '0');
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database saldo awal gagal.');
        }
    }
}
