<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Aset_tetap_service
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
        $this->ci->load->library('lampiran_service');
        $this->ci->load->library('aset_tetap_kalkulasi');
    }

    public function daftar()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT a.*, t.status AS status_perolehan, t.nomor_transaksi,
            t.lampiran_nama, COALESCE(d.akumulasi, 0) + a.akumulasi_penyusutan_awal AS akumulasi_tercatat,
            COALESCE(p.nilai_buku, a.nilai_perolehan - a.akumulasi_penyusutan_awal) AS nilai_buku
            FROM aset_tetap a
            JOIN transaksi t ON t.id_perusahaan=a.id_perusahaan AND t.id_transaksi=a.id_transaksi_perolehan
            LEFT JOIN (
                SELECT s.id_perusahaan, s.id_aset_tetap, SUM(s.nilai_penyusutan) AS akumulasi
                FROM penyusutan_aset s JOIN transaksi st ON st.id_perusahaan=s.id_perusahaan
                    AND st.id_transaksi=s.id_transaksi
                WHERE s.status='aktif' AND st.status='diposting'
                GROUP BY s.id_perusahaan, s.id_aset_tetap
            ) d ON d.id_perusahaan=a.id_perusahaan AND d.id_aset_tetap=a.id_aset_tetap
            LEFT JOIN (
                SELECT pa.id_perusahaan, pa.id_aset_tetap, pa.nilai_buku
                FROM pelepasan_aset pa JOIN transaksi pt ON pt.id_perusahaan=pa.id_perusahaan
                    AND pt.id_transaksi=pa.id_transaksi
                WHERE pt.status='diposting'
            ) p ON p.id_perusahaan=a.id_perusahaan AND p.id_aset_tetap=a.id_aset_tetap
            WHERE a.id_perusahaan=? ORDER BY a.kode_aset, a.id_aset_tetap", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get($id)
    {
        $company = $this->company();
        $this->id($id);
        $query = $this->ci->db->query("SELECT a.*, t.status AS status_perolehan, t.nomor_transaksi,
            t.lampiran_nama, t.jenis_transaksi AS jenis_transaksi_perolehan,
            COALESCE(d.akumulasi, 0) + a.akumulasi_penyusutan_awal AS akumulasi_tercatat,
            a.nilai_perolehan - COALESCE(d.akumulasi, 0) - a.akumulasi_penyusutan_awal AS nilai_buku
            FROM aset_tetap a
            JOIN transaksi t ON t.id_perusahaan=a.id_perusahaan AND t.id_transaksi=a.id_transaksi_perolehan
            LEFT JOIN (
                SELECT s.id_perusahaan, s.id_aset_tetap, SUM(s.nilai_penyusutan) AS akumulasi
                FROM penyusutan_aset s JOIN transaksi st ON st.id_perusahaan=s.id_perusahaan
                    AND st.id_transaksi=s.id_transaksi
                WHERE s.status='aktif' AND st.status='diposting'
                GROUP BY s.id_perusahaan, s.id_aset_tetap
            ) d ON d.id_perusahaan=a.id_perusahaan AND d.id_aset_tetap=a.id_aset_tetap
            WHERE a.id_perusahaan=? AND a.id_aset_tetap=?", array($company, $id));
        $this->ok($query);
        return $query->row();
    }

    public function daftar_transaksi()
    {
        $company = $this->company();
        $query = $this->ci->db->query("SELECT t.id_transaksi, t.jenis_transaksi, t.nomor_transaksi,
            t.tanggal, t.status, t.total, t.deskripsi, t.lampiran_nama, a.nama_aset
            FROM transaksi t
            LEFT JOIN aset_tetap a ON a.id_perusahaan=t.id_perusahaan
                AND a.id_transaksi_perolehan=t.id_transaksi
            WHERE t.id_perusahaan=? AND t.jenis_transaksi IN ('perolehan_aset','penyusutan_aset','pelepasan_aset')
            ORDER BY t.tanggal DESC, t.id_transaksi DESC LIMIT 200", array($company));
        $this->ok($query);
        return $query->result();
    }

    public function get_transaksi($id)
    {
        $company = $this->company();
        $this->id($id);
        $query = $this->ci->db->query("SELECT " . Lampiran_service::KOLOM_TRANSAKSI . "
            FROM transaksi t WHERE t.id_perusahaan=? AND t.id_transaksi=?
            AND t.jenis_transaksi IN ('perolehan_aset','penyusutan_aset','pelepasan_aset')",
            array($company, $id));
        $this->ok($query);
        $transaction = $query->row();
        if (!$transaction) {
            return null;
        }
        if ($transaction->jenis_transaksi === 'perolehan_aset') {
            $query = $this->ci->db->query('SELECT * FROM aset_tetap
                WHERE id_perusahaan=? AND id_transaksi_perolehan=?', array($company, $id));
            $this->ok($query);
            $transaction->asset = $query->row();
        } elseif ($transaction->jenis_transaksi === 'penyusutan_aset') {
            $query = $this->ci->db->query('SELECT s.id_aset_tetap, a.kode_aset, a.nama_aset,
                s.periode, s.nilai_penyusutan FROM penyusutan_aset s
                JOIN aset_tetap a ON a.id_perusahaan=s.id_perusahaan AND a.id_aset_tetap=s.id_aset_tetap
                WHERE s.id_perusahaan=? AND s.id_transaksi=? ORDER BY a.kode_aset', array($company, $id));
            $this->ok($query);
            $transaction->details = $query->result_array();
        } else {
            $query = $this->ci->db->query('SELECT p.*, a.kode_aset, a.nama_aset FROM pelepasan_aset p
                JOIN aset_tetap a ON a.id_perusahaan=p.id_perusahaan AND a.id_aset_tetap=p.id_aset_tetap
                WHERE p.id_perusahaan=? AND p.id_transaksi=?', array($company, $id));
            $this->ok($query);
            $transaction->disposal = $query->row();
        }
        return $transaction;
    }

    public function simpan_perolehan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if ($id !== null && $id !== '') {
            $this->id($id);
        } else {
            $id = null;
        }
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data perolehan aset tidak valid.');
        }
        if ($id === null) {
            return $this->ci->transaksi_service->buat_draft('perolehan_aset', $tanggal,
                $this->description($data, 'Perolehan aset'),
                function ($transactionId, $company, $db) use ($data, $tanggal, $lampiran) {
                    $clean = $this->validate_perolehan($company, $tanggal, $data);
                    $this->write_perolehan($transactionId, $company, $clean, $db);
                    $this->ci->lampiran_service->simpan($company, $transactionId, $lampiran, false);
                });
        }
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $data, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT t.*, a.id_aset_tetap FROM transaksi t
                JOIN aset_tetap a ON a.id_perusahaan=t.id_perusahaan AND a.id_transaksi_perolehan=t.id_transaksi
                WHERE t.id_perusahaan=? AND t.id_transaksi=? AND t.jenis_transaksi='perolehan_aset' FOR UPDATE",
                array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft perolehan aset yang dapat diubah.');
            }
            $clean = $this->validate_perolehan($company, $tanggal, $data, $transaction->id_aset_tetap);
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                ->update('transaksi', array('tanggal' => $tanggal)));
            $this->write_perolehan($id, $company, $clean, $db, $transaction->id_aset_tetap);
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function daftarkan_lama($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data aset lama tidak valid.');
        }
        return $this->ci->transaksi_service->atomic(function ($company) use ($data) {
            $clean = $this->validate_asset($company, $data, null);
            $db = $this->ci->db;
            $opening = $db->query("SELECT t.id_transaksi, t.tanggal FROM saldo_awal s
                JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi
                WHERE s.id_perusahaan=? AND t.status='diposting' FOR UPDATE", array($company));
            $this->ok($opening);
            $balance = $opening->row();
            if (!$balance) {
                throw new DomainException('Saldo Awal harus diposting sebelum aset lama didaftarkan.');
            }
            if ($clean['tanggal_mulai_penyusutan'] > $balance->tanggal) {
                throw new DomainException('Tanggal mulai penyusutan aset lama tidak boleh setelah tanggal Saldo Awal.');
            }
            $clean['akumulasi_penyusutan_awal'] = $this->optional_money(isset($data['akumulasi_penyusutan_awal'])
                ? $data['akumulasi_penyusutan_awal'] : '0');
            $this->calculator($clean, $balance->tanggal, $clean['akumulasi_penyusutan_awal']);
            $this->ok($db->insert('aset_tetap', $this->asset_row($company, $balance->id_transaksi, $clean)));
            return array('id_aset_tetap' => $db->insert_id());
        });
    }

    public function posting($id)
    {
        return $this->ci->transaksi_service->posting($id, function ($transaction, $db) {
            if ($transaction->jenis_transaksi === 'perolehan_aset') {
                return $this->posting_perolehan($transaction, $db);
            }
            if ($transaction->jenis_transaksi === 'penyusutan_aset') {
                return $this->posting_penyusutan($transaction, $db);
            }
            if ($transaction->jenis_transaksi === 'pelepasan_aset') {
                return $this->posting_pelepasan($transaction, $db);
            }
            throw new DomainException('Transaksi aset tetap tidak ditemukan.');
        });
    }

    public function buat_penyusutan($period)
    {
        $this->date($period);
        $period = substr($period, 0, 7) . '-01';
        return $this->ci->transaksi_service->buat_draft('penyusutan_aset', $period, 'Penyusutan aset ' . substr($period, 0, 7),
            function ($transactionId, $company, $db) use ($period) {
                $query = $db->query("SELECT a.*, acq.jenis_transaksi AS jenis_perolehan FROM aset_tetap a
                    JOIN transaksi acq ON acq.id_perusahaan=a.id_perusahaan AND acq.id_transaksi=a.id_transaksi_perolehan
                    WHERE a.id_perusahaan=? AND a.status='aktif' AND acq.status='diposting'
                    ORDER BY a.id_aset_tetap FOR UPDATE", array($company));
                $this->ok($query);
                $created = 0;
                foreach ($query->result() as $asset) {
                    $this->guard_asset_period($company, $asset, $period);
                    $openingDate = $this->opening_date($company, $asset);
                    $posted = $this->posted_depreciation($company, $asset->id_aset_tetap);
                    $base = $this->depreciation_base($asset, $openingDate, $posted);
                    $this->assert_contiguous($base, $asset, $posted, $period, $openingDate);
                    $amount = $base->nilai_bulan($period);
                    if ($this->cents($amount) === 0) {
                        continue;
                    }
                    $this->ok($db->insert('penyusutan_aset', array(
                        'id_perusahaan' => $company, 'id_aset_tetap' => $asset->id_aset_tetap,
                        'id_transaksi' => $transactionId, 'periode' => $period, 'nilai_penyusutan' => $amount
                    )));
                    $created++;
                }
                if (!$created) {
                    throw new DomainException('Tidak ada aset aktif yang perlu disusutkan pada periode ini.');
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transactionId)->update('transaksi',
                    array('total_sebelum_pajak' => '0.00', 'total_pajak' => '0.00', 'total' => '0.00')));
            });
    }

    public function simpan_pelepasan($id, $tanggal, $data, $lampiran = null, $hapus_lampiran = false)
    {
        $this->date($tanggal);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data pelepasan aset tidak valid.');
        }
        if ($id !== null && $id !== '') {
            $this->id($id);
        } else {
            $id = null;
        }
        if ($id === null) {
            return $this->ci->transaksi_service->buat_draft('pelepasan_aset', $tanggal, 'Pelepasan aset',
                function ($transactionId, $company, $db) use ($tanggal, $data, $lampiran) {
                    $clean = $this->validate_pelepasan($company, $tanggal, $data);
                    $this->write_pelepasan($transactionId, $company, $clean, $db);
                    $this->ci->lampiran_service->simpan($company, $transactionId, $lampiran, false);
                });
        }
        return $this->ci->transaksi_service->atomic(function ($company) use ($id, $tanggal, $data, $lampiran, $hapus_lampiran) {
            $db = $this->ci->db;
            $query = $db->query("SELECT * FROM transaksi WHERE id_perusahaan=? AND id_transaksi=?
                AND jenis_transaksi='pelepasan_aset' FOR UPDATE", array($company, $id));
            $this->ok($query);
            $transaction = $query->row();
            if (!$transaction || $transaction->status !== 'draft') {
                throw new DomainException('Hanya draft pelepasan aset yang dapat diubah.');
            }
            $clean = $this->validate_pelepasan($company, $tanggal, $data, $id);
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)
                ->update('transaksi', array('tanggal' => $tanggal)));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('pelepasan_aset'));
            $this->write_pelepasan($id, $company, $clean, $db);
            $this->ci->lampiran_service->simpan($company, $id, $lampiran, $hapus_lampiran);
            return array('id_transaksi' => (int) $id, 'nomor_transaksi' => $transaction->nomor_transaksi);
        });
    }

    public function batalkan($id, $tanggal, $alasan)
    {
        return $this->ci->transaksi_service->batalkan($id, $tanggal, $alasan, function ($transaction, $db) {
            $company = $transaction->id_perusahaan;
            if ($transaction->jenis_transaksi === 'perolehan_aset') {
                $asset = $db->query('SELECT * FROM aset_tetap WHERE id_perusahaan=? AND id_transaksi_perolehan=? FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($asset);
                if (!$asset->row()) {
                    throw new DomainException('Aset dari transaksi perolehan tidak ditemukan.');
                }
                $assetId = $asset->row()->id_aset_tetap;
                $usage = $db->query('SELECT id_penyusutan_aset FROM penyusutan_aset WHERE id_perusahaan=? AND id_aset_tetap=? LIMIT 1 FOR UPDATE',
                    array($company, $assetId));
                $this->ok($usage);
                $disposal = $db->query('SELECT id_pelepasan_aset FROM pelepasan_aset WHERE id_perusahaan=? AND id_aset_tetap=? LIMIT 1 FOR UPDATE',
                    array($company, $assetId));
                $this->ok($disposal);
                if ($usage->row() || $disposal->row()) {
                    throw new DomainException('Perolehan tidak dapat dibatalkan karena aset sudah digunakan.');
                }
                $receipts = $db->query("SELECT a.id_alokasi_transaksi FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND r.status='diposting' LIMIT 1 FOR UPDATE",
                    array($company, $transaction->id_transaksi));
                $this->ok($receipts);
                if ($receipts->row()) {
                    throw new DomainException('Perolehan aset sudah dibayar dan tidak dapat dibatalkan.');
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $assetId)->delete('aset_tetap'));
                return;
            }
            if ($transaction->jenis_transaksi === 'penyusutan_aset') {
                $rows = $db->query('SELECT * FROM penyusutan_aset WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($rows);
                foreach ($rows->result() as $row) {
                    $later = $db->query("SELECT id_penyusutan_aset FROM penyusutan_aset s
                        JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi
                        WHERE s.id_perusahaan=? AND s.id_aset_tetap=? AND s.status='aktif'
                        AND s.periode>? AND t.status='diposting' LIMIT 1 FOR UPDATE",
                        array($company, $row->id_aset_tetap, $row->periode));
                    $this->ok($later);
                    $disposal = $db->query("SELECT p.id_pelepasan_aset FROM pelepasan_aset p
                        JOIN transaksi t ON t.id_perusahaan=p.id_perusahaan AND t.id_transaksi=p.id_transaksi
                        WHERE p.id_perusahaan=? AND p.id_aset_tetap=? AND t.status='diposting' LIMIT 1 FOR UPDATE",
                        array($company, $row->id_aset_tetap));
                    $this->ok($disposal);
                    if ($later->row() || $disposal->row()) {
                        throw new DomainException('Penyusutan tidak dapat dibatalkan setelah ada periode berikutnya atau pelepasan.');
                    }
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transaction->id_transaksi)
                    ->update('penyusutan_aset', array('status' => 'dibatalkan')));
                return;
            }
            if ($transaction->jenis_transaksi === 'pelepasan_aset') {
                $usage = $db->query('SELECT a.id_alokasi_transaksi FROM alokasi_transaksi a
                    JOIN transaksi r ON r.id_perusahaan=a.id_perusahaan AND r.id_transaksi=a.id_transaksi
                    WHERE a.id_perusahaan=? AND a.id_transaksi_tagihan=? AND r.status=\'diposting\' LIMIT 1 FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($usage);
                if ($usage->row()) {
                    throw new DomainException('Pelepasan kredit sudah memiliki penerimaan piutang dan tidak dapat dibatalkan.');
                }
                $query = $db->query('SELECT id_aset_tetap FROM pelepasan_aset WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                    array($company, $transaction->id_transaksi));
                $this->ok($query);
                foreach ($query->result() as $row) {
                    $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $row->id_aset_tetap)
                        ->update('aset_tetap', array('status' => 'aktif')));
                }
                $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $transaction->id_transaksi)
                    ->where('periode', substr($transaction->tanggal, 0, 7) . '-01')
                    ->where('status', 'aktif')->update('penyusutan_aset', array('status' => 'dibatalkan')));
                return;
            }
            throw new DomainException('Transaksi aset tetap tidak ditemukan.');
        });
    }

    private function posting_perolehan($transaction, $db)
    {
        $company = $transaction->id_perusahaan;
        $query = $db->query("SELECT * FROM aset_tetap WHERE id_perusahaan=? AND id_transaksi_perolehan=? FOR UPDATE",
            array($company, $transaction->id_transaksi));
        $this->ok($query);
        $asset = $query->row();
        if (!$asset || $asset->status !== 'aktif') {
            throw new DomainException('Data aset perolehan tidak ditemukan atau tidak aktif.');
        }
        $this->account($company, $asset->id_akun_aset, '1800');
        $total = $this->cents($asset->nilai_perolehan);
        if ($total !== $this->cents($transaction->total)) {
            throw new DomainException('Nilai aset tidak sesuai dengan total transaksi.');
        }
        $journal = array();
        $this->add_journal($journal, $asset->id_akun_aset, $total, 0);
        if ($transaction->cara_pembayaran === 'tunai') {
            $this->account($company, $transaction->id_akun_kas, '1100');
            $credit = $transaction->id_akun_kas;
        } elseif ($transaction->cara_pembayaran === 'kredit' && $transaction->id_pemasok) {
            $this->setting($company, 'utang_usaha', '2100');
            $supplier = $db->query('SELECT id_pemasok FROM pemasok WHERE id_perusahaan=? AND id_pemasok=? AND aktif=1 FOR UPDATE',
                array($company, $transaction->id_pemasok));
            $this->ok($supplier);
            if (!$supplier->row()) {
                throw new DomainException('Pemasok perolehan aset tidak ditemukan atau tidak aktif.');
            }
            $credit = $this->setting($company, 'utang_usaha', '2100');
        } else {
            throw new DomainException('Metode pembayaran perolehan aset tidak valid.');
        }
        $this->add_journal($journal, $credit, 0, $total);
        return $this->normalize($journal);
    }

    private function posting_penyusutan($transaction, $db)
    {
        $company = $transaction->id_perusahaan;
        $query = $db->query("SELECT s.*, a.id_akun_beban_penyusutan, a.id_akun_akumulasi_penyusutan,
            a.nilai_perolehan, a.nilai_residu, a.umur_manfaat_bulan, a.tanggal_mulai_penyusutan,
            a.akumulasi_penyusutan_awal, a.id_transaksi_perolehan, a.status AS status_aset,
            acq.jenis_transaksi AS jenis_perolehan
            FROM penyusutan_aset s JOIN aset_tetap a ON a.id_perusahaan=s.id_perusahaan
                AND a.id_aset_tetap=s.id_aset_tetap
            JOIN transaksi acq ON acq.id_perusahaan=a.id_perusahaan AND acq.id_transaksi=a.id_transaksi_perolehan
            WHERE s.id_perusahaan=? AND s.id_transaksi=? ORDER BY s.id_aset_tetap FOR UPDATE",
            array($company, $transaction->id_transaksi));
        $this->ok($query);
        $rows = $query->result();
        if (!$rows) {
            throw new DomainException('Rincian penyusutan tidak ditemukan.');
        }
        $journal = array();
        foreach ($rows as $row) {
            if ($row->status !== 'aktif' || $row->status_aset !== 'aktif') {
                throw new DomainException('Aset tidak aktif atau periode penyusutannya tidak valid.');
            }
            $this->guard_asset_period($company, $row, $row->periode, $transaction->id_transaksi);
            $openingDate = $this->opening_date($company, $row);
            $posted = $this->posted_depreciation($company, $row->id_aset_tetap, $transaction->id_transaksi);
            $base = $this->depreciation_base($row, $openingDate, $posted);
            if ($base->nilai_bulan($row->periode) !== $row->nilai_penyusutan) {
                throw new DomainException('Nilai penyusutan tidak sesuai dengan jadwal aset.');
            }
            $this->account($company, $row->id_akun_beban_penyusutan, null, 'Beban');
            $this->account($company, $row->id_akun_akumulasi_penyusutan, '1900');
            $amount = $this->cents($row->nilai_penyusutan);
            $this->add_journal($journal, $row->id_akun_beban_penyusutan, $amount, 0);
            $this->add_journal($journal, $row->id_akun_akumulasi_penyusutan, 0, $amount);
        }
        return $this->normalize($journal);
    }

    private function posting_pelepasan($transaction, $db)
    {
        $company = $transaction->id_perusahaan;
        $query = $db->query("SELECT p.id_pelepasan_aset, p.id_aset_tetap AS disposal_asset_id,
            p.nilai_pelepasan, a.kode_aset, a.nama_aset, a.tanggal_perolehan,
            a.tanggal_mulai_penyusutan, a.nilai_perolehan, a.nilai_residu, a.umur_manfaat_bulan,
            a.id_akun_aset, a.id_akun_akumulasi_penyusutan, a.id_akun_beban_penyusutan,
            a.akumulasi_penyusutan_awal, a.id_transaksi_perolehan, a.status AS asset_status,
            acq.jenis_transaksi AS jenis_perolehan
            FROM pelepasan_aset p JOIN aset_tetap a ON a.id_perusahaan=p.id_perusahaan
                AND a.id_aset_tetap=p.id_aset_tetap
            JOIN transaksi acq ON acq.id_perusahaan=a.id_perusahaan AND acq.id_transaksi=a.id_transaksi_perolehan
            WHERE p.id_perusahaan=? AND p.id_transaksi=? FOR UPDATE", array($company, $transaction->id_transaksi));
        $this->ok($query);
        $disposal = $query->row();
        if (!$disposal || $disposal->asset_status !== 'aktif') {
            throw new DomainException('Aset pelepasan tidak ditemukan atau sudah dilepas.');
        }
        $assetId = $disposal->disposal_asset_id;
        $saleMonth = substr($transaction->tanggal, 0, 7) . '-01';
        $currentPeriod = $db->query('SELECT * FROM penyusutan_aset WHERE id_perusahaan=? AND id_aset_tetap=? AND periode=? FOR UPDATE',
            array($company, $assetId, $saleMonth));
        $this->ok($currentPeriod);
        $period = $currentPeriod->row();
        $openingDate = $this->opening_date($company, $disposal);
        $posted = $this->posted_depreciation($company, $assetId, $transaction->id_transaksi);
        $postedCurrent = false;
        if ($disposal->jenis_perolehan === 'saldo_awal' && $transaction->tanggal < $openingDate) {
            throw new DomainException('Tanggal pelepasan aset lama tidak boleh sebelum tanggal Saldo Awal.');
        }
        if ($period && $period->status === 'aktif') {
            $periodTransaction = $db->query('SELECT status FROM transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                array($company, $period->id_transaksi));
            $this->ok($periodTransaction);
            if (!$periodTransaction->row()) {
                throw new DomainException('Transaksi penyusutan periode pelepasan tidak ditemukan.');
            }
            if ($periodTransaction->row()->status === 'diposting') {
                $postedCurrent = true;
                $posted = array_values(array_filter($posted, function ($row) use ($saleMonth) {
                    return $row->periode !== $saleMonth;
                }));
            } elseif ($periodTransaction->row()->status !== 'dibatalkan') {
                throw new DomainException('Draft penyusutan periode pelepasan harus diposting atau dibatalkan terlebih dahulu.');
            }
        } elseif ($period && $period->status !== 'dibatalkan') {
            throw new DomainException('Status penyusutan periode pelepasan tidak valid.');
        }
        $base = $this->depreciation_base($disposal, $openingDate, $posted);
        $this->assert_contiguous($base, $disposal, $posted, $saleMonth, $openingDate);
        $expectedCurrent = $base->nilai_bulan($saleMonth);
        if ($postedCurrent && $expectedCurrent !== $period->nilai_penyusutan) {
            throw new DomainException('Penyusutan periode pelepasan tidak sesuai dengan jadwal aset.');
        }
        if ($period && $period->status === 'aktif' && !$postedCurrent
            && $expectedCurrent !== $period->nilai_penyusutan) {
            throw new DomainException('Draft penyusutan periode pelepasan harus diposting atau dibatalkan terlebih dahulu.');
        }
        $currentDep = $postedCurrent ? '0.00' : $expectedCurrent;
        if (!$postedCurrent && $this->cents($currentDep) > 0) {
            $this->ok($db->insert('penyusutan_aset', array(
                'id_perusahaan' => $company, 'id_aset_tetap' => $assetId,
                'id_transaksi' => $transaction->id_transaksi, 'periode' => $saleMonth,
                'nilai_penyusutan' => $currentDep
            )));
        }
        if ($postedCurrent) {
            $currentDep = '0.00';
        }
        $totalDep = $this->add_cents($this->cents($disposal->akumulasi_penyusutan_awal),
            $this->cents($this->sum_posted_depreciation($company, $assetId)));
        if ($this->cents($currentDep) > 0) {
            $totalDep = $this->add_cents($totalDep, $this->cents($currentDep));
        }
        $book = $this->cents($disposal->nilai_perolehan) - $totalDep;
        if ($book < $this->cents($disposal->nilai_residu) || $book < 0) {
            throw new DomainException('Nilai buku aset tidak valid.');
        }
        $proceeds = $this->cents($disposal->nilai_pelepasan);
        if ($proceeds !== $this->cents($transaction->total)) {
            throw new DomainException('Nilai hasil pelepasan tidak sesuai dengan transaksi.');
        }

        $this->account($company, $disposal->id_akun_aset, '1800');
        $this->account($company, $disposal->id_akun_akumulasi_penyusutan, '1900');
        $this->account($company, $disposal->id_akun_beban_penyusutan, null, 'Beban');
        $journal = array();
        if ($this->cents($currentDep) > 0) {
            $this->add_journal($journal, $disposal->id_akun_beban_penyusutan, $this->cents($currentDep), 0);
            $this->add_journal($journal, $disposal->id_akun_akumulasi_penyusutan, 0, $this->cents($currentDep));
        }
        $this->add_journal($journal, $disposal->id_akun_akumulasi_penyusutan, $totalDep, 0);
        $this->add_journal($journal, $disposal->id_akun_aset, 0, $this->cents($disposal->nilai_perolehan));
        if ($proceeds > 0) {
            if ($transaction->cara_pembayaran === 'tunai') {
                $this->account($company, $transaction->id_akun_kas, '1100');
                $debit = $transaction->id_akun_kas;
            } elseif ($transaction->cara_pembayaran === 'kredit') {
                $customer = $db->query('SELECT id_pelanggan FROM pelanggan WHERE id_perusahaan=? AND id_pelanggan=? AND aktif=1 FOR UPDATE',
                    array($company, $transaction->id_pelanggan));
                $this->ok($customer);
                if (!$customer->row()) {
                    throw new DomainException('Pelanggan untuk pelepasan aset tidak ditemukan atau tidak aktif.');
                }
                $debit = $this->setting($company, 'piutang_usaha', '1200');
            } else {
                throw new DomainException('Cara penerimaan hasil pelepasan tidak valid.');
            }
            $this->add_journal($journal, $debit, $proceeds, 0);
        }
        if ($proceeds > $book) {
            $gain = $this->setting($company, 'laba_pelepasan_aset', '8000');
            $this->add_journal($journal, $gain, 0, $proceeds - $book);
        } elseif ($book > $proceeds) {
            $loss = $this->setting($company, 'rugi_pelepasan_aset', '9000');
            $this->add_journal($journal, $loss, $book - $proceeds, 0);
        }
        $this->ok($db->where('id_perusahaan', $company)->where('id_pelepasan_aset', $disposal->id_pelepasan_aset)
            ->update('pelepasan_aset', array('nilai_buku' => $this->money($book))));
        $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $assetId)
            ->update('aset_tetap', array('status' => 'dilepas')));
        return $this->normalize($journal);
    }

    private function validate_perolehan($company, $date, $data, $assetId = null)
    {
        $asset = $this->validate_asset($company, $data, $assetId);
        if ($asset['tanggal_perolehan'] !== $date) {
            throw new DomainException('Tanggal perolehan aset harus sama dengan tanggal transaksi.');
        }
        if ($asset['tanggal_mulai_penyusutan'] < $date) {
            throw new DomainException('Penyusutan tidak boleh dimulai sebelum tanggal perolehan.');
        }
        $payment = isset($data['cara_pembayaran']) ? $data['cara_pembayaran'] : '';
        if (!in_array($payment, array('tunai', 'kredit'), true)) {
            throw new InvalidArgumentException('Cara pembayaran perolehan harus tunai atau kredit.');
        }
        $cash = null;
        $supplier = null;
        $due = null;
        if ($payment === 'tunai') {
            $this->id(isset($data['id_akun_kas']) ? $data['id_akun_kas'] : null);
            $cash = (int) $data['id_akun_kas'];
            $this->account($company, $cash, '1100');
        } else {
            $this->id(isset($data['id_pemasok']) ? $data['id_pemasok'] : null);
            $supplier = (int) $data['id_pemasok'];
            $query = $this->ci->db->query('SELECT id_pemasok FROM pemasok WHERE id_perusahaan=? AND id_pemasok=? AND aktif=1 FOR UPDATE',
                array($company, $supplier));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Pemasok tidak ditemukan atau tidak aktif.');
            }
            $this->date(isset($data['jatuh_tempo']) ? $data['jatuh_tempo'] : null);
            $due = $data['jatuh_tempo'];
            if ($due < $date) {
                throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal perolehan.');
            }
            $this->setting($company, 'utang_usaha', '2100');
        }
        $asset['cara_pembayaran'] = $payment;
        $asset['id_akun_kas'] = $cash;
        $asset['id_pemasok'] = $supplier;
        $asset['jatuh_tempo'] = $due;
        $asset['referensi'] = $this->text(isset($data['referensi']) ? $data['referensi'] : '', 100, 'Referensi');
        $asset['deskripsi'] = $this->description($data, 'Perolehan aset');
        if ($this->length($asset['deskripsi']) > 255) {
            throw new InvalidArgumentException('Keterangan terlalu panjang.');
        }
        return $asset;
    }

    private function validate_asset($company, $data, $assetId)
    {
        foreach (array('kode_aset', 'nama_aset', 'tanggal_perolehan', 'tanggal_mulai_penyusutan',
            'nilai_perolehan', 'nilai_residu', 'umur_manfaat_bulan', 'id_akun_aset',
            'id_akun_akumulasi_penyusutan', 'id_akun_beban_penyusutan') as $field) {
            if (!array_key_exists($field, $data)) {
                throw new InvalidArgumentException('Data aset tidak lengkap.');
            }
        }
        $code = $this->text($data['kode_aset'], 50, 'Kode aset');
        $name = $this->text($data['nama_aset'], 255, 'Nama aset');
        if ($code === '' || $name === '') {
            throw new InvalidArgumentException('Kode dan nama aset wajib diisi.');
        }
        $this->date($data['tanggal_perolehan']);
        $this->date($data['tanggal_mulai_penyusutan']);
        if ($data['tanggal_mulai_penyusutan'] < $data['tanggal_perolehan']) {
            throw new DomainException('Tanggal mulai penyusutan tidak boleh sebelum tanggal perolehan.');
        }
        $cost = $this->money($this->cents($data['nilai_perolehan']));
        $residual = $this->money($this->cents($data['nilai_residu']));
        if ($this->cents($cost) <= 0 || $this->cents($residual) > $this->cents($cost)) {
            throw new DomainException('Nilai perolehan harus lebih besar dari nol dan nilai residu tidak boleh melebihi biaya.');
        }
        $life = $data['umur_manfaat_bulan'];
        if ((!is_string($life) && !is_int($life)) || !ctype_digit((string) $life)
            || (int) $life < 1 || (int) $life > 120000) {
            throw new InvalidArgumentException('Umur manfaat harus antara 1 dan 120000 bulan.');
        }
        $this->id($data['id_akun_aset']);
        $this->id($data['id_akun_akumulasi_penyusutan']);
        $this->id($data['id_akun_beban_penyusutan']);
        $this->account($company, $data['id_akun_aset'], '1800');
        $this->account($company, $data['id_akun_akumulasi_penyusutan'], '1900');
        $this->account($company, $data['id_akun_beban_penyusutan'], null, 'Beban');
        $this->check_code($company, $code, $assetId);
        return array(
            'kode_aset' => $code, 'nama_aset' => $name, 'tanggal_perolehan' => $data['tanggal_perolehan'],
            'tanggal_mulai_penyusutan' => $data['tanggal_mulai_penyusutan'], 'nilai_perolehan' => $cost,
            'nilai_residu' => $residual, 'umur_manfaat_bulan' => (int) $life,
            'id_akun_aset' => (int) $data['id_akun_aset'],
            'id_akun_akumulasi_penyusutan' => (int) $data['id_akun_akumulasi_penyusutan'],
            'id_akun_beban_penyusutan' => (int) $data['id_akun_beban_penyusutan'],
            'akumulasi_penyusutan_awal' => '0.00'
        );
    }

    private function write_perolehan($id, $company, $data, $db, $assetId = null)
    {
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
            'cara_pembayaran' => $data['cara_pembayaran'], 'id_akun_kas' => $data['id_akun_kas'],
            'id_pemasok' => $data['id_pemasok'], 'jatuh_tempo' => $data['jatuh_tempo'],
            'referensi' => $data['referensi'], 'deskripsi' => $data['deskripsi'],
            'total_sebelum_pajak' => $data['nilai_perolehan'], 'total_pajak' => '0.00',
            'total' => $data['nilai_perolehan']
        )));
        $row = $this->asset_row($company, $id, $data);
        if ($assetId) {
            $this->ok($db->where('id_perusahaan', $company)->where('id_aset_tetap', $assetId)->update('aset_tetap', $row));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->delete('detail_transaksi'));
        } else {
            $this->ok($db->insert('aset_tetap', $row));
        }
        $this->ok($db->insert('detail_transaksi', array(
            'id_perusahaan' => $company, 'id_transaksi' => $id, 'id_akun' => $data['id_akun_aset'],
            'deskripsi' => $data['nama_aset'], 'jumlah' => '1.0000', 'harga' => $data['nilai_perolehan'],
            'nilai_sebelum_pajak' => $data['nilai_perolehan'], 'nilai_pajak' => '0.00',
            'subtotal' => $data['nilai_perolehan']
        )));
    }

    private function asset_row($company, $transactionId, $data)
    {
        return array(
            'id_perusahaan' => $company, 'kode_aset' => $data['kode_aset'], 'nama_aset' => $data['nama_aset'],
            'tanggal_perolehan' => $data['tanggal_perolehan'],
            'tanggal_mulai_penyusutan' => $data['tanggal_mulai_penyusutan'],
            'nilai_perolehan' => $data['nilai_perolehan'], 'nilai_residu' => $data['nilai_residu'],
            'umur_manfaat_bulan' => $data['umur_manfaat_bulan'], 'metode_penyusutan' => 'garis_lurus',
            'id_akun_aset' => $data['id_akun_aset'],
            'id_akun_akumulasi_penyusutan' => $data['id_akun_akumulasi_penyusutan'],
            'id_akun_beban_penyusutan' => $data['id_akun_beban_penyusutan'],
            'akumulasi_penyusutan_awal' => $data['akumulasi_penyusutan_awal'],
            'id_transaksi_perolehan' => $transactionId, 'status' => 'aktif'
        );
    }

    private function validate_pelepasan($company, $date, $data, $transactionId = null)
    {
        $this->id(isset($data['id_aset_tetap']) ? $data['id_aset_tetap'] : null);
        $query = $this->ci->db->query("SELECT a.*, t.status AS status_perolehan, t.jenis_transaksi AS jenis_perolehan
            FROM aset_tetap a JOIN transaksi t ON t.id_perusahaan=a.id_perusahaan
                AND t.id_transaksi=a.id_transaksi_perolehan
            WHERE a.id_perusahaan=? AND a.id_aset_tetap=? FOR UPDATE", array($company, $data['id_aset_tetap']));
        $this->ok($query);
        $asset = $query->row();
        if (!$asset || $asset->status !== 'aktif' || $asset->status_perolehan !== 'diposting') {
            throw new DomainException('Pilih aset aktif yang perolehannya sudah diposting.');
        }
        $existingSql = "SELECT p.id_transaksi FROM pelepasan_aset p
            JOIN transaksi t ON t.id_perusahaan=p.id_perusahaan AND t.id_transaksi=p.id_transaksi
            WHERE p.id_perusahaan=? AND p.id_aset_tetap=? AND t.status<>'dibatalkan'";
        $existingParams = array($company, $asset->id_aset_tetap);
        if ($transactionId !== null) {
            $existingSql .= ' AND p.id_transaksi<>?';
            $existingParams[] = $transactionId;
        }
        $existingSql .= ' LIMIT 1 FOR UPDATE';
        $existing = $this->ci->db->query($existingSql, $existingParams);
        $this->ok($existing);
        if ($existing->row()) {
            throw new DomainException('Aset sudah memiliki draft atau pelepasan lain.');
        }
        if ($date < $asset->tanggal_perolehan) {
            throw new DomainException('Tanggal pelepasan tidak boleh sebelum tanggal perolehan.');
        }
        if ($asset->jenis_perolehan === 'saldo_awal' && $date < $this->opening_date($company, $asset)) {
            throw new DomainException('Tanggal pelepasan aset lama tidak boleh sebelum tanggal Saldo Awal.');
        }
        $proceeds = $this->optional_money(isset($data['nilai_pelepasan']) ? $data['nilai_pelepasan'] : '0');
        $mode = isset($data['cara_pembayaran']) ? $data['cara_pembayaran'] : 'tanpa_hasil';
        if (!in_array($mode, array('tunai', 'kredit', 'tanpa_hasil'), true)) {
            throw new InvalidArgumentException('Cara penerimaan hasil pelepasan tidak valid.');
        }
        if ($this->cents($proceeds) === 0) {
            $mode = 'tanpa_hasil';
        } elseif ($mode === 'tanpa_hasil') {
            throw new DomainException('Pelepasan dengan hasil harus memilih tunai atau kredit.');
        }
        $cash = null;
        $customer = null;
        $due = null;
        if ($mode === 'tunai') {
            $this->id(isset($data['id_akun_kas']) ? $data['id_akun_kas'] : null);
            $cash = (int) $data['id_akun_kas'];
            $this->account($company, $cash, '1100');
        } elseif ($mode === 'kredit') {
            $this->id(isset($data['id_pelanggan']) ? $data['id_pelanggan'] : null);
            $customer = (int) $data['id_pelanggan'];
            $query = $this->ci->db->query('SELECT id_pelanggan FROM pelanggan WHERE id_perusahaan=? AND id_pelanggan=? AND aktif=1 FOR UPDATE',
                array($company, $customer));
            $this->ok($query);
            if (!$query->row()) {
                throw new DomainException('Pelanggan tidak ditemukan atau tidak aktif.');
            }
            $this->date(isset($data['jatuh_tempo']) ? $data['jatuh_tempo'] : null);
            $due = $data['jatuh_tempo'];
            if ($due < $date) {
                throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal pelepasan.');
            }
        }
        return array('asset' => $asset, 'nilai_pelepasan' => $proceeds, 'cara_pembayaran' => $mode,
            'id_akun_kas' => $cash, 'id_pelanggan' => $customer, 'jatuh_tempo' => $due,
            'deskripsi' => $this->description($data, 'Pelepasan aset'),
            'referensi' => $this->text(isset($data['referensi']) ? $data['referensi'] : '', 100, 'Referensi'));
    }

    private function write_pelepasan($id, $company, $data, $db)
    {
        $asset = $data['asset'];
        $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id)->update('transaksi', array(
            'cara_pembayaran' => $data['cara_pembayaran'] === 'tanpa_hasil' ? null : $data['cara_pembayaran'],
            'id_akun_kas' => $data['id_akun_kas'], 'id_pelanggan' => $data['id_pelanggan'],
            'jatuh_tempo' => $data['jatuh_tempo'], 'referensi' => $data['referensi'],
            'deskripsi' => $data['deskripsi'], 'total_sebelum_pajak' => $data['nilai_pelepasan'],
            'total_pajak' => '0.00', 'total' => $data['nilai_pelepasan']
        )));
        $this->ok($db->insert('pelepasan_aset', array(
            'id_perusahaan' => $company, 'id_aset_tetap' => $asset->id_aset_tetap,
            'id_transaksi' => $id, 'nilai_buku' => '0.00', 'nilai_pelepasan' => $data['nilai_pelepasan']
        )));
    }

    private function guard_asset_period($company, $asset, $period, $ignoreTransaction = null)
    {
        if ($asset->tanggal_mulai_penyusutan > $period) {
            return;
        }
        $existing = $this->ci->db->query("SELECT id_penyusutan_aset, id_transaksi FROM penyusutan_aset
            WHERE id_perusahaan=? AND id_aset_tetap=? AND periode=? AND status='aktif' FOR UPDATE",
            array($company, $asset->id_aset_tetap, $period));
        $this->ok($existing);
        if ($existing->row() && (string) $existing->row()->id_transaksi !== (string) $ignoreTransaction) {
            $transaction = $this->ci->db->query('SELECT status FROM transaksi WHERE id_perusahaan=? AND id_transaksi=? FOR UPDATE',
                array($company, $existing->row()->id_transaksi));
            $this->ok($transaction);
            if ($transaction->row() && $transaction->row()->status !== 'dibatalkan') {
                throw new DomainException('Periode penyusutan sudah memiliki draft atau posting aktif.');
            }
        }
    }

    private function assert_contiguous($base, $asset, $posted, $endPeriod, $openingDate)
    {
        $start = max($this->month($asset->tanggal_mulai_penyusutan), $this->next_period($openingDate));
        $startIndex = $this->month_index($start);
        $expected = $startIndex;
        $end = min($this->month_index($endPeriod),
            $this->month_index($this->month($asset->tanggal_mulai_penyusutan)) + (int) $asset->umur_manfaat_bulan);
        foreach ($posted as $row) {
            $period = $this->month_index($row->periode);
            if ($period < $startIndex || $period >= $end) {
                continue;
            }
            if ($period !== $expected) {
                throw new DomainException('Posting penyusutan harus berurutan; lengkapi periode yang terlewat terlebih dahulu.');
            }
            $expected++;
        }
        if ($expected < $end) {
            throw new DomainException('Posting penyusutan sampai bulan sebelum pelepasan terlebih dahulu.');
        }
    }

    private function depreciation_base($asset, $openingDate, $posted)
    {
        $amount = $this->cents($asset->akumulasi_penyusutan_awal);
        $cutoff = $openingDate;
        foreach ($posted as $row) {
            $amount = $this->add_cents($amount, $this->cents($row->nilai_penyusutan));
            if ($row->periode > $cutoff) {
                $cutoff = $row->periode;
            }
        }
        return new Aset_tetap_kalkulasi(array(
            'nilai_perolehan' => $asset->nilai_perolehan,
            'nilai_residu' => $asset->nilai_residu,
            'umur_manfaat_bulan' => $asset->umur_manfaat_bulan,
            'tanggal_mulai_penyusutan' => $asset->tanggal_mulai_penyusutan,
            'akumulasi_awal' => $this->money($amount),
            'tanggal_saldo_awal' => $cutoff
        ));
    }

    private function posted_depreciation($company, $assetId, $excludeTransaction = null)
    {
        $sql = "SELECT s.periode, s.nilai_penyusutan FROM penyusutan_aset s
            JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan AND t.id_transaksi=s.id_transaksi
            WHERE s.id_perusahaan=? AND s.id_aset_tetap=? AND s.status='aktif' AND t.status='diposting'";
        $params = array($company, $assetId);
        if ($excludeTransaction !== null) {
            $sql .= ' AND s.id_transaksi<>?';
            $params[] = $excludeTransaction;
        }
        $sql .= ' ORDER BY s.periode';
        $query = $this->ci->db->query($sql, $params);
        $this->ok($query);
        return $query->result();
    }

    private function sum_posted_depreciation($company, $assetId)
    {
        $query = $this->ci->db->query("SELECT COALESCE(SUM(s.nilai_penyusutan),0) AS total
            FROM penyusutan_aset s JOIN transaksi t ON t.id_perusahaan=s.id_perusahaan
                AND t.id_transaksi=s.id_transaksi
            WHERE s.id_perusahaan=? AND s.id_aset_tetap=? AND s.status='aktif' AND t.status='diposting'",
            array($company, $assetId));
        $this->ok($query);
        return $query->row()->total;
    }

    private function opening_date($company, $asset)
    {
        if ($asset->jenis_perolehan !== 'saldo_awal') {
            return $this->previous_period($this->month($asset->tanggal_mulai_penyusutan));
        }
        $query = $this->ci->db->query('SELECT tanggal FROM transaksi
            WHERE id_perusahaan=? AND id_transaksi=? AND jenis_transaksi=\'saldo_awal\' FOR UPDATE',
            array($company, $asset->id_transaksi_perolehan));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Tanggal Saldo Awal untuk aset lama tidak ditemukan.');
        }
        return $query->row()->tanggal;
    }

    private function calculator($asset, $cutoff, $opening)
    {
        return new Aset_tetap_kalkulasi(array(
            'nilai_perolehan' => $asset['nilai_perolehan'], 'nilai_residu' => $asset['nilai_residu'],
            'umur_manfaat_bulan' => $asset['umur_manfaat_bulan'],
            'tanggal_mulai_penyusutan' => $asset['tanggal_mulai_penyusutan'],
            'akumulasi_awal' => $opening, 'tanggal_saldo_awal' => $cutoff
        ));
    }

    private function check_code($company, $code, $excludeAssetId = null)
    {
        $sql = 'SELECT id_aset_tetap FROM aset_tetap WHERE id_perusahaan=? AND kode_aset=?';
        $params = array($company, $code);
        if ($excludeAssetId !== null) {
            $sql .= ' AND id_aset_tetap<>?';
            $params[] = $excludeAssetId;
        }
        $query = $this->ci->db->query($sql . ' FOR UPDATE', $params);
        $this->ok($query);
        if ($query->row()) {
            throw new DomainException('Kode aset sudah digunakan.');
        }
    }

    private function account($company, $id, $root, $type = null)
    {
        $query = $this->ci->db->query('SELECT * FROM akun WHERE id_perusahaan=? AND id_akun=? FOR UPDATE',
            array($company, $id));
        $this->ok($query);
        $account = $query->row();
        if (!$account) {
            throw new DomainException('Akun aset tetap tidak ditemukan.');
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
            if ($root !== null && $account->kode_akun === $root) {
                $found = true;
            }
            if ($type !== null && $account->tipe_akun !== $type) {
                throw new DomainException('Tipe akun tidak sesuai untuk transaksi aset tetap.');
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
        if ($root !== null && !$found) {
            throw new DomainException('Akun tidak termasuk kelompok ' . $root . '.');
        }
    }

    private function setting($company, $key, $root)
    {
        $query = $this->ci->db->query('SELECT id_akun FROM pengaturan_akun
            WHERE id_perusahaan=? AND jenis_pengaturan=? FOR UPDATE', array($company, $key));
        $this->ok($query);
        if (!$query->row()) {
            throw new DomainException('Pengaturan akun ' . $key . ' belum tersedia.');
        }
        $id = $query->row()->id_akun;
        $this->account($company, $id, $root);
        return $id;
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

    private function normalize($journal)
    {
        $rows = array();
        foreach ($journal as $row) {
            $net = $row['debit_cents'] - $row['credit_cents'];
            if ($net !== 0) {
                $rows[] = array('id_akun' => $row['id_akun'],
                    'debit' => $net > 0 ? $this->money($net) : '0.00',
                    'kredit' => $net < 0 ? $this->money(-$net) : '0.00');
            }
        }
        return $rows;
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
        if (!is_string($date) || !preg_match('/^[1-9]\\d{3}-\\d{2}-\\d{2}$/D', $date)
            || !($value = DateTime::createFromFormat('!Y-m-d', $date)) || $value->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Tanggal tidak valid.');
        }
    }

    private function month($date)
    {
        return substr($date, 0, 7) . '-01';
    }

    private function previous_period($date)
    {
        return date('Y-m-01', strtotime($date . ' -1 month'));
    }

    private function next_period($date)
    {
        return date('Y-m-01', strtotime($date . ' +1 month'));
    }

    private function month_index($date)
    {
        return (int) substr($date, 0, 4) * 12 + (int) substr($date, 5, 2) - 1;
    }

    private function optional_money($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\\d{1,13}(?:\\.\\d{1,2})?$/D', (string) $amount)) {
            throw new InvalidArgumentException('Nominal tidak valid.');
        }
        return $this->money($this->cents($amount));
    }

    private function cents($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\\d{1,13}(?:\\.\\d{1,2})?$/D', (string) $amount)) {
            throw new DomainException('Nominal melebihi batas database atau tidak valid.');
        }
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }

    private function add_cents($left, $right)
    {
        if ($right < 0 || $left > 999999999999999 - $right) {
            throw new DomainException('Total melebihi batas database.');
        }
        return $left + $right;
    }

    private function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function text($value, $max, $label)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        $value = trim($value);
        if ($this->length($value) > $max) {
            throw new InvalidArgumentException($label . ' terlalu panjang.');
        }
        return $value === '' ? null : $value;
    }

    private function description($data, $fallback)
    {
        if (isset($data['deskripsi']) && !is_string($data['deskripsi'])) {
            throw new InvalidArgumentException('Keterangan tidak valid.');
        }
        $description = isset($data['deskripsi']) ? trim($data['deskripsi']) : '';
        return $description === '' ? $fallback : $description;
    }

    private function length($value)
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database aset tetap gagal.');
        }
    }
}
