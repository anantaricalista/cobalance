<?php
$account_options = function ($accounts) {
    $result = array();
    foreach ($accounts as $account) {
        $result[] = array(
            'id' => $account->id_akun,
            'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
            'disabled' => !$account->selectable
        );
    }
    return $result;
};
$asset_options = array();
foreach ($assets as $item) {
    if ($item->status === 'aktif' && $item->status_perolehan === 'diposting') {
        $asset_options[] = array('id' => $item->id_aset_tetap,
            'label' => $item->kode_aset . ' - ' . $item->nama_aset);
    }
}
$supplier_options = array();
foreach ($suppliers as $supplier) {
    $supplier_options[] = array('id' => $supplier->id_pemasok,
        'label' => $supplier->kode_pemasok . ' - ' . $supplier->nama_pemasok);
}
$customer_options = array();
foreach ($customers as $customer) {
    $customer_options[] = array('id' => $customer->id_pelanggan,
        'label' => $customer->kode_pelanggan . ' - ' . $customer->nama_pelanggan);
}
$isDraft = $transaction && $transaction->status === 'draft';
?>
<h3>Aset Tetap</h3>
<p>Kelola perolehan tunai/kredit, pendaftaran aset lama yang sudah dicatat melalui Saldo Awal, penyusutan garis lurus, dan pelepasan aset.</p>
<div id="asset-message" role="alert"></div>

<?php if ($transaction): ?>
    <p><a href="<?= site_url('aset_tetap') ?>">&larr; Kembali ke daftar aset</a></p>
    <h4><?= html_escape($transaction->nomor_transaksi) ?> — <?= html_escape($transaction->jenis_transaksi) ?></h4>
    <p><strong>Tanggal/periode:</strong> <?= html_escape($transaction->tanggal) ?>
        &nbsp; <strong>Status:</strong> <?= html_escape($transaction->status) ?>
        <?php if ($transaction->total !== null): ?>&nbsp; <strong>Total:</strong> Rp <?= html_escape($transaction->total) ?><?php endif; ?>
    </p>
    <?php if ($transaction->jenis_transaksi === 'penyusutan_aset' && $transaction->details): ?>
        <div class="table-responsive"><table class="table table-bordered table-condensed">
            <thead><tr><th>Kode</th><th>Aset</th><th>Periode</th><th>Penyusutan</th></tr></thead><tbody>
            <?php foreach ($transaction->details as $row): ?>
                <tr><td><?= html_escape($row['kode_aset']) ?></td><td><?= html_escape($row['nama_aset']) ?></td>
                    <td><?= html_escape($row['periode']) ?></td><td>Rp <?= html_escape($row['nilai_penyusutan']) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
    <?php elseif ($transaction->jenis_transaksi === 'pelepasan_aset' && $transaction->disposal): ?>
        <p><strong>Aset:</strong> <?= html_escape($transaction->disposal->kode_aset . ' - ' . $transaction->disposal->nama_aset) ?>
            &nbsp; <strong>Hasil:</strong> Rp <?= html_escape($transaction->disposal->nilai_pelepasan) ?></p>
    <?php endif; ?>
    <?php if ($transaction->lampiran_nama): ?>
        <p><strong>Lampiran:</strong> <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
<?php endif; ?>

