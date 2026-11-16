<div class="container-fluid">
    <h3>Keuangan &mdash; Rekonsiliasi Bank</h3>
    <p class="text-muted">Masukkan saldo dan mutasi rekening koran, cocokkan satu per satu dengan jurnal, lalu selesaikan rekonsiliasi.</p>
    <p class="text-muted">Gunakan titik sebagai desimal tanpa pemisah ribuan, misalnya 1000.50. Maksimal 200 mutasi per rekening dan bulan.</p>
    <?php if ($message): ?><div class="alert alert-<?= html_escape($message_type ?: 'info') ?>" role="alert"><?= html_escape($message) ?></div><?php endif; ?>
    <?php if (!$schema_ready): ?>
        <div class="alert alert-warning">Fitur belum tersedia. Terapkan migrasi <code>application/sql/20261116_pengendalian_periode_rekonsiliasi.sql</code> pada database terlebih dahulu.</div>
    <?php else: ?>
        <form class="form-inline" method="get" action="<?= site_url('rekonsiliasi_bank') ?>">
            <label for="bank-month">Periode</label>
            <input id="bank-month" class="form-control" type="month" name="bulan" value="<?= html_escape(substr($period, 0, 7)) ?>">
            <label for="bank-account">Rekening</label>
            <select id="bank-account" class="form-control" name="akun" required>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= (int) $account->id_akun ?>"<?= (string) $account_id === (string) $account->id_akun ? ' selected' : '' ?>>
                        <?= html_escape($account->kode_akun . ' - ' . $account->nama_akun) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-default" type="submit">Tampilkan</button>
        </form>

        <?php if (!$accounts): ?>
            <div class="alert alert-info">Belum ada akun rekening bank aktif. Klasifikasikan akun rincian kas/bank di menu Akuntansi &rarr; Tutup Buku.</div>
        <?php elseif (!$reconciliation): ?>
            <div class="alert alert-info">Belum ada rekening koran untuk rekening dan periode ini.</div>
        <?php else: ?>
            <h4><?= html_escape($reconciliation->kode_akun . ' - ' . $reconciliation->nama_akun) ?> &mdash; <?= html_escape(date('F Y', strtotime($period))) ?></h4>
            <p>Status: <strong><?= !empty($reconciliation->belum_tersimpan) ? 'Belum diinput' : ($reconciliation->status === 'selesai' ? 'Selesai' : 'Draft') ?></strong>
                <?php if ($period_closed): ?> | Periode telah ditutup; buka kembali periode untuk melakukan perubahan.<?php endif; ?>
            </p>
            <?php $editable = $can_edit && !$period_closed; ?>

            <?php if ($editable): ?>
                <form method="post" action="<?= site_url('rekonsiliasi_bank/simpan') ?>" id="bank-statement-form">
                    <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                    <input type="hidden" name="jumlah_baris" id="statement-count" value="<?= count($reconciliation->mutasi) ?>">
                    <?php if ($this->config->item('csrf_protection')): ?>
                        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="periode" value="<?= html_escape($period) ?>">
                    <input type="hidden" name="id_akun" value="<?= (int) $account_id ?>">
                    <div class="row">
                        <div class="col-sm-3 form-group">
                            <label for="opening-balance">Saldo awal rekening koran</label>
                            <input id="opening-balance" class="form-control" type="number" step="0.01" name="saldo_awal"
                                value="<?= html_escape($reconciliation->saldo_awal) ?>" required>
                        </div>
                        <div class="col-sm-3 form-group">
                            <label for="closing-balance">Saldo akhir rekening koran</label>
                            <input id="closing-balance" class="form-control" type="number" step="0.01" name="saldo_akhir"
                                value="<?= html_escape($reconciliation->saldo_akhir) ?>" required>
                        </div>
                    </div>
                    <h4>Mutasi Rekening Koran</h4>
                    <p class="text-warning">Menyimpan ulang rekening koran akan mengganti seluruh baris dan menghapus pencocokan yang sudah dilakukan.</p>
                    <div class="table-responsive"><table class="table table-bordered table-condensed" id="statement-lines">
                        <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Arah</th><th>Jumlah</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($reconciliation->mutasi as $i => $line): ?>
                            <tr>
                                <td><input class="form-control input-sm" type="date" name="mutasi[<?= (int) $i ?>][tanggal]" value="<?= html_escape($line->tanggal) ?>"></td>
                                <td><input class="form-control input-sm" maxlength="255" name="mutasi[<?= (int) $i ?>][keterangan]" value="<?= html_escape($line->keterangan) ?>"></td>
                                <td><select class="form-control input-sm" name="mutasi[<?= (int) $i ?>][arah]">
                                    <option value="masuk"<?= $line->arah === 'masuk' ? ' selected' : '' ?>>Masuk</option>
                                    <option value="keluar"<?= $line->arah === 'keluar' ? ' selected' : '' ?>>Keluar</option>
                                </select></td>
                                <td><input class="form-control input-sm" type="number" min="0.01" step="0.01" name="mutasi[<?= (int) $i ?>][jumlah]" value="<?= html_escape($line->jumlah) ?>"></td>
                                <td><button class="btn btn-xs btn-default remove-statement-line" type="button">Hapus</button></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <button id="add-statement-line" class="btn btn-default" type="button">Tambah mutasi</button>
                    <button class="btn btn-primary" type="submit">Simpan rekening koran</button>
                </form>
            <?php else: ?>
                <p><strong>Saldo awal:</strong> Rp <?= html_escape(format_nominal($reconciliation->saldo_awal)) ?>
                    &nbsp; <strong>Saldo akhir:</strong> Rp <?= html_escape(format_nominal($reconciliation->saldo_akhir)) ?></p>
            <?php endif; ?>

            <h4>Pencocokan Mutasi dengan Jurnal</h4>
            <?php if (!$reconciliation->mutasi): ?>
                <p class="text-muted">Belum ada mutasi pada rekening koran ini.</p>
            <?php else: ?>
                <div class="table-responsive"><table class="table table-bordered">
                    <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Arah</th><th>Jumlah</th><th>Jurnal</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($reconciliation->mutasi as $line): ?>
                        <tr class="<?= $line->id_detail_jurnal ? 'success' : 'warning' ?>">
                            <td><?= html_escape($line->tanggal) ?></td>
                            <td><?= html_escape($line->keterangan) ?></td>
                            <td><?= $line->arah === 'masuk' ? 'Masuk' : 'Keluar' ?></td>
                            <td><?= html_escape(format_nominal($line->jumlah)) ?></td>
                            <td>
                                <?php if ($line->id_detail_jurnal): ?>
                                    <?= html_escape($line->tanggal_jurnal . ' - ' . $line->referensi . ' - ' . $line->deskripsi_jurnal) ?>
                                <?php elseif ($editable): ?>
                                    <form method="post" action="<?= site_url('rekonsiliasi_bank/cocokkan') ?>" class="form-inline">
                                        <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                                        <?php if ($this->config->item('csrf_protection')): ?>
                                            <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="periode" value="<?= html_escape($period) ?>">
                                        <input type="hidden" name="id_akun" value="<?= (int) $account_id ?>">
                                        <input type="hidden" name="id_mutasi" value="<?= (int) $line->id_mutasi_rekening ?>">
                                        <select class="form-control input-sm" name="id_detail" required>
                                            <option value="">Pilih jurnal yang sama arah dan jumlahnya</option>
                                            <?php foreach ($reconciliation->jurnal_tersedia as $entry): ?>
                                                <option value="<?= (int) $entry->id_detail ?>"><?= html_escape($entry->tanggal . ' | ' . $entry->referensi . ' | ' . $entry->deskripsi . ' | Debit ' . format_nominal($entry->debit ?: '0') . ' | Kredit ' . format_nominal($entry->kredit ?: '0')) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-xs btn-success" type="submit">Cocokkan</button>
                                    </form>
                                <?php else: ?>Belum cocok<?php endif; ?>
                            </td>
                            <td>
                                <?php if ($line->id_detail_jurnal && $editable): ?>
                                    <form method="post" action="<?= site_url('rekonsiliasi_bank/lepas') ?>">
                                        <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                                        <?php if ($this->config->item('csrf_protection')): ?>
                                            <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="periode" value="<?= html_escape($period) ?>">
                                        <input type="hidden" name="id_akun" value="<?= (int) $account_id ?>">
                                        <input type="hidden" name="id_mutasi" value="<?= (int) $line->id_mutasi_rekening ?>">
                                        <button class="btn btn-xs btn-warning" type="submit">Lepas</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
            <?php if ($editable && empty($reconciliation->belum_tersimpan) && $reconciliation->status === 'draft'): ?>
                <form method="post" action="<?= site_url('rekonsiliasi_bank/selesaikan') ?>">
                    <input type="hidden" name="kontrol_periode_form_token" value="<?= html_escape($this->session->userdata('kontrol_periode_form_token')) ?>">
                    <?php if ($this->config->item('csrf_protection')): ?>
                        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                    <?php endif; ?>
                    <input type="hidden" name="periode" value="<?= html_escape($period) ?>">
                    <input type="hidden" name="id_akun" value="<?= (int) $account_id ?>">
                    <button class="btn btn-success" type="submit">Selesaikan rekonsiliasi</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php if (!empty($reconciliation) && $editable): ?>
