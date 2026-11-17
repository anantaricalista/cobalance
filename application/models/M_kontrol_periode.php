<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_kontrol_periode extends CI_Model
{
    public function schema_ready()
    {
        $tables = $this->db->query("SELECT COUNT(*) AS jumlah FROM information_schema.tables
            WHERE table_schema=DATABASE() AND table_name IN
            ('periode_akuntansi','periode_akuntansi_riwayat','akun_kas_bank',
             'rekonsiliasi_bank','mutasi_rekening_bank')");
        $names = array();
        foreach (array('transaksi', 'jurnal', 'detail_jurnal', 'transaksi_jurnal',
            'mutasi_persediaan', 'penyusutan_aset', 'pelepasan_aset', 'aset_tetap') as $table) {
            foreach (array('bi', 'bu', 'bd') as $event) {
                $names[] = 'cobalance_' . $table . '_' . $event;
            }
        }
        $triggers = $this->db->query('SELECT COUNT(*) AS jumlah FROM information_schema.triggers
            WHERE trigger_schema=DATABASE() AND trigger_name IN (' . implode(',', array_fill(0, count($names), '?')) . ')', $names);
        return $tables && $triggers && (int) $tables->row()->jumlah === 5
            && (int) $triggers->row()->jumlah === count($names);
    }

    private function company()
    {
        $company = $this->session->userdata('id_perusahaan');
        if (!is_numeric($company) || (int) $company < 1) {
            throw new DomainException('Sesi perusahaan tidak valid.');
        }
        return (int) $company;
    }

    public function valid_period($period)
    {
        if (!is_string($period) || !preg_match('/^[1-9]\d{3}-(?:0[1-9]|1[0-2])-01$/D', $period)) {
            throw new InvalidArgumentException('Periode harus berupa bulan yang valid.');
        }
        $date = DateTime::createFromFormat('!Y-m-d', $period);
        if (!$date || $date->format('Y-m-d') !== $period) {
            throw new InvalidArgumentException('Periode harus berupa bulan yang valid.');
        }
        return $date;
    }

    public function periods($year)
    {
        $company = $this->company();
        $query = $this->db->query('SELECT p.*, u.nama_lengkap FROM periode_akuntansi p
            LEFT JOIN pengguna u ON u.id_perusahaan=p.id_perusahaan AND u.id_pengguna=p.id_pengguna_ubah
            WHERE p.id_perusahaan=? AND YEAR(p.periode)=? ORDER BY p.periode',
            array($company, (int) $year));
        if (!$query) {
            throw new RuntimeException('Gagal membaca periode akuntansi.');
        }
        return $query->result();
    }

    public function history($period)
    {
        $this->valid_period($period);
        $query = $this->db->query('SELECT h.*, u.nama_lengkap FROM periode_akuntansi_riwayat h
            LEFT JOIN pengguna u ON u.id_perusahaan=h.id_perusahaan AND u.id_pengguna=h.id_pengguna
            WHERE h.id_perusahaan=? AND h.periode=? ORDER BY h.id_riwayat DESC',
            array($this->company(), $period));
        if (!$query) {
            throw new RuntimeException('Gagal membaca riwayat periode.');
        }
        return $query->result();
    }

    public function latest_closed()
    {
        $query = $this->db->query("SELECT MAX(periode) AS periode FROM periode_akuntansi
            WHERE id_perusahaan=? AND status='ditutup'", array($this->company()));
        if (!$query) {
            throw new RuntimeException('Gagal membaca periode tertutup terakhir.');
        }
        return $query->row()->periode;
    }

    public function cash_accounts()
    {
        $company = $this->company();
        $query = $this->db->query("WITH RECURSIVE cash_tree AS (
            SELECT id_akun, kode_akun, nama_akun, id_akun_induk, 0 AS depth
            FROM akun WHERE id_perusahaan=? AND kode_akun='1100'
            UNION ALL
            SELECT a.id_akun, a.kode_akun, a.nama_akun, a.id_akun_induk, c.depth+1
            FROM akun a JOIN cash_tree c ON a.id_akun_induk=c.id_akun
            WHERE a.id_perusahaan=?
        )
        SELECT c.*, k.jenis, k.aktif,
            EXISTS(SELECT 1 FROM akun child WHERE child.id_perusahaan=? AND child.id_akun_induk=c.id_akun) AS has_children
        FROM cash_tree c LEFT JOIN akun_kas_bank k ON k.id_perusahaan=? AND k.id_akun=c.id_akun
        ORDER BY c.kode_akun, c.id_akun", array($company, $company, $company, $company));
        if (!$query) {
            throw new RuntimeException('Gagal membaca akun kas dan bank.');
        }
        return $query->result();
    }

    public function set_cash_account($id, $type, $active)
    {
        $id = $this->valid_id($id, 'Akun kas/bank');
        if (!in_array($type, array('rekening_bank', 'kas_tunai'), true)) {
            throw new InvalidArgumentException('Jenis akun kas/bank tidak valid.');
        }
        $company = $this->company();
        return $this->atomic(function () use ($company, $id, $type, $active) {
            $account = $this->leaf_cash_account($company, $id, true);
            if (!$account) {
                throw new DomainException('Pilih akun rincian pada kelompok kas/bank.');
            }
            $existing = $this->query('SELECT jenis,aktif FROM akun_kas_bank WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
                array($company, $id))->row();
            if ($existing && ($existing->jenis !== $type || (int) $existing->aktif !== (int) (bool) $active)) {
                $closed = $this->query("SELECT COUNT(*) AS n FROM periode_akuntansi
                    WHERE id_perusahaan=? AND status='ditutup'", array($company))->row();
                if ((int) $closed->n > 0) {
                    throw new DomainException('Buka kembali seluruh periode tertutup sebelum mengubah klasifikasi akun.');
                }
            }
            $query = $this->db->query('INSERT INTO akun_kas_bank (id_perusahaan,id_akun,jenis,aktif)
                VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE jenis=VALUES(jenis),aktif=VALUES(aktif)',
                array($company, $id, $type, $active ? 1 : 0));
            if (!$query) {
                throw new RuntimeException('Gagal menyimpan klasifikasi akun kas/bank.');
            }
        });
    }

    public function get_bank_reconciliations($period)
    {
        $this->valid_period($period);
        $query = $this->db->query('SELECT k.id_akun,k.jenis,a.kode_akun,a.nama_akun,
                r.id_rekonsiliasi,r.status,r.saldo_awal,r.saldo_akhir,
                (SELECT COUNT(*) FROM mutasi_rekening_bank m WHERE m.id_rekonsiliasi=r.id_rekonsiliasi) AS jumlah_mutasi,
                (SELECT COUNT(*) FROM mutasi_rekening_bank m WHERE m.id_rekonsiliasi=r.id_rekonsiliasi AND m.id_detail_jurnal IS NOT NULL) AS cocok
            FROM akun_kas_bank k JOIN akun a ON a.id_perusahaan=k.id_perusahaan AND a.id_akun=k.id_akun
            LEFT JOIN rekonsiliasi_bank r ON r.id_perusahaan=k.id_perusahaan AND r.id_akun=k.id_akun AND r.periode=?
            WHERE k.id_perusahaan=? AND k.aktif=1 AND k.jenis=\'rekening_bank\'
            ORDER BY a.kode_akun,a.id_akun', array($period, $this->company()));
        if (!$query) {
            throw new RuntimeException('Gagal membaca rekonsiliasi bank.');
        }
        return $query->result();
    }

    public function bank_reconciliation($period, $accountId)
    {
        $this->valid_period($period);
        $accountId = $this->valid_id($accountId, 'Akun bank');
        $company = $this->company();
        $header = $this->db->query('SELECT * FROM rekonsiliasi_bank
            WHERE id_perusahaan=? AND id_akun=? AND periode=?',
            array($company, $accountId, $period));
        if (!$header) {
            throw new RuntimeException('Gagal membaca rekening koran.');
        }
        if (!$header->row()) {
            return null;
        }
        $reconciliation = $header->row();
        $lines = $this->db->query('SELECT m.*,d.debit,d.kredit,j.tanggal AS tanggal_jurnal,
                j.deskripsi AS deskripsi_jurnal,j.referensi
            FROM mutasi_rekening_bank m
            LEFT JOIN detail_jurnal d ON d.id_detail=m.id_detail_jurnal
            LEFT JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
            WHERE m.id_rekonsiliasi=? ORDER BY m.tanggal,m.id_mutasi_rekening',
            array($reconciliation->id_rekonsiliasi));
        if (!$lines) {
            throw new RuntimeException('Gagal membaca rincian rekening koran.');
        }
        $reconciliation->mutasi = $lines->result();
        $reconciliation->jurnal_tersedia = $this->available_bank_entries($reconciliation);
        return $reconciliation;
    }

    public function is_closed($period)
    {
        $this->valid_period($period);
        $query = $this->db->query("SELECT status FROM periode_akuntansi
            WHERE id_perusahaan=? AND periode=?", array($this->company(), $period));
        if (!$query) {
            throw new RuntimeException('Gagal memeriksa status periode.');
        }
        return $query->row() && $query->row()->status === 'ditutup';
    }

    public function save_bank_reconciliation($period, $accountId, $opening, $closing, $lines)
    {
        $date = $this->valid_period($period);
        $this->assert_open($this->company(), $period);
        $accountId = $this->valid_id($accountId, 'Akun bank');
        $opening = $this->valid_money($opening, 'Saldo awal rekening');
        $closing = $this->valid_money($closing, 'Saldo akhir rekening');
        if (!is_array($lines) || count($lines) > 200) {
            throw new InvalidArgumentException('Daftar mutasi rekening tidak valid.');
        }
        $end = $date->format('Y-m-t');
        $company = $this->company();
        $this->atomic(function () use ($company, $period, $end, $accountId, $opening, $closing, $lines) {
            $account = $this->db->query("SELECT id_akun FROM akun_kas_bank
                WHERE id_perusahaan=? AND id_akun=? AND jenis='rekening_bank' AND aktif=1 FOR UPDATE",
                array($company, $accountId));
            if (!$account || !$account->row() || !$this->leaf_cash_account($company, $accountId)) {
                throw new DomainException('Akun bank aktif tidak ditemukan.');
            }
            $this->assert_open($company, $period);
            $query = $this->db->query('SELECT * FROM rekonsiliasi_bank
                WHERE id_perusahaan=? AND id_akun=? AND periode=? FOR UPDATE',
                array($company, $accountId, $period));
            if (!$query) {
                throw new RuntimeException('Gagal mengunci rekonsiliasi bank.');
            }
            if ($query->row()) {
                $id = (int) $query->row()->id_rekonsiliasi;
                $ok = $this->db->where('id_rekonsiliasi', $id)->update('rekonsiliasi_bank', array(
                    'saldo_awal' => $opening, 'saldo_akhir' => $closing, 'status' => 'draft',
                    'id_pengguna_ubah' => (int) $this->session->userdata('id_pengguna'),
                    'diperbarui_pada' => date('Y-m-d H:i:s')
                ));
                if (!$ok || !$this->db->where('id_rekonsiliasi', $id)->delete('mutasi_rekening_bank')) {
                    throw new RuntimeException('Gagal memperbarui rekening koran.');
                }
            } else {
                $ok = $this->db->insert('rekonsiliasi_bank', array(
                    'id_perusahaan' => $company, 'id_akun' => $accountId, 'periode' => $period,
                    'saldo_awal' => $opening, 'saldo_akhir' => $closing, 'status' => 'draft',
                    'id_pengguna_ubah' => (int) $this->session->userdata('id_pengguna'),
                    'diperbarui_pada' => date('Y-m-d H:i:s')
                ));
                if (!$ok) {
                    throw new RuntimeException('Gagal menyimpan rekonsiliasi bank.');
                }
                $id = (int) $this->db->insert_id();
            }
            foreach ($lines as $line) {
                if (is_array($line) && empty($line['tanggal']) && empty($line['keterangan'])
                    && empty($line['arah']) && empty($line['jumlah'])) {
                    continue;
                }
                if (!is_array($line) || !isset($line['tanggal'], $line['keterangan'], $line['arah'], $line['jumlah'])
                    || !is_string($line['tanggal']) || $line['tanggal'] < $period || $line['tanggal'] > $end
                    || !in_array($line['arah'], array('masuk', 'keluar'), true)
                    || !is_string($line['keterangan']) || trim($line['keterangan']) === ''
                    || strlen(trim($line['keterangan'])) > 255) {
                    throw new InvalidArgumentException('Rincian mutasi rekening tidak valid.');
                }
                $this->valid_date($line['tanggal']);
                $amount = $this->valid_money($line['jumlah'], 'Jumlah mutasi');
                if ($this->cents($amount) <= 0) {
                    throw new InvalidArgumentException('Jumlah mutasi rekening harus lebih dari nol.');
                }
                if (!$this->db->insert('mutasi_rekening_bank', array(
                    'id_rekonsiliasi' => $id, 'tanggal' => $line['tanggal'],
                    'keterangan' => trim($line['keterangan']), 'arah' => $line['arah'], 'jumlah' => $amount
                ))) {
                    throw new RuntimeException('Gagal menyimpan mutasi rekening.');
                }
            }
        });
    }

    public function match_bank_line($period, $lineId, $detailId)
    {
        $this->valid_period($period);
        $company = $this->company();
        $lineId = $this->valid_id($lineId, 'Mutasi rekening');
        $detailId = $this->valid_id($detailId, 'Rincian jurnal');
        $this->atomic(function () use ($period, $company, $lineId, $detailId) {
            $query = $this->db->query('SELECT m.*,r.id_akun,r.periode,r.status AS status_rekonsiliasi
                FROM mutasi_rekening_bank m JOIN rekonsiliasi_bank r ON r.id_rekonsiliasi=m.id_rekonsiliasi
                WHERE r.id_perusahaan=? AND r.periode=? AND m.id_mutasi_rekening=? FOR UPDATE',
                array($company, $period, $lineId));
            if (!$query || !$query->row()) {
                throw new DomainException('Mutasi rekening tidak ditemukan.');
            }
            $line = $query->row();
            if ($line->status_rekonsiliasi !== 'draft') {
                throw new DomainException('Rekonsiliasi yang selesai tidak dapat diubah.');
            }
            $this->assert_open($company, $period);
            $entry = $this->db->query('SELECT d.id_detail,d.debit,d.kredit,j.tanggal
                FROM detail_jurnal d JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
                WHERE j.id_perusahaan=? AND d.id_akun=? AND d.id_detail=?
                    AND j.tanggal BETWEEN ? AND ? FOR UPDATE',
                array($company, $line->id_akun, $detailId, $period, date('Y-m-t', strtotime($period))));
            if (!$entry || !$entry->row()) {
                throw new DomainException('Rincian jurnal tidak cocok dengan akun atau periode bank.');
            }
            $entry = $entry->row();
            $signed = $this->cents($entry->debit === null ? '0' : $entry->debit)
                - $this->cents($entry->kredit === null ? '0' : $entry->kredit);
            $statement = $this->cents($line->jumlah) * ($line->arah === 'masuk' ? 1 : -1);
            if ($signed !== $statement) {
                throw new DomainException('Nilai mutasi rekening dan jurnal harus sama arah serta jumlahnya.');
            }
            if (!$this->db->where('id_mutasi_rekening', $lineId)->update('mutasi_rekening_bank', array('id_detail_jurnal' => $detailId))) {
                throw new RuntimeException('Gagal mencocokkan mutasi rekening.');
            }
        });
    }

    public function unmatch_bank_line($period, $lineId)
    {
        $this->valid_period($period);
        $company = $this->company();
        $lineId = $this->valid_id($lineId, 'Mutasi rekening');
        $this->atomic(function () use ($company, $period, $lineId) {
            $this->assert_open($company, $period);
            $query = $this->db->query('UPDATE mutasi_rekening_bank m
                JOIN rekonsiliasi_bank r ON r.id_rekonsiliasi=m.id_rekonsiliasi
                SET m.id_detail_jurnal=NULL WHERE r.id_perusahaan=? AND r.periode=?
                    AND r.status=\'draft\' AND m.id_mutasi_rekening=?',
                array($company, $period, $lineId));
            if (!$query || $this->db->affected_rows() !== 1) {
                throw new DomainException('Mutasi tidak dapat dilepas dari jurnal.');
            }
        });
    }

    public function complete_bank_reconciliation($period, $accountId)
    {
        $this->valid_period($period);
        $company = $this->company();
        $accountId = $this->valid_id($accountId, 'Akun bank');
        $this->atomic(function () use ($period, $company, $accountId) {
            $this->assert_open($company, $period);
            $reconciliation = $this->db->query('SELECT * FROM rekonsiliasi_bank
                WHERE id_perusahaan=? AND id_akun=? AND periode=? FOR UPDATE',
                array($company, $accountId, $period));
            if (!$reconciliation || !$reconciliation->row()) {
                throw new DomainException('Simpan rekening koran terlebih dahulu.');
            }
            $reconciliation = $reconciliation->row();
            if ($reconciliation->status !== 'draft') {
                throw new DomainException('Rekonsiliasi bank sudah selesai.');
            }
            $this->validate_bank_reconciliation($company, $period, $accountId, $reconciliation);
            $updated = $this->db->where('id_rekonsiliasi', $reconciliation->id_rekonsiliasi)
                ->where('status', 'draft')->update('rekonsiliasi_bank', array(
                    'status' => 'selesai', 'id_pengguna_ubah' => (int) $this->session->userdata('id_pengguna'),
                    'diperbarui_pada' => date('Y-m-d H:i:s')
                ));
            if (!$updated) {
                throw new RuntimeException('Gagal menyelesaikan rekonsiliasi bank.');
            }
        });
    }

    private function validate_bank_reconciliation($company, $period, $accountId, $reconciliation)
    {
            $lines = $this->db->query('SELECT COUNT(*) AS jumlah,
                SUM(id_detail_jurnal IS NULL) AS belum_cocok,
                COALESCE(SUM(CASE WHEN arah=\'masuk\' THEN jumlah ELSE -jumlah END),0) AS mutasi
                FROM mutasi_rekening_bank WHERE id_rekonsiliasi=?',
                array($reconciliation->id_rekonsiliasi));
            if (!$lines) {
                throw new RuntimeException('Gagal memeriksa mutasi rekening.');
            }
            $lineSummary = $lines->row();
            if ((int) $lineSummary->belum_cocok > 0) {
                throw new DomainException('Cocokkan semua mutasi rekening dengan jurnal sebelum menyelesaikan rekonsiliasi.');
            }
            $end = date('Y-m-t', strtotime($period));
            $invalid = $this->query("SELECT COUNT(*) AS jumlah
                FROM mutasi_rekening_bank m
                LEFT JOIN detail_jurnal d ON d.id_detail=m.id_detail_jurnal
                LEFT JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
                WHERE m.id_rekonsiliasi=? AND (
                    d.id_detail IS NULL OR j.id_perusahaan<>? OR d.id_akun<>?
                    OR j.tanggal NOT BETWEEN ? AND ?
                    OR COALESCE(d.debit,0)-COALESCE(d.kredit,0)
                        <> CASE WHEN m.arah='masuk' THEN m.jumlah ELSE -m.jumlah END)",
                array($reconciliation->id_rekonsiliasi, $company, $accountId, $period, $end))->row();
            if ((int) $invalid->jumlah > 0) {
                throw new DomainException('Pencocokan jurnal berubah; perbarui rekonsiliasi bank.');
            }
            $unmatched = $this->db->query('SELECT COUNT(*) AS jumlah FROM detail_jurnal d
                JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
                LEFT JOIN mutasi_rekening_bank m ON m.id_detail_jurnal=d.id_detail
                WHERE j.id_perusahaan=? AND d.id_akun=? AND j.tanggal BETWEEN ? AND ?
                    AND COALESCE(d.debit,0)+COALESCE(d.kredit,0)>0 AND m.id_mutasi_rekening IS NULL',
                array($company, $accountId, $period, $end));
            if (!$unmatched) {
                throw new RuntimeException('Gagal memeriksa jurnal bank.');
            }
            if ((int) $unmatched->row()->jumlah > 0) {
                throw new DomainException('Cocokkan semua mutasi jurnal bank pada periode sebelum menyelesaikan rekonsiliasi.');
            }
            $opening = $this->account_balance_for_id($company, $accountId,
                date('Y-m-d', strtotime($period . ' -1 day')), true);
            $ending = $this->account_balance_for_id($company, $accountId, $end, true);
            if ($this->cents($reconciliation->saldo_awal) !== $opening) {
                throw new DomainException('Saldo awal rekening koran harus sama dengan saldo buku besar sebelum periode.');
            }
            if ($this->cents($reconciliation->saldo_akhir) !== $ending) {
                throw new DomainException('Saldo akhir rekening koran harus sama dengan saldo buku besar akhir periode.');
            }
            if ($this->cents($reconciliation->saldo_awal) + $this->cents($lineSummary->mutasi)
                !== $this->cents($reconciliation->saldo_akhir)) {
                throw new DomainException('Saldo awal ditambah seluruh mutasi tidak sama dengan saldo akhir rekening koran.');
            }
    }

    public function available_bank_entries($reconciliation)
    {
        $company = $this->company();
        $end = date('Y-m-t', strtotime($reconciliation->periode));
        $query = $this->db->query('SELECT d.id_detail,d.debit,d.kredit,j.tanggal,j.deskripsi,j.referensi
            FROM detail_jurnal d JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
            LEFT JOIN mutasi_rekening_bank m ON m.id_detail_jurnal=d.id_detail
            WHERE j.id_perusahaan=? AND d.id_akun=? AND j.tanggal BETWEEN ? AND ?
                AND m.id_mutasi_rekening IS NULL
            ORDER BY j.tanggal,j.id_jurnal,d.id_detail', array($company, $reconciliation->id_akun,
                $reconciliation->periode, $end));
        if (!$query) {
            throw new RuntimeException('Gagal membaca mutasi jurnal bank.');
        }
        return $query->result();
    }

    public function checks($period)
    {
        $date = $this->valid_period($period);
        $company = $this->company();
        $end = $date->format('Y-m-t');
        $checks = array();

        $drafts = $this->query("SELECT COUNT(*) AS n FROM transaksi WHERE id_perusahaan=?
            AND status='draft' AND tanggal BETWEEN ? AND ?", array($company, $period, $end))->row();
        $checks[] = $this->check('Draft pada periode', (int) $drafts->n, 0,
            (int) $drafts->n === 0, 'Semua draft bertanggal dalam periode harus diselesaikan.');

        $totals = $this->query('SELECT COALESCE(SUM(d.debit),0) AS debit,COALESCE(SUM(d.kredit),0) AS kredit
            FROM jurnal_transaksi j JOIN detail_jurnal d ON d.id_jurnal=j.id_jurnal
            WHERE j.id_perusahaan=? AND j.tanggal<=?', array($company, $end))->row();
        $difference = $this->cents($totals->debit) - $this->cents($totals->kredit);
        $checks[] = $this->check('Neraca saldo seimbang', $totals->debit, $totals->kredit,
            $difference === 0, 'Total debit dan kredit sampai akhir periode harus sama.');

        foreach (array(
            array('Piutang usaha', 'piutang_usaha', 'piutang', true),
            array('Utang usaha', 'utang_usaha', 'utang', false),
            array('Persediaan', 'persediaan', 'persediaan', true)
        ) as $spec) {
            $ledger = $this->account_balance($company, $spec[1], $end, $spec[3]);
            $subsidiary = $spec[2] === 'persediaan'
                ? $this->inventory_balance($company, $end)
                : $this->receivable_payable_balance($company, $end, $spec[2]);
            $ok = $ledger !== null && $this->cents($ledger) === $this->cents($subsidiary);
            $checks[] = $this->check($spec[0] . ' cocok dengan buku besar',
                $ledger === null ? 'Belum diatur' : $ledger, $subsidiary, $ok,
                $ledger === null ? 'Lengkapi Pengaturan Akun terlebih dahulu.' : 'Telusuri selisih pada rincian tagihan atau mutasi.');
        }

        $asset = $this->fixed_asset_balance($company, $end);
        $checks[] = $this->check('Aset tetap dan akumulasi penyusutan cocok',
            $asset['ledger'], $asset['subsidiary'], $asset['ok'],
            $asset['message']);

        $cashAccounts = $this->cash_accounts();
        $unclassified = array();
        foreach ($cashAccounts as $account) {
            if (!(int) $account->has_children && (!$account->jenis || !(int) $account->aktif)) {
                $unclassified[] = $account->kode_akun . ' - ' . $account->nama_akun;
            }
        }
        $checks[] = $this->check('Semua akun kas/bank diklasifikasikan', count($unclassified), 0,
            count($cashAccounts) > 0 && count($unclassified) === 0,
            !$cashAccounts ? 'Kelompok akun kas/bank 1100 belum tersedia.'
                : ($unclassified ? implode(', ', $unclassified) : 'Setiap akun rincian kas/bank sudah diklasifikasikan.'));

        $bank = $this->get_bank_reconciliations($period);
        $missingBank = array();
        foreach ($bank as $account) {
            if ($account->status !== 'selesai') {
                $missingBank[] = $account->kode_akun . ' - ' . $account->nama_akun;
                continue;
            }
            try {
                $this->validate_bank_reconciliation($company, $period, $account->id_akun, $account);
            } catch (DomainException $e) {
                $missingBank[] = $account->kode_akun . ' - ' . $account->nama_akun . ': ' . $e->getMessage();
            }
        }
        $checks[] = $this->check('Rekening bank direkonsiliasi', count($missingBank), 0,
            count($missingBank) === 0, $missingBank ? implode(', ', $missingBank) : 'Semua rekening bank aktif sudah direkonsiliasi.');

        return array('period' => $period, 'end' => $end, 'checks' => $checks,
            'ready' => count(array_filter($checks, function ($check) { return !$check['ok']; })) === 0);
    }

    public function close($period, $note)
    {
        $date = $this->valid_period($period);
        if ($date->format('Y-m') > date('Y-m')) {
            throw new DomainException('Periode mendatang belum dapat ditutup.');
        }
        if (!is_string($note) || strlen(trim($note)) > 500) {
            throw new InvalidArgumentException('Catatan penutupan maksimal 500 karakter.');
        }
        $company = $this->company();
        $this->atomic(function () use ($company, $period, $note) {
            $this->assert_open($company, $period);
            $this->assert_sequence($company, $period);
            $result = $this->checks($period);
            if (!$result['ready']) {
                $failed = array();
                foreach ($result['checks'] as $check) {
                    if (!$check['ok']) {
                        $failed[] = $check['label'];
                    }
                }
                throw new DomainException('Periode belum dapat ditutup. Selesaikan: ' . implode('; ', $failed) . '.');
            }
            $user = (int) $this->session->userdata('id_pengguna');
            $query = $this->db->query("INSERT INTO periode_akuntansi
                (id_perusahaan,periode,status,catatan,id_pengguna_ubah,diperbarui_pada)
                VALUES (?,?, 'ditutup', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE status='ditutup',catatan=VALUES(catatan),
                    id_pengguna_ubah=VALUES(id_pengguna_ubah),diperbarui_pada=VALUES(diperbarui_pada)",
                array($company, $period, trim($note) === '' ? null : trim($note), $user));
            if (!$query) {
                throw new RuntimeException('Gagal menutup periode.');
            }
            $this->audit($company, $period, 'ditutup', $note, $user);
        });
    }

    public function reopen($period, $reason)
    {
        $this->valid_period($period);
        if (!is_string($reason) || trim($reason) === '' || strlen(trim($reason)) > 500) {
            throw new InvalidArgumentException('Alasan membuka kembali periode wajib diisi, maksimal 500 karakter.');
        }
        $company = $this->company();
        $this->atomic(function () use ($company, $period, $reason) {
            $query = $this->db->query("SELECT periode FROM periode_akuntansi
                WHERE id_perusahaan=? AND status='ditutup' ORDER BY periode DESC LIMIT 1 FOR UPDATE",
                array($company));
            if (!$query) {
                throw new RuntimeException('Gagal memeriksa periode yang ditutup.');
            }
            if (!$query->row() || $query->row()->periode !== $period) {
                throw new DomainException('Hanya periode tertutup paling akhir yang dapat dibuka kembali.');
            }
            $user = (int) $this->session->userdata('id_pengguna');
            $updated = $this->db->where('id_perusahaan', $company)->where('periode', $period)
                ->where('status', 'ditutup')->update('periode_akuntansi', array(
                    'status' => 'terbuka', 'catatan' => trim($reason),
                    'id_pengguna_ubah' => $user, 'diperbarui_pada' => date('Y-m-d H:i:s')
                ));
            if (!$updated) {
                throw new RuntimeException('Gagal membuka kembali periode.');
            }
            $this->audit($company, $period, 'dibuka_kembali', $reason, $user);
        });
    }

    private function assert_sequence($company, $period)
    {
        $query = $this->db->query("SELECT MIN(tanggal) AS awal FROM (
            SELECT tanggal FROM transaksi WHERE id_perusahaan=?
            UNION ALL SELECT tanggal FROM jurnal_transaksi WHERE id_perusahaan=?
        ) aktivitas", array($company, $company));
        if (!$query) {
            throw new RuntimeException('Gagal memeriksa urutan periode.');
        }
        $first = $query->row()->awal;
        $first = $first ? substr($first, 0, 7) . '-01' : date('Y-m-01');
        $cursor = new DateTime($first);
        $target = new DateTime($period);
        if ($target < $cursor) {
            throw new DomainException('Periode tidak dapat ditutup sebelum periode aktivitas pertama perusahaan.');
        }
        while ($cursor < $target) {
            $month = $cursor->format('Y-m-d');
            $closed = $this->db->where('id_perusahaan', $company)->where('periode', $month)
                ->where('status', 'ditutup')->count_all_results('periode_akuntansi');
            if ($closed !== 1) {
                throw new DomainException('Tutup periode secara berurutan. Periode ' . $cursor->format('m/Y') . ' belum ditutup.');
            }
            $cursor->modify('+1 month');
        }
    }

    private function account_balance($company, $setting, $end, $debit_normal)
    {
        $settingQuery = $this->db->query('SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan=? LIMIT 1', array($company, $setting));
        if (!$settingQuery) {
            throw new RuntimeException('Gagal membaca pengaturan akun ' . $setting . '.');
        }
        if (!$settingQuery->row()) {
            return null;
        }
        $sql = "WITH RECURSIVE subtree AS (
            SELECT id_akun FROM akun WHERE id_perusahaan=? AND kode_akun=?
            UNION ALL SELECT a.id_akun FROM akun a JOIN subtree s ON a.id_akun_induk=s.id_akun
                WHERE a.id_perusahaan=?
        )
        SELECT COALESCE(SUM(COALESCE(d.debit,0)-COALESCE(d.kredit,0)),0) AS saldo
        FROM subtree s JOIN detail_jurnal d ON d.id_akun=s.id_akun
        JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal AND j.id_perusahaan=? AND j.tanggal<=?";
        $roots = array('piutang_usaha' => '1200', 'utang_usaha' => '2100', 'persediaan' => '1700');
        $query = $this->db->query($sql, array($company, $roots[$setting], $company, $company, $end));
        if (!$query) {
            throw new RuntimeException('Gagal menghitung saldo buku besar.');
        }
        $balance = $query->row()->saldo;
        if (!$debit_normal) {
            $balance = $this->money(-$this->cents($balance));
        }
        return $balance;
    }

    private function receivable_payable_balance($company, $end, $type)
    {
        $invoiceTypes = $type === 'piutang'
            ? "t.jenis_transaksi IN ('penjualan','pelepasan_aset') AND t.cara_pembayaran='kredit'"
            : "t.jenis_transaksi IN ('pembelian','perolehan_aset','beban_penjualan_jasa') AND t.cara_pembayaran='kredit'";
        $openingType = $type === 'piutang' ? 'saldo_awal_piutang' : 'saldo_awal_utang';
        $paymentType = $type === 'piutang' ? 'penerimaan_piutang' : 'pembayaran_utang';
        $returnType = $type === 'piutang' ? 'retur_penjualan' : 'retur_pembelian';
        $date = $this->db->escape($end);
        $active = function ($alias) use ($company, $date) {
            return "EXISTS (SELECT 1 FROM transaksi_jurnal tp JOIN jurnal_transaksi jp
                    ON jp.id_perusahaan=tp.id_perusahaan AND jp.id_jurnal=tp.id_jurnal
                    WHERE tp.id_perusahaan={$company} AND tp.id_transaksi={$alias}.id_transaksi
                    AND tp.jenis_jurnal='posting' AND jp.tanggal<={$date})
                AND NOT EXISTS (SELECT 1 FROM transaksi_jurnal tr JOIN jurnal_transaksi jr
                    ON jr.id_perusahaan=tr.id_perusahaan AND jr.id_jurnal=tr.id_jurnal
                    WHERE tr.id_perusahaan={$company} AND tr.id_transaksi={$alias}.id_transaksi
                    AND tr.jenis_jurnal='pembalik' AND jr.tanggal<={$date})";
        };
        $activeParent = function ($alias) use ($company, $date) {
            return "EXISTS (SELECT 1 FROM transaksi_jurnal tp JOIN jurnal_transaksi jp
                    ON jp.id_perusahaan=tp.id_perusahaan AND jp.id_jurnal=tp.id_jurnal
                    WHERE tp.id_perusahaan={$company} AND tp.id_transaksi={$alias}.id_transaksi_asal
                    AND tp.jenis_jurnal='posting' AND jp.tanggal<={$date})
                AND NOT EXISTS (SELECT 1 FROM transaksi_jurnal tr JOIN jurnal_transaksi jr
                    ON jr.id_perusahaan=tr.id_perusahaan AND jr.id_jurnal=tr.id_jurnal
                    WHERE tr.id_perusahaan={$company} AND tr.id_transaksi={$alias}.id_transaksi_asal
                    AND tr.jenis_jurnal='pembalik' AND jr.tanggal<={$date})";
        };
        $paymentActive = $active('pay');
        $returnActive = $active('ret');
        $invoiceActive = $active('t');
        $openingActive = $activeParent('t');
        $sql = "SELECT COALESCE(SUM(CASE WHEN sisa>0 THEN sisa ELSE 0 END),0) AS saldo
            FROM (
                SELECT t.total
                    - COALESCE((SELECT SUM(a.jumlah) FROM alokasi_transaksi a
                        JOIN transaksi pay ON pay.id_perusahaan=a.id_perusahaan AND pay.id_transaksi=a.id_transaksi
                        WHERE a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_tagihan=t.id_transaksi
                            AND pay.jenis_transaksi=" . $this->db->escape($paymentType) . "
                            AND {$paymentActive}),0)
                    - COALESCE((SELECT SUM(ret.total) FROM transaksi ret
                        WHERE ret.id_perusahaan=t.id_perusahaan AND ret.id_transaksi_asal=t.id_transaksi
                            AND ret.jenis_transaksi=" . $this->db->escape($returnType) . "
                            AND {$returnActive}),0) AS sisa
                FROM transaksi t
                WHERE t.id_perusahaan=? AND (
                    (t.jenis_transaksi=" . $this->db->escape($openingType) . " AND {$openingActive})
                    OR (({$invoiceTypes}) AND {$invoiceActive})
                )
            ) tagihan";
        $query = $this->db->query($sql, array($company));
        if (!$query) {
            throw new RuntimeException('Gagal menghitung saldo buku pembantu ' . $type . '.');
        }
        return $query->row()->saldo;
    }

    private function inventory_balance($company, $end)
    {
        $query = $this->db->query("SELECT COALESCE(SUM(CASE
                WHEN m.jenis_mutasi='masuk' THEN m.nilai
                WHEN m.jenis_mutasi='keluar' THEN -m.nilai
                WHEN m.jenis_mutasi='pembalik_masuk' THEN -m.nilai
                ELSE m.nilai END),0) AS saldo
            FROM mutasi_persediaan m
            JOIN transaksi_jurnal tj ON tj.id_perusahaan=m.id_perusahaan AND tj.id_transaksi=m.id_transaksi
                AND tj.jenis_jurnal=CASE WHEN m.jenis_mutasi IN ('pembalik_masuk','pembalik_keluar')
                    THEN 'pembalik' ELSE 'posting' END
            JOIN jurnal_transaksi j ON j.id_perusahaan=tj.id_perusahaan AND j.id_jurnal=tj.id_jurnal
            WHERE m.id_perusahaan=? AND j.tanggal<=?", array($company, $end));
        if (!$query) {
            throw new RuntimeException('Gagal menghitung buku pembantu persediaan.');
        }
        return $query->row()->saldo;
    }

    private function fixed_asset_balance($company, $end)
    {
        $date = $this->db->escape($end);
        $active = function ($alias) use ($company, $date) {
            return "EXISTS (SELECT 1 FROM transaksi_jurnal tp JOIN jurnal_transaksi jp
                    ON jp.id_perusahaan=tp.id_perusahaan AND jp.id_jurnal=tp.id_jurnal
                    WHERE tp.id_perusahaan={$company} AND tp.id_transaksi={$alias}.id_transaksi
                    AND tp.jenis_jurnal='posting' AND jp.tanggal<={$date})
                AND NOT EXISTS (SELECT 1 FROM transaksi_jurnal tr JOIN jurnal_transaksi jr
                    ON jr.id_perusahaan=tr.id_perusahaan AND jr.id_jurnal=tr.id_jurnal
                    WHERE tr.id_perusahaan={$company} AND tr.id_transaksi={$alias}.id_transaksi
                    AND tr.jenis_jurnal='pembalik' AND jr.tanggal<={$date})";
        };
        $assetActive = function ($alias) use ($company, $date) {
            return "EXISTS (SELECT 1 FROM transaksi_jurnal tp JOIN jurnal_transaksi jp
                    ON jp.id_perusahaan=tp.id_perusahaan AND jp.id_jurnal=tp.id_jurnal
                    WHERE tp.id_perusahaan={$company} AND tp.id_transaksi={$alias}.id_transaksi_perolehan
                    AND tp.jenis_jurnal='posting' AND jp.tanggal<={$date})
                AND NOT EXISTS (SELECT 1 FROM transaksi_jurnal tr JOIN jurnal_transaksi jr
                    ON jr.id_perusahaan=tr.id_perusahaan AND jr.id_jurnal=tr.id_jurnal
                    WHERE tr.id_perusahaan={$company} AND tr.id_transaksi={$alias}.id_transaksi_perolehan
                    AND tr.jenis_jurnal='pembalik' AND jr.tanggal<={$date})";
        };
        $disposalActive = $active('p');
        $depreciationActive = $active('s');
        $assetActiveSql = $assetActive('a');
        $assets = $this->db->query("SELECT a.id_aset_tetap,a.nilai_perolehan,a.akumulasi_penyusutan_awal,
                a.id_akun_aset,a.id_akun_akumulasi_penyusutan,a.id_transaksi_perolehan
            FROM aset_tetap a WHERE a.id_perusahaan=? AND {$assetActiveSql}",
            array($company));
        if (!$assets) {
            throw new RuntimeException('Gagal membaca buku pembantu aset tetap.');
        }
        $gross = 0;
        $accumulated = 0;
        foreach ($assets->result() as $asset) {
            $disposal = $this->db->query("SELECT COUNT(*) AS n FROM pelepasan_aset p
                JOIN transaksi t ON t.id_perusahaan=p.id_perusahaan AND t.id_transaksi=p.id_transaksi
                WHERE p.id_perusahaan=? AND p.id_aset_tetap=? AND {$disposalActive}",
                array($company, $asset->id_aset_tetap));
            if (!$disposal) {
                throw new RuntimeException('Gagal membaca pelepasan aset.');
            }
            if ((int) $disposal->row()->n > 0) {
                continue;
            }
            $depreciation = $this->db->query("SELECT COALESCE(SUM(s.nilai_penyusutan),0) AS nilai
                FROM penyusutan_aset s WHERE s.id_perusahaan=? AND s.id_aset_tetap=? AND {$depreciationActive}",
                array($company, $asset->id_aset_tetap));
            if (!$depreciation) {
                throw new RuntimeException('Gagal membaca penyusutan aset.');
            }
            $gross += $this->cents($asset->nilai_perolehan);
            $accumulated += $this->cents($asset->akumulasi_penyusutan_awal)
                + $this->cents($depreciation->row()->nilai);
        }
        $query = $this->db->query("WITH RECURSIVE asset_tree AS (
            SELECT id_akun,kode_akun AS root FROM akun WHERE id_perusahaan=? AND kode_akun IN ('1800','1900')
            UNION SELECT a.id_akun,t.root FROM akun a JOIN asset_tree t ON a.id_akun_induk=t.id_akun
                WHERE a.id_perusahaan=?
        )
        SELECT a.root,COALESCE(SUM(COALESCE(d.debit,0)-COALESCE(d.kredit,0)),0) AS saldo
        FROM asset_tree a JOIN detail_jurnal d ON d.id_akun=a.id_akun
        JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
        WHERE j.id_perusahaan=? AND j.tanggal<=? GROUP BY a.root", array($company, $company, $company, $end));
        if (!$query) {
            throw new RuntimeException('Gagal menghitung saldo buku besar aset tetap.');
        }
        $ledgerGross = 0;
        $ledgerAccumulated = 0;
        foreach ($query->result() as $row) {
            if ($row->root === '1800') {
                $ledgerGross = $this->cents($row->saldo);
            } else {
                $ledgerAccumulated = -$this->cents($row->saldo);
            }
        }
        $ledger = 'Perolehan ' . $this->money($ledgerGross) . '; akumulasi ' . $this->money($ledgerAccumulated);
        $subsidiary = 'Perolehan ' . $this->money($gross) . '; akumulasi ' . $this->money($accumulated);
        $missing = $this->query("SELECT COUNT(*) AS n FROM transaksi t
            JOIN transaksi_jurnal p ON p.id_perusahaan=t.id_perusahaan AND p.id_transaksi=t.id_transaksi AND p.jenis_jurnal='posting'
            JOIN jurnal_transaksi jp ON jp.id_jurnal=p.id_jurnal AND jp.id_perusahaan=p.id_perusahaan
            JOIN transaksi_jurnal r ON r.id_perusahaan=t.id_perusahaan AND r.id_transaksi=t.id_transaksi AND r.jenis_jurnal='pembalik'
            JOIN jurnal_transaksi jr ON jr.id_jurnal=r.id_jurnal AND jr.id_perusahaan=r.id_perusahaan
            LEFT JOIN aset_tetap a ON a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_perolehan=t.id_transaksi
            WHERE t.id_perusahaan=? AND t.jenis_transaksi='perolehan_aset'
              AND jp.tanggal<=? AND jr.tanggal>? AND a.id_aset_tetap IS NULL", array($company, $end, $end))->row();
        if ((int) $missing->n > 0) {
            return array('ledger' => $ledger, 'subsidiary' => 'Tidak tersedia',
                'ok' => false, 'message' => 'Riwayat register aset yang dibatalkan tidak tersedia untuk tanggal ini; rekonsiliasi harus ditelusuri.');
        }
        return array('ledger' => $ledger, 'subsidiary' => $subsidiary,
            'ok' => $ledgerGross === $gross && $ledgerAccumulated === $accumulated,
            'message' => 'Selisih aset tetap harus ditelusuri pada register aset dan jurnal.');
    }

    private function account_balance_for_id($company, $account, $end, $debitNormal)
    {
        $query = $this->db->query('SELECT COALESCE(SUM(COALESCE(d.debit,0)-COALESCE(d.kredit,0)),0) AS saldo
            FROM detail_jurnal d JOIN jurnal_transaksi j ON j.id_jurnal=d.id_jurnal
            WHERE j.id_perusahaan=? AND j.tanggal<=? AND d.id_akun=?', array($company, $end, $account));
        if (!$query) {
            throw new RuntimeException('Gagal menghitung saldo akun aset.');
        }
        $value = $this->cents($query->row()->saldo);
        return $debitNormal ? $value : -$value;
    }

    private function leaf_cash_account($company, $id, $lock = false)
    {
        $query = $this->db->query("WITH RECURSIVE cash_tree AS (
            SELECT id_akun,id_akun_induk FROM akun WHERE id_perusahaan=? AND kode_akun='1100'
            UNION ALL SELECT a.id_akun,a.id_akun_induk FROM akun a JOIN cash_tree c ON a.id_akun_induk=c.id_akun
                WHERE a.id_perusahaan=?
        )
        SELECT a.id_akun FROM akun a JOIN cash_tree c ON c.id_akun=a.id_akun
        WHERE a.id_perusahaan=? AND a.id_akun=? AND NOT EXISTS (
            SELECT 1 FROM akun child WHERE child.id_perusahaan=a.id_perusahaan AND child.id_akun_induk=a.id_akun
        ) LIMIT 1" . ($lock ? ' FOR UPDATE' : ''),
            array($company, $company, $company, $id));
        if (!$query) {
            throw new RuntimeException('Gagal memeriksa akun kas/bank.');
        }
        return $query->row();
    }

    private function assert_open($company, $period)
    {
        $this->valid_period($period);
        $query = $this->db->query('SELECT status FROM periode_akuntansi
            WHERE id_perusahaan=? AND periode=? FOR UPDATE', array($company, $period));
        if (!$query) {
            throw new RuntimeException('Gagal memeriksa status periode.');
        }
        if ($query->row() && $query->row()->status === 'ditutup') {
            throw new DomainException('Periode sudah ditutup; buka kembali periode sebelum mengubah rekonsiliasi.');
        }
    }

    private function valid_id($value, $label)
    {
        if (!is_scalar($value) || !preg_match('/^[1-9]\d{0,9}$/D', (string) $value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        return (int) $value;
    }

    private function valid_date($value)
    {
        if (!is_string($value) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $value)
            || !($date = DateTime::createFromFormat('!Y-m-d', $value))
            || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Tanggal mutasi rekening tidak valid.');
        }
        return $date;
    }

    private function valid_money($value, $label)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        $sign = '';
        if (isset($value[0]) && $value[0] === '-') {
            $sign = '-';
            $value = substr($value, 1);
        }
        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/D', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d+(?:,\d{1,2})?$/D', $value)) {
            $value = str_replace(',', '.', $value);
        } elseif (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        if (!preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', $value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        return $this->money($this->cents($sign . $value));
    }

    private function audit($company, $period, $action, $reason, $user)
    {
        if (!$this->db->insert('periode_akuntansi_riwayat', array(
            'id_perusahaan' => $company, 'periode' => $period, 'aksi' => $action,
            'alasan' => trim((string) $reason) === '' ? null : trim($reason),
            'id_pengguna' => $user, 'dibuat_pada' => date('Y-m-d H:i:s')
        ))) {
            throw new RuntimeException('Gagal mencatat riwayat perubahan periode.');
        }
    }

    private function atomic($operation)
    {
        if ($this->db->trans_active()) {
            throw new RuntimeException('Operasi periode harus memulai transaksi database sendiri.');
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        if (!$this->db->trans_begin()) {
            $this->db->db_debug = $debug;
            throw new RuntimeException('Gagal memulai transaksi database.');
        }
        try {
            $company = $this->company();
            $row = $this->db->query('SELECT id_perusahaan FROM perusahaan WHERE id_perusahaan=? FOR UPDATE', array($company));
            if (!$row || !$row->row()) {
                throw new DomainException('Perusahaan tidak ditemukan.');
            }
            $operation();
            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Gagal menyimpan perubahan periode.');
            }
        } catch (Throwable $e) {
            if ($this->db->trans_active()) {
                $this->db->trans_rollback();
            }
            throw $e;
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    private function query($sql, $params)
    {
        $query = $this->db->query($sql, $params);
        if (!$query) {
            throw new RuntimeException('Gagal memeriksa kesiapan tutup buku.');
        }
        return $query;
    }

    private function check($label, $actual, $expected, $ok, $note)
    {
        return array('label' => $label, 'actual' => $actual, 'expected' => $expected,
            'ok' => (bool) $ok, 'note' => $note);
    }

    private function cents($amount)
    {
        $amount = (string) $amount;
        if (!preg_match('/^-?\d{1,15}(?:\.\d{1,2})?$/D', $amount)) {
            throw new RuntimeException('Nominal buku besar tidak valid.');
        }
        $negative = isset($amount[0]) && $amount[0] === '-';
        if ($negative) {
            $amount = substr($amount, 1);
        }
        $parts = explode('.', $amount);
        $value = (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
        return $negative ? -$value : $value;
    }

    private function money($cents)
    {
        $negative = $cents < 0;
        $cents = abs($cents);
        return ($negative ? '-' : '') . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