<?php if (!$transaction || ($isDraft && $transaction->jenis_transaksi === 'perolehan_aset')): ?>
<details <?= $transaction && $transaction->jenis_transaksi === 'perolehan_aset' ? 'open' : '' ?>>
    <summary><strong><?= $transaction && $transaction->jenis_transaksi === 'perolehan_aset' ? 'Ubah draft perolehan aset' : 'Catat perolehan aset baru' ?></strong></summary>
    <form id="acquisition-form" enctype="multipart/form-data">
        <input id="acq-id" type="hidden" value="<?= $transaction && $transaction->jenis_transaksi === 'perolehan_aset' ? (int) $transaction->id_transaksi : '' ?>">
        <div class="row">
            <div class="form-group col-sm-3"><label for="acq-date">Tanggal perolehan</label>
                <input class="form-control" id="acq-date" type="date" required value="<?= html_escape($transaction && $transaction->jenis_transaksi === 'perolehan_aset' ? $transaction->tanggal : date('Y-m-d')) ?>"></div>
            <div class="form-group col-sm-3"><label for="acq-code">Kode aset</label>
                <input class="form-control" id="acq-code" maxlength="50" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->kode_aset : '') ?>"></div>
            <div class="form-group col-sm-6"><label for="acq-name">Nama aset</label>
                <input class="form-control" id="acq-name" maxlength="255" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->nama_aset : '') ?>"></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-3"><label for="acq-cost">Nilai perolehan</label>
                <input class="form-control" id="acq-cost" type="number" min="0.01" step="0.01" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->nilai_perolehan : '') ?>"></div>
            <div class="form-group col-sm-3"><label for="acq-residual">Nilai residu</label>
                <input class="form-control" id="acq-residual" type="number" min="0" step="0.01" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->nilai_residu : '0.00') ?>"></div>
            <div class="form-group col-sm-3"><label for="acq-life">Umur manfaat (bulan)</label>
                <input class="form-control" id="acq-life" type="number" min="1" max="120000" step="1" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->umur_manfaat_bulan : '') ?>"></div>
            <div class="form-group col-sm-3"><label for="acq-start">Mulai penyusutan</label>
                <input class="form-control" id="acq-start" type="date" required value="<?= html_escape($transaction && $transaction->asset ? $transaction->asset->tanggal_mulai_penyusutan : date('Y-m-01')) ?>"></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4"><label for="acq-asset-account">Akun aset (kelompok 1800)</label><select class="form-control" id="acq-asset-account" required></select></div>
            <div class="form-group col-sm-4"><label for="acq-accum-account">Akun akumulasi (kelompok 1900)</label><select class="form-control" id="acq-accum-account" required></select></div>
            <div class="form-group col-sm-4"><label for="acq-expense-account">Akun beban penyusutan</label><select class="form-control" id="acq-expense-account" required></select></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-3"><label for="acq-payment">Pembayaran</label>
                <select class="form-control" id="acq-payment"><option value="tunai">Tunai</option><option value="kredit">Kredit</option></select></div>
            <div class="form-group col-sm-5" id="acq-cash-wrap"><label for="acq-cash">Akun kas/bank</label><select class="form-control" id="acq-cash"></select></div>
            <div class="form-group col-sm-5" id="acq-supplier-wrap"><label for="acq-supplier">Pemasok</label><select class="form-control" id="acq-supplier"></select></div>
            <div class="form-group col-sm-4" id="acq-due-wrap"><label for="acq-due">Jatuh tempo</label><input class="form-control" id="acq-due" type="date"></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4"><label for="acq-reference">Referensi/faktur</label><input class="form-control" id="acq-reference" maxlength="100" value="<?= html_escape($transaction ? $transaction->referensi : '') ?>"></div>
            <div class="form-group col-sm-8"><label for="acq-description">Keterangan</label><input class="form-control" id="acq-description" maxlength="255" value="<?= html_escape($transaction ? $transaction->deskripsi : '') ?>"></div>
        </div>
        <div class="form-group"><label for="acq-file">Lampiran bukti (PDF/JPG/PNG, maks. 5 MB)</label><input id="acq-file" type="file" accept="application/pdf,image/jpeg,image/png">
            <?php if ($transaction && $transaction->lampiran_nama): ?>
                <p>Lampiran saat ini: <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a>
                    <label><input type="checkbox" id="acq-remove-file"> Hapus lampiran</label></p>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit">Simpan draft perolehan</button>
    </form>
</details>
<?php endif; ?>

