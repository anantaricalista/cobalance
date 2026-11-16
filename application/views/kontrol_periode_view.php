<div class="container-fluid">
    <h3>Akuntansi &mdash; Tutup Buku</h3>
    <p class="text-muted">Periksa jurnal, buku pembantu, persediaan, aset tetap, dan rekonsiliasi bank sebelum periode ditutup.</p>
    <?php if ($message): ?><div class="alert alert-<?= html_escape($message_type ?: 'info') ?>" role="alert"><?= html_escape($message) ?></div><?php endif; ?>
    <?php if (!$schema_ready): ?>
        <div class="alert alert-warning">Fitur belum tersedia. Terapkan migrasi <code>application/sql/20261116_pengendalian_periode_rekonsiliasi.sql</code> pada database terlebih dahulu.</div>
    <?php else: ?>
        <form class="form-inline" method="get" action="<?= site_url('kontrol_periode') ?>">
            <label for="year-filter">Tahun</label>
            <input id="year-filter" class="form-control" type="number" min="1000" max="9999" name="tahun" value="<?= (int) $year ?>">
            <label for="period-filter">Periode pemeriksaan</label>
            <input id="period-filter" class="form-control" type="month" name="bulan" value="<?= html_escape(substr($period, 0, 7)) ?>">
            <button class="btn btn-default" type="submit">Tampilkan</button>
        </form>

        <h4>Periode <?= (int) $year ?></h4>
        <div class="table-responsive"><table class="table table-bordered table-condensed">
            <thead><tr><th>Periode</th><th>Status</th><th>Tindakan</th></tr></thead>
            <tbody>
            <?php
            $periodMap = array();
            foreach ($periods as $item) $periodMap[$item->periode] = $item;
            for ($month = 1; $month <= 12; $month++):
                $monthPeriod = sprintf('%04d-%02d-01', $year, $month);
                $closed = isset($periodMap[$monthPeriod]) && $periodMap[$monthPeriod]->status === 'ditutup';
                $isFuture = substr($monthPeriod, 0, 7) > date('Y-m');
            ?>
                <tr>
                    <td><?= html_escape(date('F Y', strtotime($monthPeriod))) ?></td>
                    <td><?= $closed ? 'Ditutup' : 'Terbuka' ?></td>
                    <td><a class="btn btn-xs btn-default" href="<?= site_url('kontrol_periode?periode=' . rawurlencode($monthPeriod) . '&tahun=' . $year) ?>">Periksa</a>
                        <?php if ($is_admin && $closed && $latest_closed === $monthPeriod): ?>
                            <form method="post" action="<?= site_url('kontrol_periode/buka_kembali') ?>" style="display:inline-block">
                                <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                                <?php if ($this->config->item('csrf_protection')): ?>
                                    <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                                <?php endif; ?>
                                <input type="hidden" name="periode" value="<?= html_escape($monthPeriod) ?>">
                                <input class="form-control input-sm" name="alasan" maxlength="500" placeholder="Alasan buka kembali" required>
                                <button class="btn btn-xs btn-warning" type="submit">Buka kembali</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table></div>

        <?php if ($checks): ?>
            <h4>Pemeriksaan <?= html_escape(date('F Y', strtotime($period))) ?></h4>
            <div class="table-responsive"><table class="table table-bordered">
                <thead><tr><th>Pemeriksaan</th><th>Nilai aktual</th><th>Target</th><th>Status</th><th>Catatan</th></tr></thead>
                <tbody>
                <?php foreach ($checks['checks'] as $check): ?>
                    <tr class="<?= $check['ok'] ? 'success' : 'danger' ?>">
                        <td><?= html_escape($check['label']) ?></td>
                        <td><?= html_escape(is_scalar($check['actual']) ? (string) $check['actual'] : json_encode($check['actual'])) ?></td>
                        <td><?= html_escape(is_scalar($check['expected']) ? (string) $check['expected'] : json_encode($check['expected'])) ?></td>
                        <td><?= $check['ok'] ? 'Siap' : 'Perlu tindakan' ?></td>
                        <td><?= html_escape($check['note']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php
            $selectedClosed = $selected_closed;
            $selectedFuture = substr($period, 0, 7) > date('Y-m');
            ?>
            <?php if ($is_admin && !$selectedClosed && !$selectedFuture): ?>
                <form method="post" action="<?= site_url('kontrol_periode/tutup') ?>" class="form-inline">
                    <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                    <?php if ($this->config->item('csrf_protection')): ?>
                        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="periode" value="<?= html_escape($period) ?>">
                    <label for="close-note">Catatan penutupan</label>
                    <input id="close-note" class="form-control" name="catatan" maxlength="500" placeholder="Opsional">
                    <button class="btn btn-danger" type="submit"<?= $checks['ready'] ? '' : ' disabled' ?>>Tutup periode</button>
                    <?php if (!$checks['ready']): ?><span class="text-danger">Selesaikan semua pemeriksaan yang gagal terlebih dahulu.</span><?php endif; ?>
                </form>
            <?php elseif ($selectedClosed): ?>
                <p class="alert alert-info">Periode ini telah ditutup. Hanya Admin Perusahaan yang dapat membuka kembali periode tertutup paling akhir.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($history)): ?>
            <h4>Riwayat periode <?= html_escape($period) ?></h4>
            <div class="table-responsive"><table class="table table-condensed table-bordered">
                <thead><tr><th>Waktu</th><th>Tindakan</th><th>Pengguna</th><th>Catatan</th></tr></thead>
                <tbody><?php foreach ($history as $item): ?>
                    <tr><td><?= html_escape($item->dibuat_pada) ?></td><td><?= html_escape($item->aksi) ?></td>
                        <td><?= html_escape($item->nama_lengkap ?: 'Tidak tersedia') ?></td><td><?= html_escape((string) $item->alasan) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>

        <h4>Klasifikasi Akun Kas dan Bank</h4>
        <p class="text-muted">Setiap akun rincian di bawah kelompok 1100 harus diklasifikasikan agar pemeriksaan rekonsiliasi mencakup akun yang benar.</p>
        <div class="table-responsive"><table class="table table-striped table-condensed">
            <thead><tr><th>Kode</th><th>Akun</th><th>Klasifikasi</th><th></th></tr></thead><tbody>
            <?php foreach ($cash_accounts as $account): if ((int) $account->has_children) continue; ?>
                <tr><td><?= html_escape($account->kode_akun) ?></td><td><?= html_escape($account->nama_akun) ?></td>
                    <td colspan="2">
                        <?php if ($can_manage): ?>
                            <form method="post" action="<?= site_url('kontrol_periode/klasifikasikan_akun') ?>" class="form-inline">
                            <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                            <?php if ($this->config->item('csrf_protection')): ?>
                                <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                            <?php endif; ?>
                            <input type="hidden" name="id_akun" value="<?= (int) $account->id_akun ?>">
                            <select class="form-control input-sm" name="jenis" required>
                                <option value="">Pilih klasifikasi</option>
                                <option value="rekening_bank"<?= $account->jenis === 'rekening_bank' ? ' selected' : '' ?>>Rekening bank</option>
                                <option value="kas_tunai"<?= $account->jenis === 'kas_tunai' ? ' selected' : '' ?>>Kas tunai</option>
                            </select>
                            <button class="btn btn-xs btn-primary" type="submit">Simpan</button>
                            </form>
                        <?php else: ?>
                            <?= html_escape($account->jenis === 'rekening_bank' ? 'Rekening bank' : ($account->jenis === 'kas_tunai' ? 'Kas tunai' : 'Belum diklasifikasikan')) ?>
                        <?php endif; ?>
                    </td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