<script>
(function () {
    var body = document.querySelector('#statement-lines tbody');
    var next = body ? body.querySelectorAll('tr').length : 0;
    function updateCount() {
        document.getElementById('statement-count').value = body.querySelectorAll('tr').length;
    }
    function bindRemove(button) {
        button.addEventListener('click', function () { button.closest('tr').remove(); updateCount(); });
    }
    if (body) {
        body.querySelectorAll('.remove-statement-line').forEach(bindRemove);
        document.getElementById('add-statement-line').addEventListener('click', function () {
            if (body.querySelectorAll('tr').length >= 200) {
                alert('Maksimal 200 mutasi per rekening dan periode.');
                return;
            }
            var row = document.createElement('tr');
            row.innerHTML = '<td><input class="form-control input-sm" type="date" name="mutasi[' + next + '][tanggal]"></td>' +
                '<td><input class="form-control input-sm" maxlength="255" name="mutasi[' + next + '][keterangan]"></td>' +
                '<td><select class="form-control input-sm" name="mutasi[' + next + '][arah]"><option value="masuk">Masuk</option><option value="keluar">Keluar</option></select></td>' +
                '<td><input class="form-control input-sm" type="number" min="0.01" step="0.01" name="mutasi[' + next + '][jumlah]"></td>' +
                '<td><button class="btn btn-xs btn-default remove-statement-line" type="button">Hapus</button></td>';
            body.appendChild(row);
            updateCount();
            bindRemove(row.querySelector('.remove-statement-line'));
            next++;
        });
    }
}());
</script>
<?php endif; ?>