<?php if (!$transaction): ?>
<details>
    <summary><strong>Daftarkan aset lama (sudah tercatat melalui Saldo Awal)</strong></summary>
    <p>Registrasi ini tidak membuat jurnal. Nilai aset dan akumulasi penyusutan harus sudah tercermin dalam Saldo Awal.</p>
    <form id="legacy-form">
        <div class="row">
            <div class="form-group col-sm-3"><label for="old-code">Kode aset</label><input class="form-control" id="old-code" maxlength="50" required></div>
            <div class="form-group col-sm-5"><label for="old-name">Nama aset</label><input class="form-control" id="old-name" maxlength="255" required></div>
            <div class="form-group col-sm-2"><label for="old-date">Tanggal perolehan</label><input class="form-control" id="old-date" type="date" required></div>
            <div class="form-group col-sm-2"><label for="old-start">Mulai penyusutan</label><input class="form-control" id="old-start" type="date" required></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-3"><label for="old-cost">Nilai perolehan</label><input class="form-control" id="old-cost" type="number" min="0.01" step="0.01" required></div>
            <div class="form-group col-sm-3"><label for="old-residual">Nilai residu</label><input class="form-control" id="old-residual" type="number" min="0" step="0.01" value="0.00" required></div>
            <div class="form-group col-sm-3"><label for="old-life">Umur manfaat (bulan)</label><input class="form-control" id="old-life" type="number" min="1" max="120000" required></div>
            <div class="form-group col-sm-3"><label for="old-accum">Akumulasi penyusutan per Saldo Awal</label><input class="form-control" id="old-accum" type="number" min="0" step="0.01" value="0.00" required></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4"><label for="old-asset-account">Akun aset (1800)</label><select class="form-control" id="old-asset-account" required></select></div>
            <div class="form-group col-sm-4"><label for="old-accum-account">Akun akumulasi (1900)</label><select class="form-control" id="old-accum-account" required></select></div>
            <div class="form-group col-sm-4"><label for="old-expense-account">Akun beban penyusutan</label><select class="form-control" id="old-expense-account" required></select></div>
        </div>
        <button class="btn btn-default" type="submit">Daftarkan aset lama</button>
    </form>
</details>
<?php endif; ?>
<?php if (!$transaction): ?>
<details>
    <summary><strong>Buat draft penyusutan bulanan</strong></summary>
    <p>Periode harus diproses berurutan. Setiap transaksi penyusutan mencatat satu bulan penuh untuk semua aset aktif.</p>
    <form id="depreciation-form" class="form-inline">
        <label for="depreciation-period">Periode</label>
        <input class="form-control" id="depreciation-period" type="month" required value="<?= date('Y-m') ?>">
        <button class="btn btn-default" type="submit">Buat draft penyusutan</button>
    </form>
</details>
<?php endif; ?>
<?php if (!$transaction || ($isDraft && $transaction->jenis_transaksi === 'pelepasan_aset')): ?>
<details>
    <summary><strong><?= $isDraft && $transaction->jenis_transaksi === 'pelepasan_aset' ? 'Ubah draft pelepasan aset' : 'Buat draft pelepasan aset' ?></strong></summary>
    <form id="disposal-form" enctype="multipart/form-data">
        <input id="disposal-id" type="hidden" value="<?= $isDraft && $transaction->jenis_transaksi === 'pelepasan_aset' ? (int) $transaction->id_transaksi : '' ?>">
        <div class="row">
            <div class="form-group col-sm-3"><label for="disposal-date">Tanggal pelepasan</label><input class="form-control" id="disposal-date" type="date" required value="<?= html_escape($isDraft && $transaction->jenis_transaksi === 'pelepasan_aset' ? $transaction->tanggal : date('Y-m-d')) ?>"></div>
            <div class="form-group col-sm-5"><label for="disposal-asset">Aset</label><select class="form-control" id="disposal-asset" required></select></div>
            <div class="form-group col-sm-4"><label for="disposal-value">Hasil pelepasan</label><input class="form-control" id="disposal-value" type="number" min="0" step="0.01" value="<?= html_escape($isDraft && $transaction->jenis_transaksi === 'pelepasan_aset' && $transaction->disposal ? $transaction->disposal->nilai_pelepasan : '0.00') ?>" required></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-3"><label for="disposal-payment">Cara penerimaan</label><select class="form-control" id="disposal-payment"><option value="tanpa_hasil">Tanpa hasil</option><option value="tunai">Tunai</option><option value="kredit">Kredit</option></select></div>
            <div class="form-group col-sm-4" id="disposal-cash-wrap"><label for="disposal-cash">Akun kas/bank</label><select class="form-control" id="disposal-cash"></select></div>
            <div class="form-group col-sm-5" id="disposal-customer-wrap"><label for="disposal-customer">Pelanggan</label><select class="form-control" id="disposal-customer"></select></div>
            <div class="form-group col-sm-4" id="disposal-due-wrap"><label for="disposal-due">Jatuh tempo</label><input class="form-control" id="disposal-due" type="date"></div>
        </div>
        <div class="form-group"><label for="disposal-file">Lampiran bukti (PDF/JPG/PNG, maks. 5 MB)</label><input id="disposal-file" type="file" accept="application/pdf,image/jpeg,image/png">
            <?php if ($isDraft && $transaction->jenis_transaksi === 'pelepasan_aset' && $transaction->lampiran_nama): ?>
                <p>Lampiran saat ini: <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a>
                    <label><input type="checkbox" id="disposal-remove-file"> Hapus lampiran</label></p>
            <?php endif; ?>
        </div>
        <button class="btn btn-default" type="submit">Simpan draft pelepasan</button>
    </form>
