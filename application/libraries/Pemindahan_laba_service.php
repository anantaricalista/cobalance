<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pemindahan_laba_service
{
    const KODE_BERJALAN = '3103';
    const KODE_DITAHAN = '3102';
    const BATAS_BARIS = 1000;

    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
        $this->ci->load->library('session');
        $this->ci->load->library('transaksi_service');
    }

    public function tabel_tersedia()
    {
        return $this->ci->db->table_exists('pemindahan_laba');
    }

    public function tahun_tersedia()
    {
        $company = $this->company();
        $akun = $this->akun($company);
        $query = $this->query($this->subtree_sql() . "
            SELECT MIN(YEAR(j.tanggal)) AS tahun_awal
            FROM detail_jurnal d
            JOIN jurnal_transaksi j ON j.id_jurnal = d.id_jurnal
            JOIN subtree s ON s.id_akun = d.id_akun
            WHERE j.id_perusahaan = ? AND " . $this->bukan_pemindahan_sql(),
            array($company, $akun['berjalan']->id_akun, $company, $company, $company));
        $row = $query->row();
        $years = array();
        if ($row && $row->tahun_awal !== null) {
            for ($year = $this->tahun_berjalan() - 1; $year >= max(2000, (int) $row->tahun_awal); $year--) {
                $years[] = $year;
            }
        }
        return $years;
    }

    public function pratinjau($tahun)
    {
        $company = $this->company();
        $tahun = $this->tahun($tahun);
        $akun = $this->akun($company);
        $id_berjalan = $akun['berjalan']->id_akun;

        $totals = array('belum' => array(0, 0), 'draft' => array(0, 0), 'diposting' => array(0, 0));
        $counts = array('belum' => 0, 'draft' => 0, 'diposting' => 0);
        foreach ($this->baris($company, $tahun, $id_berjalan,
            $this->bucket_sql() . ' AS bucket, COUNT(*) AS jumlah, SUM(d.debit) AS debit, SUM(d.kredit) AS kredit',
            'GROUP BY bucket') as $row) {
            $totals[$row->bucket] = array($this->cents($row->kredit), $this->cents($row->debit));
            $counts[$row->bucket] = (int) $row->jumlah;
        }
        $net = array();
        foreach ($totals as $bucket => $value) {
            $net[$bucket] = $value[0] - $value[1];
        }
        $total_net = $net['belum'] + $net['draft'] + $net['diposting'];

        $query = $this->query("SELECT p.id_pemindahan_laba, p.jenis, p.jumlah, p.tahun, t.id_transaksi,
            t.nomor_transaksi, t.status, t.tanggal
            FROM pemindahan_laba p
            JOIN transaksi t ON t.id_perusahaan = p.id_perusahaan AND t.id_transaksi = p.id_transaksi
            WHERE p.id_perusahaan = ? AND p.tahun = ? ORDER BY p.id_pemindahan_laba",
            array($company, $tahun));
        $pemindahan = array();
        $ada_draft = false;
        $ada_posting = false;
        foreach ($query->result() as $row) {
            $ada_draft = $ada_draft || $row->status === 'draft';
            $ada_posting = $ada_posting || $row->status === 'diposting';
            $pemindahan[] = array('id_transaksi' => (int) $row->id_transaksi, 'nomor' => $row->nomor_transaksi,
                'tanggal' => $row->tanggal, 'status' => $row->status, 'jenis' => $row->jenis,
                'jumlah' => format_nominal($row->jumlah));
        }

        $semua = $counts['belum'] + $counts['draft'] + $counts['diposting'];
        if ($net['belum'] !== 0) {
            $aksi = $ada_draft ? 'perbarui_draft' : ($ada_posting ? 'buat_selisih' : 'buat');
        } elseif ($ada_draft) {
            $aksi = 'posting';
        } elseif ($ada_posting) {
            $aksi = 'selesai';
        } else {
            $aksi = 'kosong';
        }

        $lines = array();
        $rows = $this->baris($company, $tahun, $id_berjalan,
            'd.id_detail, j.tanggal, j.referensi, j.deskripsi AS jurnal, a.kode_akun, a.nama_akun, d.debit, d.kredit, '
            . 'x.id_transaksi AS id_pemindahan, x.nomor_transaksi AS nomor_pemindahan, ' . $this->bucket_sql() . ' AS bucket',
            'ORDER BY j.tanggal, j.id_jurnal, d.id_detail LIMIT ' . self::BATAS_BARIS);
        foreach ($rows as $row) {
            $lines[] = array('tanggal' => $row->tanggal, 'referensi' => $row->referensi, 'jurnal' => $row->jurnal,
                'akun' => $row->kode_akun . ' - ' . $row->nama_akun,
                'debit' => format_nominal($row->debit), 'kredit' => format_nominal($row->kredit),
                'status' => $row->bucket, 'id_pemindahan' => $row->id_pemindahan === null ? null : (int) $row->id_pemindahan,
                'nomor_pemindahan' => $row->nomor_pemindahan);
        }

        return array(
            'tahun' => $tahun,
            'tanggal_jurnal' => ($tahun + 1) . '-01-01',
            'akun_berjalan' => $akun['berjalan']->kode_akun . ' - ' . $akun['berjalan']->nama_akun,
            'akun_ditahan' => $akun['ditahan']->kode_akun . ' - ' . $akun['ditahan']->nama_akun,
            'total' => format_nominal($this->money($total_net)),
            'diposting' => format_nominal($this->money($net['diposting'])),
            'draft' => format_nominal($this->money($net['draft'])),
            'belum' => format_nominal($this->money($net['belum'])),
            'laba' => $total_net >= 0,
            'jumlah_baris' => $semua,
            'baris_belum' => $counts['belum'],
            'aksi' => $aksi,
            'pemindahan' => $pemindahan,
            'baris' => $lines,
            'dipotong' => $semua > self::BATAS_BARIS
        );
    }

    public function generate($tahun)
    {
        $company = $this->company();
        $tahun = $this->tahun($tahun);
        $akun = $this->akun($company);
        if ($this->draft_terbuka($company, $tahun, false)) {
            return $this->ci->transaksi_service->atomic(function ($company, $pengguna) use ($tahun, $akun) {
                $draft = $this->draft_terbuka($company, $tahun, true);
                if (!$draft) {
                    throw new DomainException('Draft pemindahan sudah berubah. Muat ulang pratinjau.');
                }
                $lines = $this->baris_jurnal($company, $tahun, $akun['berjalan']->id_akun, array('belum', 'draft'));
                $net = $this->tulis($company, $draft->id_transaksi, $tahun, $draft->jenis, $akun, $lines, $draft);
                return array('id_transaksi' => (int) $draft->id_transaksi, 'nomor_transaksi' => $draft->nomor_transaksi,
                    'hasil' => 'diperbarui', 'jumlah' => format_nominal($this->money($net)));
            });
        }

        $jenis = $this->ada_posting($company, $tahun) ? 'selisih' : 'awal';
        $deskripsi = 'Pemindahan Saldo Laba (Rugi) Berjalan ' . $tahun . ' ke Saldo Laba (Rugi) Ditahan'
            . ($jenis === 'selisih' ? ' (selisih)' : '');
        $net = 0;
        $result = $this->ci->transaksi_service->buat_draft('penyesuaian_akuntansi', ($tahun + 1) . '-01-01', $deskripsi,
            function ($id, $company) use ($tahun, $akun, $jenis, &$net) {
                if ($this->draft_terbuka($company, $tahun, true)
                    || ($this->ada_posting($company, $tahun) ? 'selisih' : 'awal') !== $jenis) {
                    throw new DomainException('Data pemindahan sudah berubah. Muat ulang pratinjau.');
                }
                $lines = $this->baris_jurnal($company, $tahun, $akun['berjalan']->id_akun, array('belum'));
                $net = $this->tulis($company, $id, $tahun, $jenis, $akun, $lines, null);
            });
        $result['hasil'] = $jenis === 'selisih' ? 'selisih' : 'dibuat';
        $result['jumlah'] = format_nominal($this->money($net));
        return $result;
    }

    public function untuk_transaksi($id)
    {
        if (!$this->tabel_tersedia() || (!is_string($id) && !is_int($id)) || !ctype_digit((string) $id)) {
            return null;
        }
        $query = $this->query('SELECT * FROM pemindahan_laba WHERE id_perusahaan = ? AND id_transaksi = ?',
            array($this->company(), $id));
        return $query->row() ?: null;
    }

    // Dipanggil di dalam transaksi posting, sehingga perusahaan sudah terkunci.
    public function validasi_posting($company, $id_transaksi)
    {
        $query = $this->query('SELECT * FROM pemindahan_laba WHERE id_perusahaan = ? AND id_transaksi = ? FOR UPDATE',
            array($company, $id_transaksi));
        $data = $query->row();
        if (!$data) {
            throw new DomainException('Data pemindahan laba tidak ditemukan.');
        }
        $akun = $this->akun($company);
        if ((int) $akun['berjalan']->id_akun !== (int) $data->id_akun_berjalan
            || (int) $akun['ditahan']->id_akun !== (int) $data->id_akun_ditahan) {
            throw new DomainException('Akun Saldo Laba (Rugi) telah berubah. Perbarui draft pemindahan sebelum posting.');
        }
        $belum = 0;
        $draft = 0;
        foreach ($this->baris($company, (int) $data->tahun, $data->id_akun_berjalan,
            'd.id_detail, d.debit, d.kredit, x.id_transaksi AS id_pemindahan, ' . $this->bucket_sql() . ' AS bucket') as $row) {
            $net = $this->cents($row->kredit) - $this->cents($row->debit);
            if ($row->bucket === 'belum') {
                $belum += $net;
            } elseif ($row->bucket === 'draft' && (int) $row->id_pemindahan === (int) $id_transaksi) {
                $draft += $net;
            }
        }
        if ($belum !== 0) {
            throw new DomainException('Ada transaksi baru pada tahun ' . $data->tahun
                . ' yang belum masuk draft. Perbarui draft di menu Pindahkan Saldo Laba Berjalan, lalu posting.');
        }
        if ($draft !== $this->cents($data->jumlah)) {
            throw new DomainException('Draft pemindahan tidak lagi sesuai dengan transaksi tahun ' . $data->tahun
                . '. Perbarui draft sebelum posting.');
        }
        return $data;
    }

    private function tulis($company, $id_transaksi, $tahun, $jenis, $akun, $lines, $existing)
    {
        $net = 0;
        foreach ($lines as $line) {
            $net += $line['kredit'] - $line['debit'];
        }
        if (!$lines) {
            throw new DomainException('Tidak ada transaksi Saldo Laba (Rugi) Berjalan tahun ' . $tahun . ' yang perlu dipindahkan.');
        }
        if ($net === 0) {
            throw new DomainException('Nilai bersih transaksi yang belum dipindahkan Rp 0, sehingga jurnal pemindahan tidak diperlukan.');
        }
        $db = $this->ci->db;
        $amount = $this->money(abs($net));
        $rows = array(
            array($akun['berjalan']->id_akun, $net > 0 ? $amount : '0.00', $net > 0 ? '0.00' : $amount),
            array($akun['ditahan']->id_akun, $net > 0 ? '0.00' : $amount, $net > 0 ? $amount : '0.00')
        );
        $note = 'Pemindahan saldo laba (rugi) berjalan tahun ' . $tahun;
        if ($existing) {
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id_transaksi)->delete('detail_penyesuaian'));
            $this->ok($db->where('id_perusahaan', $company)->where('id_transaksi', $id_transaksi)->delete('pemindahan_laba_detail'));
            $this->ok($db->where('id_pemindahan_laba', $existing->id_pemindahan_laba)->where('id_perusahaan', $company)
                ->update('pemindahan_laba', array('jumlah' => $this->money($net))));
        } else {
            $this->ok($db->insert('pemindahan_laba', array(
                'id_perusahaan' => $company, 'tahun' => $tahun, 'id_transaksi' => $id_transaksi, 'jenis' => $jenis,
                'id_akun_berjalan' => $akun['berjalan']->id_akun, 'id_akun_ditahan' => $akun['ditahan']->id_akun,
                'jumlah' => $this->money($net), 'id_pengguna' => $this->ci->session->userdata('id_pengguna'))));
        }
        foreach ($rows as $row) {
            $this->ok($db->insert('detail_penyesuaian', array('id_perusahaan' => $company, 'id_transaksi' => $id_transaksi,
                'id_akun' => $row[0], 'debit' => $row[1], 'kredit' => $row[2], 'deskripsi' => $note)));
        }
        foreach (array_chunk($lines, 500) as $chunk) {
            $batch = array();
            foreach ($chunk as $line) {
                $batch[] = array('id_perusahaan' => $company, 'id_transaksi' => $id_transaksi, 'id_detail' => $line['id_detail']);
            }
            $this->ok($db->insert_batch('pemindahan_laba_detail', $batch));
        }
        return $net;
    }

    private function baris_jurnal($company, $tahun, $id_berjalan, $buckets)
    {
        $lines = array();
        foreach ($this->baris($company, $tahun, $id_berjalan,
            'd.id_detail, d.debit, d.kredit, ' . $this->bucket_sql() . ' AS bucket', 'ORDER BY d.id_detail') as $row) {
            if (in_array($row->bucket, $buckets, true)) {
                $lines[] = array('id_detail' => (int) $row->id_detail,
                    'debit' => $this->cents($row->debit), 'kredit' => $this->cents($row->kredit));
            }
        }
        return $lines;
    }

    private function baris($company, $tahun, $id_berjalan, $select, $tambahan = '')
    {
        $sql = $this->subtree_sql() . "
            SELECT $select
            FROM detail_jurnal d
            JOIN jurnal_transaksi j ON j.id_jurnal = d.id_jurnal
            JOIN subtree s ON s.id_akun = d.id_akun
            JOIN akun a ON a.id_perusahaan = j.id_perusahaan AND a.id_akun = d.id_akun
            LEFT JOIN (
                SELECT pd.id_detail, pt.id_transaksi, pt.nomor_transaksi, pt.status
                FROM pemindahan_laba_detail pd
                JOIN transaksi pt ON pt.id_perusahaan = pd.id_perusahaan AND pt.id_transaksi = pd.id_transaksi
                WHERE pd.id_perusahaan = ? AND pt.status <> 'dibatalkan'
            ) x ON x.id_detail = d.id_detail
            WHERE j.id_perusahaan = ? AND j.tanggal >= ? AND j.tanggal <= ?
              AND " . $this->bukan_pemindahan_sql() . " $tambahan";
        return $this->query($sql, array($company, $id_berjalan, $company, $company, $company,
            $tahun . '-01-01', $tahun . '-12-31', $company))->result();
    }

    private function subtree_sql()
    {
        return 'WITH RECURSIVE subtree (id_akun) AS (
            SELECT id_akun FROM akun WHERE id_perusahaan = ? AND id_akun = ?
            UNION ALL
            SELECT a.id_akun FROM akun a JOIN subtree s ON a.id_akun_induk = s.id_akun WHERE a.id_perusahaan = ?
        )';
    }

    // Jurnal pemindahan sendiri (termasuk pembaliknya) tidak dihitung sebagai laba tahun berjalan.
    private function bukan_pemindahan_sql()
    {
        return 'NOT EXISTS (SELECT 1 FROM transaksi_jurnal tj
            JOIN pemindahan_laba p ON p.id_perusahaan = tj.id_perusahaan AND p.id_transaksi = tj.id_transaksi
            WHERE tj.id_perusahaan = ? AND tj.id_jurnal = j.id_jurnal)';
    }

    private function bucket_sql()
    {
        return "CASE WHEN x.status = 'diposting' THEN 'diposting' WHEN x.status = 'draft' THEN 'draft' ELSE 'belum' END";
    }

    private function draft_terbuka($company, $tahun, $lock)
    {
        $query = $this->query("SELECT p.*, t.nomor_transaksi FROM pemindahan_laba p
            JOIN transaksi t ON t.id_perusahaan = p.id_perusahaan AND t.id_transaksi = p.id_transaksi
            WHERE p.id_perusahaan = ? AND p.tahun = ? AND t.status = 'draft' LIMIT 1" . ($lock ? ' FOR UPDATE' : ''),
            array($company, $tahun));
        return $query->row() ?: null;
    }

    private function ada_posting($company, $tahun)
    {
        $query = $this->query("SELECT p.id_transaksi FROM pemindahan_laba p
            JOIN transaksi t ON t.id_perusahaan = p.id_perusahaan AND t.id_transaksi = p.id_transaksi
            WHERE p.id_perusahaan = ? AND p.tahun = ? AND t.status = 'diposting' LIMIT 1", array($company, $tahun));
        return (bool) $query->row();
    }

    private function akun($company)
    {
        $query = $this->query("SELECT id_akun, kode_akun, nama_akun FROM akun
            WHERE id_perusahaan = ? AND tipe_akun = 'Ekuitas' AND kode_akun IN (?, ?)",
            array($company, self::KODE_BERJALAN, self::KODE_DITAHAN));
        $akun = array();
        foreach ($query->result() as $row) {
            $key = $row->kode_akun === self::KODE_BERJALAN ? 'berjalan' : 'ditahan';
            if (isset($akun[$key])) {
                throw new DomainException('Kode akun ' . $row->kode_akun . ' tidak boleh ganda.');
            }
            $akun[$key] = $row;
        }
        if (!isset($akun['berjalan'], $akun['ditahan'])) {
            throw new DomainException('Akun ' . self::KODE_BERJALAN . ' Saldo Laba (Rugi) Berjalan dan '
                . self::KODE_DITAHAN . ' Saldo Laba (Rugi) Ditahan bertipe Ekuitas wajib tersedia.');
        }
        $child = $this->query('SELECT id_akun FROM akun WHERE id_perusahaan = ? AND id_akun_induk = ? LIMIT 1',
            array($company, $akun['ditahan']->id_akun));
        if ($child->row()) {
            throw new DomainException('Akun Saldo Laba (Rugi) Ditahan harus berupa akun rinci tanpa turunan.');
        }
        return $akun;
    }

    private function tahun($value)
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^\d{4}$/D', (string) $value)
            || (int) $value < 2000 || (int) $value >= $this->tahun_berjalan()) {
            throw new InvalidArgumentException('Pilih tahun sebelum tahun berjalan.');
        }
        return (int) $value;
    }

    private function tahun_berjalan()
    {
        return (int) date('Y');
    }

    private function company()
    {
        $company = $this->ci->session->userdata('id_perusahaan');
        $user = $this->ci->session->userdata('id_pengguna');
        if (!ctype_digit((string) $company) || !ctype_digit((string) $user)) {
            throw new DomainException('Sesi perusahaan tidak valid.');
        }
        $query = $this->query('SELECT id_pengguna FROM pengguna WHERE id_perusahaan = ? AND id_pengguna = ?',
            array($company, $user));
        if (!$query->row()) {
            throw new DomainException('Pengguna tidak termasuk perusahaan ini.');
        }
        return (int) $company;
    }

    private function query($sql, $params)
    {
        $query = $this->ci->db->query($sql, $params);
        $this->ok($query);
        return $query;
    }

    private function cents($value)
    {
        if ($value === null || !preg_match('/^(-?)(\d{1,15})(?:\.(\d{1,2}))?$/D', (string) $value, $parts)) {
            throw new RuntimeException('Nominal jurnal tidak valid.');
        }
        $cents = (int) $parts[2] * 100 + (int) str_pad(isset($parts[3]) ? $parts[3] : '', 2, '0');
        return $parts[1] === '-' ? -$cents : $cents;
    }

    private function money($cents)
    {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    private function ok($result)
    {
        if (!$result) {
            throw new RuntimeException('Operasi database pemindahan laba gagal.');
        }
    }
}
