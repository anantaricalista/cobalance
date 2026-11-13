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
        $this->ci->load->library('aset_tetap_service');
    }

    public function get()
    {
        $p = $this->company();
        $db = $this->ci->db;
        $q = $db->query('SELECT ' . Lampiran_service::KOLOM_TRANSAKSI . ', s.id_akun_penyeimbang FROM saldo_awal s JOIN transaksi t
            ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi WHERE s.id_perusahaan=?', array($p));
        $this->ok($q);
        $t = $q->row();
        $result = array('transaksi' => $t ?: null, 'penyeimbang' => $t ? $t->id_akun_penyeimbang : null,
            'akun' => array(), 'piutang' => array(), 'utang' => array(), 'stok' => array(), 'aset' => array(),
            'koreksi' => array());
        if (!$t) {
            return $result;
        }
        $q = $db->query('SELECT k.id_saldo_awal_koreksi,k.alasan,k.id_pengguna,k.dibuat_pada,
            COALESCE(u.nama_lengkap, CONCAT(\'Pengguna #\', k.id_pengguna)) AS pengguna
            FROM saldo_awal_koreksi k LEFT JOIN pengguna u ON u.id_perusahaan=k.id_perusahaan
                AND u.id_pengguna=k.id_pengguna
            WHERE k.id_perusahaan=? AND k.id_transaksi=? ORDER BY k.id_saldo_awal_koreksi DESC',
            array($p, $t->id_transaksi));
        $this->ok($q);
        $result['koreksi'] = $q->result_array();
        $q = $db->query('SELECT id_akun,debit,kredit FROM detail_penyesuaian WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_detail_penyesuaian', array($p, $t->id_transaksi));
        $this->ok($q);
        $result['akun'] = $q->result_array();
        $q = $db->query("SELECT jenis_transaksi,id_pelanggan,id_pemasok,referensi,jatuh_tempo,total FROM transaksi
            WHERE id_perusahaan=? AND id_transaksi_asal=? AND status='diposting'
            AND jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang') ORDER BY id_transaksi", array($p, $t->id_transaksi));
        $this->ok($q);
        foreach ($q->result() as $row) {
            $type = $row->jenis_transaksi === 'saldo_awal_piutang' ? 'piutang' : 'utang';
            $result[$type][] = array('id_pihak' => $type === 'piutang' ? $row->id_pelanggan : $row->id_pemasok,
                'nomor' => $row->referensi, 'jatuh_tempo' => $row->jatuh_tempo, 'jumlah' => $row->total);
        }
        $q = $db->query('SELECT id_detail_transaksi,id_produk,jumlah,subtotal AS nilai FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? ORDER BY id_detail_transaksi', array($p, $t->id_transaksi));
        $this->ok($q);
        $result['stok'] = $q->result_array();
        $q = $db->query('SELECT id_aset_tetap,kode_aset,nama_aset,tanggal_perolehan,tanggal_mulai_penyusutan,
            nilai_perolehan,nilai_residu,umur_manfaat_bulan,id_akun_aset,id_akun_akumulasi_penyusutan,
            id_akun_beban_penyusutan,akumulasi_penyusutan_awal FROM aset_tetap
            WHERE id_perusahaan=? AND id_transaksi_perolehan=? ORDER BY id_aset_tetap', array($p, $t->id_transaksi));
        $this->ok($q);
        $result['aset'] = $q->result_array();
        return $result;
    }

    public function simpan($tanggal, $data)
    {
        $this->date($tanggal);
        return $this->ci->transaksi_service->atomic(function ($p, $user) use ($tanggal, $data) {
            $db = $this->ci->db;
            $q = $db->query('SELECT t.* FROM saldo_awal s JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan
                AND t.id_transaksi=s.id_transaksi WHERE s.id_perusahaan=? FOR UPDATE', array($p));
            $this->ok($q);
            $t = $q->row();
            if ($t && $t->status !== 'draft') {
                throw new DomainException('Saldo awal yang sudah diposting atau dibatalkan tidak dapat diubah.');
            }
            if ($t) {
                $this->ok($db->where('id_perusahaan', $p)->where('id_transaksi_perolehan', $t->id_transaksi)->delete('aset_tetap'));
            }
            $data = $this->validate($p, $data, $tanggal);
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
            foreach ($data['aset'] as $row) {
                $this->ci->aset_tetap_service->simpan_aset_saldo_awal($p, $id, $row);
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
            $data = $this->validate($p, $data, $t->tanggal, true);
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
            foreach ($data['aset'] as $row) {
                $journal[] = array('id_akun' => $row['id_akun_aset'], 'debit' => $row['nilai_perolehan'], 'kredit' => '0');
                if ($this->number($row['akumulasi_penyusutan_awal'], 2, 13) > 0) {
                    $journal[] = array('id_akun' => $row['id_akun_akumulasi_penyusutan'], 'debit' => '0', 'kredit' => $row['akumulasi_penyusutan_awal']);
                }
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
            $q = $db->query("SELECT a.id_aset_tetap FROM aset_tetap a
                WHERE a.id_perusahaan=? AND a.id_transaksi_perolehan=?
                AND (EXISTS (SELECT 1 FROM penyusutan_aset s WHERE s.id_perusahaan=a.id_perusahaan AND s.id_aset_tetap=a.id_aset_tetap)
                OR EXISTS (SELECT 1 FROM pelepasan_aset p WHERE p.id_perusahaan=a.id_perusahaan AND p.id_aset_tetap=a.id_aset_tetap))
                LIMIT 1 FOR UPDATE",
                array($p, $t->id_transaksi));
            $this->ok($q);
            if ($q->row()) {
                throw new DomainException('Saldo awal tidak dapat dibatalkan karena aset sudah digunakan dalam penyusutan atau pelepasan.');
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

    public function dampak_koreksi($data)
    {
        $this->require_admin();
        $company = $this->company();
        $opening = $this->posted_opening($company);
        $this->assert_assets_owned($company, $opening->id_transaksi, $data);
        $clean = $this->validate($company, $data, $opening->tanggal, true, true);
        return $this->correction_impact($company, $opening->id_transaksi, $clean);
    }

    public function koreksi($data, $reason, $confirm_open_period, $baseline)
    {
        $this->require_admin();
        if (!is_string($reason) || trim($reason) === '' || strlen(trim($reason)) > 1000) {
            throw new InvalidArgumentException('Alasan koreksi wajib diisi, maksimal 1000 karakter.');
        }
        if (!$confirm_open_period) {
            throw new DomainException('Konfirmasi bahwa periode terkait terbuka wajib diberikan.');
        }
        if (!is_string($baseline) || !preg_match('/^[a-f0-9]{64}$/D', $baseline)) {
            throw new InvalidArgumentException('Pratinjau koreksi tidak valid; muat ulang halaman.');
        }
        return $this->ci->transaksi_service->atomic(function ($company, $user) use ($data, $reason, $baseline) {
            $db = $this->ci->db;
            $this->require_admin();
            $opening = $this->posted_opening($company, true);
            $before = $this->get();
            $this->assert_assets_owned($company, $opening->id_transaksi, $data);
            $clean = $this->validate($company, $data, $opening->tanggal, true, true);
            $impact = $this->correction_impact($company, $opening->id_transaksi, $clean);
            if (!hash_equals($baseline, $impact['baseline'])) {
                throw new DomainException('Saldo awal atau transaksi terkait berubah setelah pratinjau. Muat ulang halaman dan tinjau dampak terbaru.');
            }
            $beforeJson = json_encode($before);
            $afterJson = json_encode($clean);
            if ($beforeJson === false || $afterJson === false) {
                throw new RuntimeException('Gagal membuat catatan audit koreksi.');
            }
            $impact['penyusutan_sebelum'] = $this->asset_history_snapshot($company, $opening->id_transaksi);
            $stockProducts = array();
            foreach (array_merge($before['stok'], $clean['stok']) as $row) {
                $stockProducts[$row['id_produk']] = (int) $row['id_produk'];
            }
            $impact['mutasi_stok_sebelum'] = $this->inventory_history_snapshot($company, array_values($stockProducts));

            $this->replace_account_details($company, $opening->id_transaksi, $clean['akun']);
            $this->replace_invoices($company, $opening, $clean);
            $this->replace_inventory($company, $opening, $clean['stok']);
            $this->replace_assets($company, $opening, $clean['aset']);
            $this->recalculate_asset_depreciation($company, $opening, $before['aset'], $clean['aset']);
            $impact['penyusutan_sesudah'] = $this->asset_history_snapshot($company, $opening->id_transaksi);
            $impact['mutasi_stok_sesudah'] = $this->inventory_history_snapshot($company, array_values($stockProducts));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $opening->id_transaksi)
                ->update('saldo_awal', array('id_akun_penyeimbang' => $clean['penyeimbang'])));

            $journal = $this->build_opening_journal($company, $opening->id_transaksi, $clean);
            $this->replace_opening_journal($company, $opening->id_transaksi, $journal);

            $after = $this->get();
            $afterJson = json_encode($after);
            if ($afterJson === false) {
                throw new RuntimeException('Gagal membuat catatan audit koreksi.');
            }
            $impactJson = json_encode($impact);
            if ($impactJson === false) {
                throw new RuntimeException('Gagal membuat catatan audit koreksi.');
            }
            $this->ok($db->insert('saldo_awal_koreksi', array(
                'id_perusahaan' => $company,
                'id_transaksi' => $opening->id_transaksi,
                'alasan' => trim($reason),
                'data_sebelum' => $beforeJson,
                'data_sesudah' => $afterJson,
                'dampak' => $impactJson,
                'id_pengguna' => $user
            )));
            return array('status' => true);
        });
    }

    private function correction_impact($company, $openingId, $data)
    {
        $db = $this->ci->db;
        $old = $this->get();
        $impactedMoves = 0;
        $products = array();
        foreach (array_merge($old['stok'], $data['stok']) as $row) {
            $products[$row['id_produk']] = $row;
        }
        foreach ($products as $row) {
            $query = $db->query('SELECT COUNT(*) AS jumlah FROM mutasi_persediaan
                WHERE id_perusahaan=? AND id_produk=? AND id_transaksi<>?',
                array($company, $row['id_produk'], $openingId));
            $this->ok($query);
            $impactedMoves += (int) $query->row()->jumlah;
        }
        $impactedDepreciation = 0;
        $assets = array();
        foreach (array_merge($old['aset'], $data['aset']) as $row) {
            if (empty($row['id_aset_tetap'])) {
                continue;
            }
            $assets[$row['id_aset_tetap']] = $row;
        }
        foreach ($assets as $row) {
            $query = $db->query('SELECT COUNT(*) AS jumlah FROM penyusutan_aset
                WHERE id_perusahaan=? AND id_aset_tetap=?',
                array($company, $row['id_aset_tetap']));
            $this->ok($query);
            $impactedDepreciation += (int) $query->row()->jumlah;
        }
        $query = $db->query("SELECT COUNT(*) AS jumlah FROM transaksi
            WHERE id_perusahaan=? AND id_transaksi_asal=? AND jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang')
            AND status='diposting'", array($company, $openingId));
        $this->ok($query);
        $beforeTotals = $this->opening_totals($old);
        $afterTotals = $this->opening_totals($data);
        $baseline = json_encode(array(
            'saldo_awal' => $old,
            'jumlah_mutasi_stok' => $impactedMoves,
            'jumlah_periode_penyusutan' => $impactedDepreciation,
            'jumlah_faktur' => (int) $query->row()->jumlah
        ));
        if ($baseline === false) {
            throw new RuntimeException('Gagal membuat sidik data saldo awal.');
        }
        return array(
            'baseline' => hash('sha256', $baseline),
            'jurnal_asli_diperbarui' => true,
            'tanggal_jurnal' => $old['transaksi']->tanggal,
            'ringkasan' => array(
                'Debit sebelum: Rp ' . $beforeTotals['debit'] . '; sesudah: Rp ' . $afterTotals['debit'],
                'Kredit sebelum: Rp ' . $beforeTotals['kredit'] . '; sesudah: Rp ' . $afterTotals['kredit'],
                'Rincian akun: ' . count($old['akun']) . ' menjadi ' . count($data['akun']),
                'Faktur piutang: ' . count($old['piutang']) . ' menjadi ' . count($data['piutang']),
                'Faktur utang: ' . count($old['utang']) . ' menjadi ' . count($data['utang']),
                'Rincian stok: ' . count($old['stok']) . ' menjadi ' . count($data['stok']),
                'Aset: ' . count($old['aset']) . ' menjadi ' . count($data['aset'])
            ),
            'jumlah_mutasi_stok_terdampak' => $impactedMoves,
            'jumlah_periode_penyusutan_tercatat' => $impactedDepreciation,
            'jumlah_faktur_saldo_awal' => (int) $query->row()->jumlah,
            'peringatan' => array(
                'Jurnal saldo awal pada tanggal aslinya akan diperbarui; laporan historis yang memakai jurnal tersebut dapat berubah.',
                'Jurnal HPP transaksi stok berikutnya tidak dihitung ulang. Jadwal dan jurnal penyusutan dihitung ulang; aset yang sudah dilepas tidak dapat dikoreksi melalui alur ini.',
                'Saldo faktur yang sudah dialokasikan tidak boleh dikoreksi hingga lebih kecil dari pembayaran atau retur yang tercatat.'
            )
        );
    }

    private function opening_totals($data)
    {
        $debit = 0;
        $credit = 0;
        foreach ($data['akun'] as $row) {
            $debit += $this->number($row['debit'], 2, 13);
            $credit += $this->number($row['kredit'], 2, 13);
        }
        foreach ($data['piutang'] as $row) {
            $debit += $this->number($row['jumlah'], 2, 13);
        }
        foreach ($data['utang'] as $row) {
            $credit += $this->number($row['jumlah'], 2, 13);
        }
        foreach ($data['stok'] as $row) {
            $debit += $this->number($row['nilai'], 2, 13);
        }
        foreach ($data['aset'] as $row) {
            $debit += $this->number($row['nilai_perolehan'], 2, 13);
            $credit += $this->number($row['akumulasi_penyusutan_awal'], 2, 13);
        }
        if ($debit > $credit) {
            $credit += $debit - $credit;
        } elseif ($credit > $debit) {
            $debit += $credit - $debit;
        }
        return array('debit' => $this->money($debit), 'kredit' => $this->money($credit));
    }

    private function assert_assets_owned($company, $openingId, $data)
    {
        if (!isset($data['aset']) || !is_array($data['aset'])) {
            return;
        }
        foreach ($data['aset'] as $row) {
            if (empty($row['id_aset_tetap'])) {
                continue;
            }
            $query = $this->ci->db->query('SELECT id_aset_tetap FROM aset_tetap
                WHERE id_perusahaan=? AND id_transaksi_perolehan=? AND id_aset_tetap=? FOR UPDATE',
                array($company, $openingId, $row['id_aset_tetap']));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Aset yang dikoreksi bukan bagian dari saldo awal ini.');
            }
        }
    }

    private function posted_opening($company, $lock = false)
    {
        $sql = 'SELECT t.* FROM saldo_awal s JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan
            AND t.id_transaksi=s.id_transaksi WHERE s.id_perusahaan=?';
        if ($lock) {
            $sql .= ' FOR UPDATE';
        }
        $query = $this->ci->db->query($sql, array($company));
        $this->ok($query);
        $opening = $query->row();
        if (!$opening || $opening->jenis_transaksi !== 'saldo_awal' || $opening->status !== 'diposting') {
            throw new DomainException('Hanya saldo awal yang sudah diposting yang dapat dikoreksi.');
        }
        return $opening;
    }

    private function require_admin()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        $query = $this->ci->db->query('SELECT role FROM pengguna
            WHERE id_perusahaan=? AND id_pengguna=?', array($company, $user));
        $this->ok($query);
        if (!$query->row() || $query->row()->role !== 'admin') {
            throw new DomainException('Koreksi saldo awal hanya dapat dilakukan oleh Admin Perusahaan.');
        }
    }

    private function replace_account_details($company, $openingId, $rows)
    {
        $db = $this->ci->db;
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $openingId)
            ->delete('detail_penyesuaian'));
        foreach ($rows as $row) {
            $this->ok($db->insert('detail_penyesuaian', array(
                'id_perusahaan' => $company, 'id_transaksi' => $openingId,
                'id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit']
            )));
        }
    }

    private function replace_invoices($company, $opening, $data)
    {
        $db = $this->ci->db;
        $query = $db->query("SELECT * FROM transaksi WHERE id_perusahaan=? AND id_transaksi_asal=?
            AND jenis_transaksi IN ('saldo_awal_piutang','saldo_awal_utang') AND status='diposting' FOR UPDATE",
            array($company, $opening->id_transaksi));
        $this->ok($query);
        $existing = array();
        foreach ($query->result() as $row) {
            $type = $row->jenis_transaksi === 'saldo_awal_piutang' ? 'piutang' : 'utang';
            $party = $type === 'piutang' ? $row->id_pelanggan : $row->id_pemasok;
            $key = $type . ':' . $party . ':' . strtolower(trim((string) $row->referensi));
            $existing[$key] = $row;
        }
        $retained = array();
        foreach (array('piutang', 'utang') as $type) {
            foreach ($data[$type] as $row) {
                $key = $type . ':' . $row['id_pihak'] . ':' . strtolower(trim($row['nomor']));
                if (isset($existing[$key])) {
                    $invoice = $existing[$key];
                    $this->ensure_invoice_reference_unique($company, $type, $row, $invoice->id_transaksi);
                    $this->ensure_invoice_not_overallocated($company, $invoice->id_transaksi, $row['jumlah'], $type);
                    $partyField = $type === 'piutang' ? 'id_pelanggan' : 'id_pemasok';
                    $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $invoice->id_transaksi)
                        ->update('transaksi', array($partyField => $row['id_pihak'], 'referensi' => trim($row['nomor']),
                            'jatuh_tempo' => $row['jatuh_tempo'], 'total' => $row['jumlah'],
                            'total_sebelum_pajak' => $row['jumlah'])));
                    $retained[$invoice->id_transaksi] = true;
                } else {
                    $this->ensure_invoice_reference_unique($company, $type, $row, null);
                    $partyField = $type === 'piutang' ? 'id_pelanggan' : 'id_pemasok';
                    $uuid = $db->query('SELECT UUID() AS uuid');
                    $this->ok($uuid);
                    $this->ok($db->insert('transaksi', array(
                        'id_perusahaan' => $company, 'jenis_transaksi' => 'saldo_awal_' . $type,
                        'nomor_transaksi' => 'DRAFT-' . $uuid->row()->uuid, 'tanggal' => $opening->tanggal,
                        'jatuh_tempo' => $row['jatuh_tempo'], 'referensi' => trim($row['nomor']),
                        'total' => $row['jumlah'], 'total_sebelum_pajak' => $row['jumlah'],
                        'status' => 'diposting', 'id_transaksi_asal' => $opening->id_transaksi,
                        'id_pengguna_pembuat' => $this->ci->session->userdata('id_pengguna'),
                        'id_pengguna_posting' => $this->ci->session->userdata('id_pengguna'),
                        'waktu_posting' => date('Y-m-d H:i:s'),
                        $partyField => $row['id_pihak']
                    )));
                    $id = $db->insert_id();
                    $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                        ->update('transaksi', array('nomor_transaksi' => 'TRX-' . str_pad((string) $id, 10, '0', STR_PAD_LEFT))));
                    $retained[$id] = true;
                }
            }
        }
        foreach ($existing as $invoice) {
            if (isset($retained[$invoice->id_transaksi])) {
                continue;
            }
            $query = $db->query("SELECT id_alokasi_transaksi FROM alokasi_transaksi a
                JOIN transaksi p ON p.id_perusahaan=a.id_perusahaan AND p.id_transaksi=a.id_transaksi
                WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND p.status='diposting' LIMIT 1 FOR UPDATE",
                array($company, $invoice->id_transaksi));
            $this->ok($query);
            $child = $db->query('SELECT id_transaksi FROM transaksi WHERE id_perusahaan=?
                AND id_transaksi_asal=? LIMIT 1 FOR UPDATE', array($company, $invoice->id_transaksi));
            $this->ok($child);
            if ($query->row() || $child->row()) {
                throw new DomainException('Faktur yang sudah dipakai tidak dapat dihapus dari koreksi saldo awal.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $invoice->id_transaksi)
                ->update('transaksi', array('status' => 'dibatalkan',
                    'id_pengguna_pembatal' => $this->ci->session->userdata('id_pengguna'),
                    'waktu_pembatalan' => date('Y-m-d H:i:s'),
                    'alasan_pembatalan' => 'Dihapus melalui koreksi saldo awal')));
        }
    }

    private function ensure_invoice_reference_unique($company, $type, $row, $excludeId)
    {
        $partyField = $type === 'piutang' ? 'id_pelanggan' : 'id_pemasok';
        $eligible = $type === 'piutang'
            ? "(jenis_transaksi='saldo_awal_piutang' OR
                (jenis_transaksi IN ('penjualan','pelepasan_aset') AND cara_pembayaran='kredit'))"
            : "(jenis_transaksi='saldo_awal_utang' OR
                (jenis_transaksi IN ('pembelian','perolehan_aset') AND cara_pembayaran='kredit'))";
        $sql = "SELECT id_transaksi FROM transaksi WHERE id_perusahaan=? AND $eligible
            AND $partyField=? AND LOWER(TRIM(referensi))=LOWER(TRIM(?)) AND status<>'dibatalkan'";
        $params = array($company, $row['id_pihak'], $row['nomor']);
        if ($excludeId !== null) {
            $sql .= ' AND id_transaksi<>?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $query = $this->ci->db->query($sql, $params);
        $this->ok($query);
        if ($query->row()) {
            throw new DomainException('Nomor faktur pihak yang sama sudah digunakan pada saldo awal lain.');
        }
    }

    private function ensure_invoice_not_overallocated($company, $invoiceId, $newAmount, $type)
    {
        $paymentType = $type === 'piutang' ? 'penerimaan_piutang' : 'pembayaran_utang';
        $returnType = $type === 'piutang' ? 'retur_penjualan' : 'retur_pembelian';
        $query = $this->ci->db->query("SELECT
            COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a JOIN transaksi p
                ON p.id_perusahaan=a.id_perusahaan AND p.id_transaksi=a.id_transaksi
                WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND p.status='diposting'
                AND p.jenis_transaksi=?),0) AS paid,
            COALESCE((SELECT SUM(total) FROM transaksi WHERE id_perusahaan=? AND id_transaksi_asal=?
                AND jenis_transaksi=? AND status='diposting'),0) AS returned",
            array($company, $invoiceId, $paymentType, $company, $invoiceId, $returnType));
        $this->ok($query);
        $used = $this->number($query->row()->paid, 2, 13) + $this->number($query->row()->returned, 2, 13);
        if ($used > $this->number($newAmount, 2, 13)) {
            throw new DomainException('Nominal faktur tidak boleh kurang dari pembayaran dan retur yang sudah diposting.');
        }
    }

    private function replace_inventory($company, $opening, $rows)
    {
        $db = $this->ci->db;
        $query = $db->query('SELECT * FROM detail_transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
            array($company, $opening->id_transaksi));
        $this->ok($query);
        $old = array();
        foreach ($query->result() as $row) {
            if ($row->id_produk) {
                $old[$row->id_produk] = $row;
            }
        }
        $next = array();
        foreach ($rows as $row) {
            $next[$row['id_produk']] = $row;
        }
        foreach ($old as $productId => $previous) {
            if (!isset($next[$productId])) {
                $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan
                    WHERE id_perusahaan=? AND id_produk=? AND id_transaksi<>? LIMIT 1 FOR UPDATE',
                    array($company, $productId, $opening->id_transaksi));
                $this->ok($later);
                if ($later->row()) {
                    throw new DomainException('Produk stok awal yang sudah memiliki mutasi lanjutan tidak dapat dihapus; koreksi jumlah dan nilainya.');
                }
                $movement = $db->query("SELECT * FROM mutasi_persediaan WHERE id_perusahaan=?
                    AND id_transaksi=? AND id_produk=? AND jenis_mutasi='masuk' FOR UPDATE",
                    array($company, $opening->id_transaksi, $productId));
                $this->ok($movement);
                $balance = $db->query('SELECT * FROM saldo_persediaan
                    WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($company, $productId));
                $this->ok($balance);
                if (!$movement->row() || !$balance->row()
                    || $balance->row()->jumlah !== $movement->row()->saldo_jumlah
                    || $balance->row()->nilai !== $movement->row()->saldo_nilai
                    || $balance->row()->harga_pokok_rata_rata !== $movement->row()->harga_pokok_rata_rata) {
                    throw new DomainException('Saldo berjalan tidak sesuai dengan riwayat stok; koreksi penghapusan dibatalkan.');
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $opening->id_transaksi)
                    ->where('id_detail_transaksi', $previous->id_detail_transaksi)->delete('detail_transaksi'));
                $this->ok($db->where('id_perusahaan', $company)->where('id_mutasi_persediaan', $movement->row()->id_mutasi_persediaan)
                    ->delete('mutasi_persediaan'));
                $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $productId)
                    ->update('saldo_persediaan', array('jumlah' => '0.0000', 'nilai' => '0.00',
                        'harga_pokok_rata_rata' => '0.000000')));
                continue;
            }
            $current = $db->query('SELECT * FROM saldo_persediaan
                WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($company, $productId));
            $this->ok($current);
            $oldUnits = $this->number($previous->jumlah, 4, 14);
            $newUnits = $this->number($next[$productId]['jumlah'], 4, 14);
            $oldValue = $this->number($previous->subtotal, 2, 13);
            $newValue = $this->number($next[$productId]['nilai'], 2, 13);
            $this->ok($db->where('id_perusahaan', $company)->where('id_detail_transaksi', $previous->id_detail_transaksi)
                ->update('detail_transaksi', array('jumlah' => $next[$productId]['jumlah'],
                    'nilai_sebelum_pajak' => $next[$productId]['nilai'], 'subtotal' => $next[$productId]['nilai'])));
            $move = $db->query("SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_transaksi=?
                AND id_produk=? AND jenis_mutasi='masuk' FOR UPDATE",
                array($company, $opening->id_transaksi, $productId));
            $this->ok($move);
            if (!$move->row()) {
                throw new DomainException('Mutasi stok awal tidak ditemukan; koreksi stok dihentikan.');
            }
            $openingAverage = $this->average($newValue, $newUnits);
            $this->ok($db->where('id_perusahaan', $company)->where('id_mutasi_persediaan', $move->row()->id_mutasi_persediaan)
                ->update('mutasi_persediaan', array('jumlah' => $next[$productId]['jumlah'], 'nilai' => $next[$productId]['nilai'],
                    'saldo_jumlah' => $next[$productId]['jumlah'], 'saldo_nilai' => $next[$productId]['nilai'],
                    'harga_pokok_rata_rata' => $openingAverage)));
            $later = $db->query('SELECT * FROM mutasi_persediaan WHERE id_perusahaan=? AND id_produk=?
                AND id_mutasi_persediaan>? ORDER BY id_mutasi_persediaan FOR UPDATE',
                array($company, $productId, $move->row()->id_mutasi_persediaan));
            $this->ok($later);
            $units = $newUnits;
            $value = $newValue;
            $latestMove = $move->row();
            foreach ($later->result() as $movement) {
                $sign = in_array($movement->jenis_mutasi, array('masuk', 'pembalik_keluar'), true) ? 1
                    : (in_array($movement->jenis_mutasi, array('keluar', 'pembalik_masuk'), true) ? -1 : 0);
                $units += $sign * $this->number($movement->jumlah, 4, 14);
                $value += $sign * $this->number($movement->nilai, 2, 13);
                if ($units < 0 || $units > 999999999999999999 || $value < 0
                    || $value > 999999999999999 || ($units === 0 && $value !== 0)) {
                    throw new DomainException('Koreksi menghasilkan saldo stok lanjutan yang tidak valid.');
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_mutasi_persediaan', $movement->id_mutasi_persediaan)
                    ->update('mutasi_persediaan', array('saldo_jumlah' => $this->quantity($units),
                        'saldo_nilai' => $this->money($value), 'harga_pokok_rata_rata' => $this->average($value, $units))));
                $latestMove = $movement;
            }
            if (!$current->row() || $current->row()->jumlah !== $latestMove->saldo_jumlah
                || $current->row()->nilai !== $latestMove->saldo_nilai
                || $current->row()->harga_pokok_rata_rata !== $latestMove->harga_pokok_rata_rata) {
                throw new DomainException('Saldo berjalan tidak sesuai dengan riwayat stok; koreksi dibatalkan.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $productId)
                ->update('saldo_persediaan', array('jumlah' => $this->quantity($units),
                    'nilai' => $this->money($value), 'harga_pokok_rata_rata' => $this->average($value, $units))));
        }
        foreach ($next as $productId => $row) {
            if (isset($old[$productId])) {
                continue;
            }
            $later = $db->query('SELECT id_mutasi_persediaan FROM mutasi_persediaan
                WHERE id_perusahaan=? AND id_produk=? LIMIT 1 FOR UPDATE', array($company, $productId));
            $this->ok($later);
            if ($later->row()) {
                throw new DomainException('Stok awal produk baru tidak dapat ditambahkan setelah produk memiliki riwayat mutasi.');
            }
            $product = $this->product($company, $productId);
            $account = $product->id_akun_persediaan ?: $this->setting($company, 'persediaan', '1700');
            $this->account($company, $account, '1700');
            $this->ok($db->insert('detail_transaksi', array('id_perusahaan' => $company,
                'id_transaksi' => $opening->id_transaksi, 'id_produk' => $productId, 'id_akun' => $account,
                'deskripsi' => 'Persediaan awal', 'jumlah' => $row['jumlah'],
                'nilai_sebelum_pajak' => $row['nilai'], 'subtotal' => $row['nilai'])));
            $detailId = $db->insert_id();
            $average = $this->average($this->number($row['nilai'], 2, 13), $this->number($row['jumlah'], 4, 14));
            $this->ok($db->insert('mutasi_persediaan', array('id_perusahaan' => $company,
                'id_transaksi' => $opening->id_transaksi, 'id_detail_transaksi' => $detailId,
                'id_produk' => $productId, 'jenis_mutasi' => 'masuk', 'jumlah' => $row['jumlah'],
                'nilai' => $row['nilai'], 'saldo_jumlah' => $row['jumlah'], 'saldo_nilai' => $row['nilai'],
                'harga_pokok_rata_rata' => $average)));
            $balance = $db->query('SELECT id_saldo_persediaan FROM saldo_persediaan
                WHERE id_perusahaan=? AND id_produk=? FOR UPDATE', array($company, $productId));
            $this->ok($balance);
            $values = array('jumlah' => $row['jumlah'], 'nilai' => $row['nilai'], 'harga_pokok_rata_rata' => $average);
            if ($balance->row()) {
                if ($balance->row()->jumlah !== '0.0000' || $balance->row()->nilai !== '0.00'
                    || $balance->row()->harga_pokok_rata_rata !== '0.000000') {
                    throw new DomainException('Saldo produk tanpa riwayat mutasi sudah memiliki jumlah atau nilai; koreksi stok dihentikan.');
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_produk', $productId)->update('saldo_persediaan', $values));
            } else {
                $this->ok($db->insert('saldo_persediaan', $values + array('id_perusahaan' => $company, 'id_produk' => $productId)));
            }
        }
    }

    private function replace_assets($company, $opening, $rows)
    {
        $db = $this->ci->db;
        $query = $db->query('SELECT * FROM aset_tetap WHERE id_perusahaan=? AND id_transaksi_perolehan=? FOR UPDATE',
            array($company, $opening->id_transaksi));
        $this->ok($query);
        $existing = array();
        foreach ($query->result() as $row) {
            $existing[$row->id_aset_tetap] = $row;
        }
        $retained = array();
        foreach ($rows as $row) {
            $values = array(
                'id_perusahaan' => $company, 'kode_aset' => $row['kode_aset'], 'nama_aset' => $row['nama_aset'],
                'tanggal_perolehan' => $row['tanggal_perolehan'], 'tanggal_mulai_penyusutan' => $row['tanggal_mulai_penyusutan'],
                'nilai_perolehan' => $row['nilai_perolehan'], 'nilai_residu' => $row['nilai_residu'],
                'umur_manfaat_bulan' => $row['umur_manfaat_bulan'], 'metode_penyusutan' => 'garis_lurus',
                'id_akun_aset' => $row['id_akun_aset'], 'id_akun_akumulasi_penyusutan' => $row['id_akun_akumulasi_penyusutan'],
                'id_akun_beban_penyusutan' => $row['id_akun_beban_penyusutan'],
                'akumulasi_penyusutan_awal' => $row['akumulasi_penyusutan_awal'],
                'id_transaksi_perolehan' => $opening->id_transaksi, 'status' => 'aktif'
            );
            if (!empty($row['id_aset_tetap']) && isset($existing[$row['id_aset_tetap']])) {
                $id = $row['id_aset_tetap'];
                $values['status'] = $existing[$id]->status;
                $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $id)->update('aset_tetap', $values));
            } else {
                $this->ok($db->insert('aset_tetap', $values));
                $id = $db->insert_id();
            }
            $retained[$id] = true;
        }
        foreach ($existing as $id => $asset) {
            if (isset($retained[$id])) {
                continue;
            }
            $used = $db->query('SELECT id_penyusutan_aset FROM penyusutan_aset
                WHERE id_perusahaan=? AND id_aset_tetap=? LIMIT 1 FOR UPDATE', array($company, $id));
            $this->ok($used);
            $disposed = $db->query('SELECT id_pelepasan_aset FROM pelepasan_aset
                WHERE id_perusahaan=? AND id_aset_tetap=? LIMIT 1 FOR UPDATE', array($company, $id));
            $this->ok($disposed);
            if ($used->row() || $disposed->row()) {
                throw new DomainException('Aset yang sudah disusutkan atau dilepas tidak dapat dihapus dari koreksi.');
            }
            $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $id)->delete('aset_tetap'));
        }
    }

    private function build_opening_journal($company, $openingId, $data)
    {
        $journal = array();
        foreach ($data['akun'] as $row) {
            $journal[] = array('id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit']);
        }
        foreach ($data['aset'] as $row) {
            $journal[] = array('id_akun' => $row['id_akun_aset'], 'debit' => $row['nilai_perolehan'], 'kredit' => '0');
            if ($this->number($row['akumulasi_penyusutan_awal'], 2, 13) > 0) {
                $journal[] = array('id_akun' => $row['id_akun_akumulasi_penyusutan'], 'debit' => '0',
                    'kredit' => $row['akumulasi_penyusutan_awal']);
            }
        }
        foreach (array('piutang', 'utang') as $type) {
            if (!$data[$type]) {
                continue;
            }
            $account = $this->setting($company, $type === 'piutang' ? 'piutang_usaha' : 'utang_usaha',
                $type === 'piutang' ? '1200' : '2100');
            foreach ($data[$type] as $row) {
                $journal[] = array('id_akun' => $account, 'debit' => $type === 'piutang' ? $row['jumlah'] : '0',
                    'kredit' => $type === 'utang' ? $row['jumlah'] : '0');
            }
        }
        foreach ($data['stok'] as $row) {
            $product = $this->product($company, $row['id_produk']);
            $account = $product->id_akun_persediaan ?: $this->setting($company, 'persediaan', '1700');
            $this->account($company, $account, '1700');
            $this->ok($this->ci->db->where('id_perusahaan', $company)->where('id_transaksi', $openingId)
                ->where('id_produk', $row['id_produk'])->update('detail_transaksi', array('id_akun' => $account)));
            if ($this->number($row['nilai'], 2, 13) > 0) {
                $journal[] = array('id_akun' => $account, 'debit' => $row['nilai'], 'kredit' => '0');
            }
        }
        $net = 0;
        foreach ($journal as $row) {
            $debit = $this->number($row['debit'], 2, 13);
            $credit = $this->number($row['kredit'], 2, 13);
            $net += $debit - $credit;
        }
        if (abs($net) > 999999999999999) {
            throw new DomainException('Saldo penyeimbang melebihi batas.');
        }
        if ($net !== 0) {
            $journal[] = array('id_akun' => $data['penyeimbang'],
                'debit' => $net < 0 ? $this->money(-$net) : '0',
                'kredit' => $net > 0 ? $this->money($net) : '0');
        }
        if (count($journal) < 2) {
            throw new DomainException('Koreksi harus menghasilkan jurnal dengan minimal dua baris.');
        }
        $debits = 0;
        $credits = 0;
        foreach ($journal as $row) {
            $debit = $this->number($row['debit'], 2, 13);
            $credit = $this->number($row['kredit'], 2, 13);
            if (($debit === 0) === ($credit === 0)) {
                throw new DomainException('Setiap baris jurnal koreksi harus memiliki tepat satu nominal positif.');
            }
            $debits += $debit;
            $credits += $credit;
        }
        if ($debits !== $credits) {
            throw new DomainException('Jurnal saldo awal koreksi tidak seimbang.');
        }
        return $journal;
    }

    private function replace_opening_journal($company, $openingId, $rows)
    {
        $this->replace_transaction_journal($company, $openingId, $rows);
    }

    private function replace_transaction_journal($company, $transactionId, $rows)
    {
        $db = $this->ci->db;
        $query = $db->query("SELECT id_jurnal FROM transaksi_jurnal WHERE id_perusahaan=?
            AND id_transaksi=? AND jenis_jurnal='posting' FOR UPDATE", array($company, $transactionId));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Jurnal transaksi yang harus dihitung ulang tidak ditemukan.');
        }
        $journalId = $query->row()->id_jurnal;
        $this->ok($db->where('id_jurnal', $journalId)->delete('detail_jurnal'));
        foreach ($rows as $row) {
            $this->ok($db->insert('detail_jurnal', array('id_jurnal' => $journalId,
                'id_akun' => $row['id_akun'], 'debit' => $row['debit'], 'kredit' => $row['kredit'])));
        }
    }

    private function recalculate_asset_depreciation($company, $opening, $beforeAssets, $afterAssets)
    {
        $db = $this->ci->db;
        $beforeById = array();
        $afterById = array();
        foreach ($beforeAssets as $asset) {
            $beforeById[$asset['id_aset_tetap']] = $asset;
        }
        foreach ($afterAssets as $asset) {
            if (!empty($asset['id_aset_tetap'])) {
                $afterById[$asset['id_aset_tetap']] = $asset;
            }
        }
        $fields = array('tanggal_perolehan', 'tanggal_mulai_penyusutan', 'nilai_perolehan',
            'nilai_residu', 'umur_manfaat_bulan', 'id_akun_aset',
            'id_akun_akumulasi_penyusutan', 'id_akun_beban_penyusutan',
            'akumulasi_penyusutan_awal');
        $assets = $db->query('SELECT * FROM aset_tetap
            WHERE id_perusahaan=? AND id_transaksi_perolehan=? FOR UPDATE',
            array($company, $opening->id_transaksi));
        $this->ok($assets);
        $transactions = array();
        foreach ($assets->result() as $asset) {
            $id = $asset->id_aset_tetap;
            if (!isset($beforeById[$id], $afterById[$id])) {
                continue;
            }
            $changed = false;
            foreach ($fields as $field) {
                if ((string) $beforeById[$id][$field] !== (string) $afterById[$id][$field]) {
                    $changed = true;
                    break;
                }
            }
            if (!$changed) {
                continue;
            }
            $disposal = $db->query('SELECT id_pelepasan_aset FROM pelepasan_aset
                WHERE id_perusahaan=? AND id_aset_tetap=? LIMIT 1 FOR UPDATE',
                array($company, $asset->id_aset_tetap));
            $this->ok($disposal);
            if ($disposal->row()) {
                throw new DomainException('Aset yang sudah dilepas tidak dapat dikoreksi melalui saldo awal; nilai pelepasan perlu ditinjau manual.');
            }
            $base = new Aset_tetap_kalkulasi(array(
                'nilai_perolehan' => $asset->nilai_perolehan,
                'nilai_residu' => $asset->nilai_residu,
                'umur_manfaat_bulan' => $asset->umur_manfaat_bulan,
                'tanggal_mulai_penyusutan' => $asset->tanggal_mulai_penyusutan,
                'akumulasi_awal' => $asset->akumulasi_penyusutan_awal,
                'tanggal_saldo_awal' => $opening->tanggal
            ));
            $periods = $db->query('SELECT s.id_penyusutan_aset,s.id_transaksi,s.periode,s.status,t.status AS status_transaksi
                FROM penyusutan_aset s JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan
                    AND t.id_transaksi=s.id_transaksi
                WHERE s.id_perusahaan=? AND s.id_aset_tetap=? ORDER BY s.periode FOR UPDATE',
                array($company, $asset->id_aset_tetap));
            $this->ok($periods);
            foreach ($periods->result() as $period) {
                if ($period->status !== 'aktif') {
                    continue;
                }
                $amount = $base->nilai_bulan($period->periode);
                $this->ok($db->where('id_perusahaan', $company)
                    ->where('id_penyusutan_aset', $period->id_penyusutan_aset)
                    ->update('penyusutan_aset', array('nilai_penyusutan' => $amount)));
                if ($period->status_transaksi === 'diposting') {
                    $transactions[$period->id_transaksi] = true;
                }
            }
        }
        foreach (array_keys($transactions) as $transactionId) {
            $rows = $db->query("SELECT s.nilai_penyusutan,a.id_akun_beban_penyusutan,
                a.id_akun_akumulasi_penyusutan
                FROM penyusutan_aset s JOIN aset_tetap a ON a.id_perusahaan=s.id_perusahaan
                    AND a.id_aset_tetap=s.id_aset_tetap
                WHERE s.id_perusahaan=? AND s.id_transaksi=? AND s.status='aktif'
                ORDER BY s.id_aset_tetap FOR UPDATE", array($company, $transactionId));
            $this->ok($rows);
            $balances = array();
            foreach ($rows->result() as $row) {
                $amount = $this->number($row->nilai_penyusutan, 2, 13);
                if ($amount === 0) {
                    continue;
                }
                $expense = $row->id_akun_beban_penyusutan;
                $accumulated = $row->id_akun_akumulasi_penyusutan;
                $balances[$expense] = isset($balances[$expense]) ? $balances[$expense] + $amount : $amount;
                $balances[$accumulated] = isset($balances[$accumulated]) ? $balances[$accumulated] - $amount : -$amount;
            }
            $journal = array();
            foreach ($balances as $account => $amount) {
                if ($amount > 999999999999999 || $amount < -999999999999999) {
                    throw new DomainException('Nilai jurnal penyusutan hasil koreksi melebihi batas database.');
                }
                if ($amount > 0) {
                    $journal[] = array('id_akun' => $account, 'debit' => $this->money($amount), 'kredit' => '0');
                } elseif ($amount < 0) {
                    $journal[] = array('id_akun' => $account, 'debit' => '0', 'kredit' => $this->money(-$amount));
                }
            }
            if (!$journal) {
                throw new DomainException('Koreksi membuat seluruh jurnal penyusutan historis suatu periode menjadi nol; periode tersebut perlu ditinjau manual.');
            }
            $this->replace_transaction_journal($company, $transactionId, $journal);
        }
    }

    private function asset_history_snapshot($company, $openingId)
    {
        $query = $this->ci->db->query('SELECT s.id_aset_tetap,s.id_transaksi,s.periode,
            s.nilai_penyusutan,s.status,t.status AS status_transaksi
            FROM penyusutan_aset s JOIN aset_tetap a ON a.id_perusahaan=s.id_perusahaan
                AND a.id_aset_tetap=s.id_aset_tetap
            JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi
            WHERE a.id_perusahaan=? AND a.id_transaksi_perolehan=?
            ORDER BY s.id_aset_tetap,s.periode', array($company, $openingId));
        $this->ok($query);
        $snapshot = array('periode' => $query->result_array(), 'jurnal' => array());
        $ids = array();
        foreach ($snapshot['periode'] as $row) {
            if ($row['status_transaksi'] === 'diposting') {
                $ids[$row['id_transaksi']] = true;
            }
        }
        foreach (array_keys($ids) as $transactionId) {
            $journal = $this->ci->db->query("SELECT d.id_akun,d.debit,d.kredit
                FROM transaksi_jurnal tj JOIN detail_jurnal d ON d.id_jurnal=tj.id_jurnal
                WHERE tj.id_perusahaan=? AND tj.id_transaksi=? AND tj.jenis_jurnal='posting'
                ORDER BY d.id_detail", array($company, $transactionId));
            $this->ok($journal);
            $snapshot['jurnal'][$transactionId] = $journal->result_array();
        }
        return $snapshot;
    }

    private function inventory_history_snapshot($company, $productIds)
    {
        if (!$productIds) {
            return array('mutasi' => array(), 'saldo' => array());
        }
        $mutations = $this->ci->db->query('SELECT id_mutasi_persediaan,id_transaksi,id_detail_transaksi,
            id_produk,jenis_mutasi,jumlah,nilai,saldo_jumlah,saldo_nilai,harga_pokok_rata_rata
            FROM mutasi_persediaan WHERE id_perusahaan=? AND id_produk IN (' .
                implode(',', array_fill(0, count($productIds), '?')) . ')
            ORDER BY id_produk,id_mutasi_persediaan', array_merge(array($company), $productIds));
        $this->ok($mutations);
        $balances = $this->ci->db->query('SELECT id_produk,jumlah,nilai,harga_pokok_rata_rata
            FROM saldo_persediaan WHERE id_perusahaan=? AND id_produk IN (' .
                implode(',', array_fill(0, count($productIds), '?')) . ') ORDER BY id_produk',
            array_merge(array($company), $productIds));
        $this->ok($balances);
        return array('mutasi' => $mutations->result_array(), 'saldo' => $balances->result_array());
    }

    private function average($cents, $units)
    {
        if ($units === 0) {
            return '0.000000';
        }
        $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) /
            CAST(? AS DECIMAL(18,4)),6) AS CHAR) AS rata',
            array($this->money($cents), $this->quantity($units)));
        $this->ok($query);
        $average = $query->row()->rata;
        if (!is_string($average) || !preg_match('/^\d{1,13}\.\d{6}$/D', $average)) {
            throw new DomainException('Harga pokok rata-rata melebihi batas database.');
        }
        return $average;
    }

    private function quantity($units)
    {
        return intdiv($units, 10000) . '.' . str_pad((string) ($units % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function validate($p, $data, $tanggal, $stored = false, $correction = false)
    {
        if (!is_array($data) || !isset($data['penyeimbang']) || !isset($data['akun'], $data['piutang'], $data['utang'], $data['stok'], $data['aset'])) {
            throw new InvalidArgumentException('Data saldo awal tidak lengkap.');
        }
        foreach (array('akun', 'piutang', 'utang', 'stok', 'aset') as $key) {
            if (!is_array($data[$key]) || count($data[$key]) > 200) {
                throw new InvalidArgumentException('Rincian saldo awal tidak valid atau melebihi 200 baris.');
            }
        }
        if (!$data['akun'] && !$data['piutang'] && !$data['utang'] && !$data['stok'] && !$data['aset']) {
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
        $assetCodes = array();
        foreach ($data['aset'] as &$row) {
            if (!is_array($row) || ($stored && !$correction && !isset($row['id_aset_tetap']))) {
                throw new InvalidArgumentException('Rincian aset tidak valid.');
            }
            $assetId = $correction && isset($row['id_aset_tetap']) ? $row['id_aset_tetap'] : null;
            $row = $this->ci->aset_tetap_service->validasi_saldo_awal($p, $tanggal, $row,
                $assetId);
            if ($correction && $assetId !== null) {
                $row['id_aset_tetap'] = $assetId;
            }
            $code = function_exists('mb_strtolower') ? mb_strtolower($row['kode_aset'], 'UTF-8') : strtolower($row['kode_aset']);
            if (isset($assetCodes[$code])) {
                throw new DomainException('Kode aset saldo awal tidak boleh berulang.');
            }
            $assetCodes[$code] = true;
            if (isset($seen[$row['id_akun_aset']]) || isset($seen[$row['id_akun_akumulasi_penyusutan']])) {
                throw new DomainException('Akun aset dan akumulasi yang dipakai dalam rincian aset tidak boleh diisi lagi pada Saldo Akun.');
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
        $q = $this->ci->db->query("SELECT id_produk, tipe_produk, aktif, id_akun_persediaan FROM produk WHERE id_perusahaan=? AND id_produk=? FOR UPDATE", array($p, $id));
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