</details>
<?php endif; ?>

<?php if ($transaction && $transaction->status === 'draft'): ?>
    <button type="button" class="btn btn-success" id="post-asset-transaction" data-id="<?= (int) $transaction->id_transaksi ?>">Posting transaksi</button>
<?php elseif ($transaction && $transaction->status === 'diposting'): ?>
    <form id="cancel-asset-transaction" class="form-inline">
        <label for="asset-cancel-date">Tanggal pembatalan</label><input class="form-control" id="asset-cancel-date" type="date" required value="<?= date('Y-m-d') ?>">
        <label for="asset-cancel-reason">Alasan</label><input class="form-control" id="asset-cancel-reason" maxlength="255" required>
        <button class="btn btn-danger" type="submit">Batalkan transaksi</button>
    </form>
<?php endif; ?>

<?php if ($asset): ?>
    <h4><?= html_escape($asset->kode_aset . ' - ' . $asset->nama_aset) ?></h4>
    <p>Perolehan Rp <?= html_escape($asset->nilai_perolehan) ?>; residu Rp <?= html_escape($asset->nilai_residu) ?>;
        akumulasi Rp <?= html_escape($asset->akumulasi_tercatat) ?>; nilai buku Rp <?= html_escape($asset->nilai_buku) ?>;
        status <?= html_escape($asset->status) ?>.</p>
    <p>Jadwal mulai <?= html_escape($asset->tanggal_mulai_penyusutan) ?>, umur <?= (int) $asset->umur_manfaat_bulan ?> bulan.</p>
<?php endif; ?>

<?php if (!$transaction): ?>
<h4>Daftar aset</h4>
<div class="table-responsive"><table class="table table-striped table-bordered">
    <thead><tr><th>Kode</th><th>Nama</th><th>Tanggal perolehan</th><th>Biaya</th><th>Akumulasi</th><th>Nilai buku</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($assets as $item): ?>
        <tr><td><?= html_escape($item->kode_aset) ?></td><td><?= html_escape($item->nama_aset) ?></td>
            <td><?= html_escape($item->tanggal_perolehan) ?></td><td>Rp <?= html_escape($item->nilai_perolehan) ?></td>
            <td>Rp <?= html_escape($item->akumulasi_tercatat) ?></td><td>Rp <?= html_escape($item->nilai_buku) ?></td>
            <td><?= html_escape($item->status === 'dilepas' ? 'Dilepas' : $item->status_perolehan) ?></td>
            <td><a class="btn btn-default btn-xs" href="<?= site_url('aset_tetap?id=' . (int) $item->id_aset_tetap) ?>">Lihat</a>
                <?php if ($item->lampiran_nama): ?><a class="btn btn-default btn-xs" href="<?= site_url('lampiran/unduh/' . (int) $item->id_transaksi_perolehan) ?>">Bukti perolehan</a><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$assets): ?><tr><td colspan="8" class="text-center">Belum ada aset tetap.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<h4>Riwayat transaksi aset</h4>
<div class="table-responsive"><table class="table table-striped table-bordered">
    <thead><tr><th>Tanggal/periode</th><th>Nomor</th><th>Jenis</th><th>Rincian</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transactions as $item): ?>
        <tr><td><?= html_escape($item->tanggal) ?></td><td><?= html_escape($item->nomor_transaksi) ?></td>
            <td><?= html_escape($item->jenis_transaksi) ?></td><td><?= html_escape($item->nama_aset ?: $item->deskripsi) ?></td>
            <td><?= html_escape($item->status) ?></td><td><a class="btn btn-default btn-xs" href="<?= site_url('aset_tetap?trx=' . (int) $item->id_transaksi) ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="6" class="text-center">Belum ada transaksi aset.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<?php endif; ?>

<script>
$(function () {
    $('#acq-cost, #acq-residual, #old-cost, #old-residual, #old-accum, #disposal-value').each(function () {
        MoneyInput.init(this, this.value);
    });
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var choices = <?= json_encode(array(
        'assetAccounts' => $account_options($accounts_asset),
        'accumAccounts' => $account_options($accounts_accumulated),
        'expenseAccounts' => $account_options($accounts_expense),
        'cashAccounts' => $account_options($cash_accounts),
        'allAssets' => $asset_options,
        'suppliers' => $supplier_options,
        'customers' => $customer_options
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;

    function selectOptions(selector, list, selected, placeholder) {
        var select = $(selector).empty().append($('<option>').val('').text(placeholder));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', !!item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }
    function error(message) {
        $('#asset-message').empty().append($('<div class="alert alert-danger">').text(message));
    }
    function request(url, fields, file) {
        var form = new FormData();
        Object.keys(fields).forEach(function (key) { form.append(key, fields[key]); });
        if (file) {
            if (file.size > 5242880) {
                error('Ukuran lampiran melebihi batas 5 MB.');
                return;
            }
            form.append('lampiran', file);
        }
        if (csrf) form.append(csrf.name, csrf.value);
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: form, processData: false, contentType: false})
            .done(function () { location.href = <?= json_encode(site_url('aset_tetap')) ?>; })
            .fail(function (xhr) {
                error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal.');
                $('button').prop('disabled', false);
            });
    }
    function selectData(selector, list, selected, placeholder) {
        selectOptions(selector, list, selected, placeholder);
    }
    function initAccounts(prefix, asset, accum, expense, cash) {
        selectData('#' + prefix + '-asset-account', choices.assetAccounts, asset, '-- Pilih akun aset --');
        selectData('#' + prefix + '-accum-account', choices.accumAccounts, accum, '-- Pilih akun akumulasi --');
        selectData('#' + prefix + '-expense-account', choices.expenseAccounts, expense, '-- Pilih akun beban --');
        if (cash) selectData('#' + prefix + '-cash', choices.cashAccounts, cash, '-- Pilih akun kas/bank --');
    }
    function acqPaymentChanged() {
        var credit = $('#acq-payment').val() === 'kredit';
        $('#acq-cash-wrap').toggle(!credit);
        $('#acq-supplier-wrap, #acq-due-wrap').toggle(credit);
        $('#acq-cash').prop('required', !credit);
        $('#acq-supplier, #acq-due').prop('required', credit);
    }
    if ($('#acquisition-form').length) {
        var asset = transaction && transaction.asset ? transaction.asset : null;
        initAccounts('acq', asset ? asset.id_akun_aset : null, asset ? asset.id_akun_akumulasi_penyusutan : null,
            asset ? asset.id_akun_beban_penyusutan : null, transaction ? transaction.id_akun_kas : null);
        selectData('#acq-supplier', choices.suppliers, transaction ? transaction.id_pemasok : null, '-- Pilih pemasok --');
        if (transaction) {
            $('#acq-payment').val(transaction.cara_pembayaran || 'tunai');
            $('#acq-due').val(transaction.jatuh_tempo || '');
            $('#acq-reference').val(transaction.referensi || '');
            $('#acq-description').val(transaction.deskripsi || '');
        }
        $('#acq-payment').on('change', acqPaymentChanged);
        acqPaymentChanged();
    }
    if ($('#legacy-form').length) {
        initAccounts('old', null, null, null, null);
    }
    if ($('#disposal-form').length) {
        var disposal = transaction && transaction.disposal ? transaction.disposal : null;
        selectData('#disposal-asset', choices.allAssets, disposal ? disposal.id_aset_tetap : null, '-- Pilih aset --');
        selectData('#disposal-cash', choices.cashAccounts, null, '-- Pilih akun kas/bank --');
        selectData('#disposal-customer', choices.customers, null, '-- Pilih pelanggan --');
        if (transaction && disposal) {
            $('#disposal-payment').val(transaction.cara_pembayaran || 'tanpa_hasil');
            $('#disposal-cash').val(transaction.id_akun_kas || '');
            $('#disposal-customer').val(transaction.id_pelanggan || '');
            $('#disposal-due').val(transaction.jatuh_tempo || '');
        }
        function disposalPaymentChanged() {
            var value = MoneyInput.value($('#disposal-value')[0]);
            var mode = $('#disposal-payment').val();
            var noProceeds = !value || Number(value) === 0;
            $('#disposal-cash-wrap').toggle(!noProceeds && mode === 'tunai');
            $('#disposal-customer-wrap, #disposal-due-wrap').toggle(!noProceeds && mode === 'kredit');
            $('#disposal-cash').prop('required', !noProceeds && mode === 'tunai');
            $('#disposal-customer, #disposal-due').prop('required', !noProceeds && mode === 'kredit');
        }
        $('#disposal-value, #disposal-payment').on('change input', disposalPaymentChanged);
        disposalPaymentChanged();
    }

    $('#acquisition-form').on('submit', function (event) {
        event.preventDefault();
        var data = {
            kode_aset: $('#acq-code').val(), nama_aset: $('#acq-name').val(),
            tanggal_perolehan: $('#acq-date').val(), tanggal_mulai_penyusutan: $('#acq-start').val(),
            nilai_perolehan: MoneyInput.value($('#acq-cost')[0]), nilai_residu: MoneyInput.value($('#acq-residual')[0]),
            umur_manfaat_bulan: $('#acq-life').val(), id_akun_aset: $('#acq-asset-account').val(),
            id_akun_akumulasi_penyusutan: $('#acq-accum-account').val(),
            id_akun_beban_penyusutan: $('#acq-expense-account').val(),
            cara_pembayaran: $('#acq-payment').val(), id_akun_kas: $('#acq-cash').val(),
            id_pemasok: $('#acq-supplier').val(), jatuh_tempo: $('#acq-due').val(),
            referensi: $('#acq-reference').val(), deskripsi: $('#acq-description').val()
        };
        request(<?= json_encode(site_url('aset_tetap/simpan')) ?>, {
            id: $('#acq-id').val(), tanggal: $('#acq-date').val(), data: JSON.stringify(data),
            hapus_lampiran: $('#acq-remove-file').prop('checked') ? '1' : '0'
        }, $('#acq-file')[0].files[0] || null);
    });
    $('#legacy-form').on('submit', function (event) {
        event.preventDefault();
        var data = {
            kode_aset: $('#old-code').val(), nama_aset: $('#old-name').val(),
            tanggal_perolehan: $('#old-date').val(), tanggal_mulai_penyusutan: $('#old-start').val(),
            nilai_perolehan: MoneyInput.value($('#old-cost')[0]), nilai_residu: MoneyInput.value($('#old-residual')[0]),
            umur_manfaat_bulan: $('#old-life').val(), akumulasi_penyusutan_awal: MoneyInput.value($('#old-accum')[0]),
            id_akun_aset: $('#old-asset-account').val(), id_akun_akumulasi_penyusutan: $('#old-accum-account').val(),
            id_akun_beban_penyusutan: $('#old-expense-account').val()
        };
        request(<?= json_encode(site_url('aset_tetap/daftarkan_lama')) ?>, {data: JSON.stringify(data)});
    });
    $('#depreciation-form').on('submit', function (event) {
        event.preventDefault();
        request(<?= json_encode(site_url('aset_tetap/susut')) ?>, {periode: $('#depreciation-period').val() + '-01'});
    });
    $('#disposal-form').on('submit', function (event) {
        event.preventDefault();
        var data = {
            id_aset_tetap: $('#disposal-asset').val(), nilai_pelepasan: MoneyInput.value($('#disposal-value')[0]),
            cara_pembayaran: $('#disposal-payment').val(), id_akun_kas: $('#disposal-cash').val(),
            id_pelanggan: $('#disposal-customer').val(), jatuh_tempo: $('#disposal-due').val()
        };
        request(<?= json_encode(site_url('aset_tetap/lepas')) ?>, {
            id: $('#disposal-id').val(), tanggal: $('#disposal-date').val(), data: JSON.stringify(data),
            hapus_lampiran: $('#disposal-remove-file').prop('checked') ? '1' : '0'
        }, $('#disposal-file')[0].files[0] || null);
    });
    $('#post-asset-transaction').on('click', function () {
        if (confirm('Posting transaksi aset tetap ini?')) {
            request(<?= json_encode(site_url('aset_tetap/posting')) ?>, {id: $(this).data('id')});
        }
    });
    $('#cancel-asset-transaction').on('submit', function (event) {
        event.preventDefault();
        if (confirm('Batalkan transaksi ini dan buat jurnal pembalik?')) {
            request(<?= json_encode(site_url('aset_tetap/batalkan')) ?>, {
                id: transaction.id_transaksi, tanggal: $('#asset-cancel-date').val(),
                alasan: $('#asset-cancel-reason').val()
            });
        }
    });
});
</script>
