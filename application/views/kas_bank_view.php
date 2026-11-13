<?php
$editable = !$transaction || $transaction->status === 'draft';
$map = function ($accounts) {
    $out = array();
    foreach ($accounts as $account) {
        $out[] = array(
            'id' => $account->id_akun,
            'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
            'disabled' => !$account->selectable
        );
    }
    return $out;
};
?>
<h3>Kas dan Bank</h3>
<p class="text-muted" role="note">Nominal: pemisah ribuan ditambahkan otomatis; gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>.</p>
<p>Catat kas masuk, kas keluar, dan transfer antar akun kas/bank. Posting membuat jurnal otomatis. Piutang dan utang usaha tidak dipilih di sini; gunakan modul Penerimaan Piutang dan Pembayaran Utang.</p>

<?php if ($transaction): ?>
    <p><a href="<?= site_url('kas_bank') ?>">&larr; Kembali ke daftar kas dan bank</a></p>
    <h4><?= $editable ? 'Draft' : 'Rincian' ?> <?= html_escape($types[$transaction->jenis_transaksi]) ?></h4>
    <p>
        <strong>Nomor:</strong> <?= html_escape($transaction->nomor_transaksi) ?>
        &nbsp; <strong>Status:</strong> <?= html_escape($transaction->status) ?>
        <?php if (!$editable): ?>
            &nbsp; <strong>Total:</strong> Rp <?= html_escape(format_nominal($transaction->total)) ?>
        <?php endif; ?>
    </p>
    <?php if (!$editable): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Akun</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($transaction->details as $detail): ?>
                    <tr>
                        <td><?= html_escape($detail['kode_akun'] . ' - ' . $detail['nama_akun']) ?></td>
                        <td><?= html_escape($detail['deskripsi']) ?></td>
                        <td>Rp <?= html_escape(format_nominal($detail['subtotal'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($transaction->lampiran_nama): ?>
            <p><strong>Lampiran:</strong>
                <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<div id="cash-message" role="alert"></div>
<?php if ($editable): ?>
    <form id="cash-form" enctype="multipart/form-data">
        <fieldset>
            <input id="cash-id" type="hidden" value="<?= $transaction ? (int) $transaction->id_transaksi : '' ?>">
            <div class="row">
                <div class="form-group col-sm-3">
                    <label for="cash-type">Jenis</label>
                    <select class="form-control" id="cash-type" <?= $transaction ? 'disabled' : '' ?> required>
                        <?php foreach ($types as $key => $label): ?>
                            <option value="<?= html_escape($key) ?>"><?= html_escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-sm-3">
                    <label for="cash-date">Tanggal</label>
                    <input class="form-control" id="cash-date" type="date" required
                        value="<?= html_escape($transaction ? $transaction->tanggal : date('Y-m-d')) ?>">
                </div>
                <div class="form-group col-sm-6">
                    <label for="cash-account" id="cash-account-label">Akun kas/bank</label>
                    <select class="form-control account-search" id="cash-account" required></select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-4">
                    <label for="cash-reference">Referensi</label>
                    <input class="form-control" id="cash-reference" maxlength="100">
                </div>
                <div class="form-group col-sm-8">
                    <label for="cash-description">Keterangan</label>
                    <input class="form-control" id="cash-description" maxlength="255">
                </div>
            </div>
            <h4 id="lines-title">Rincian</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed" id="cash-lines">
                    <thead><tr><th id="line-account-head">Akun</th><th>Keterangan</th><th>Jumlah</th><th>Aksi</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <p><button type="button" class="btn btn-default" id="add-cash-line">Tambah rincian</button></p>
            <div class="form-group">
                <label for="cash-file">Lampiran bukti (PDF/JPG/PNG, maks. 5 MB)</label>
                <input id="cash-file" type="file" accept="application/pdf,image/jpeg,image/png">
                <?php if ($transaction && $transaction->lampiran_nama): ?>
                    <p>Lampiran saat ini:
                        <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a>
                        <label><input type="checkbox" id="cash-remove-file"> Hapus lampiran</label>
                    </p>
                    <p class="text-muted">Memilih file baru akan menggantikan lampiran saat ini.</p>
                <?php endif; ?>
            </div>
            <p><button type="submit" class="btn btn-primary">Simpan draft</button></p>
        </fieldset>
    </form>
<?php endif; ?>

<?php if ($transaction && $transaction->status === 'draft'): ?>
    <?php if ($this->can_post_transactions): ?>
        <button type="button" id="post-cash" class="btn btn-success">Posting transaksi tersimpan</button>
        <p>Pastikan draft sudah disimpan sebelum melakukan posting.</p>
    <?php else: ?>
        <p class="text-info" role="status">Draft tersimpan dan menunggu posting oleh Akuntansi atau Admin Perusahaan.</p>
    <?php endif; ?>
<?php elseif ($transaction && $transaction->status === 'diposting'): ?>
    <?php if ($this->can_post_transactions): ?>
        <h4>Pembatalan</h4>
        <p>Pembatalan membuat jurnal pembalik.</p>
        <form id="cancel-cash" class="form-inline">
            <label for="cancel-date">Tanggal</label>
            <input id="cancel-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
            <label for="cancel-reason">Alasan</label>
            <input id="cancel-reason" class="form-control" required maxlength="255">
            <button type="submit" class="btn btn-danger">Batalkan transaksi</button>
        </form>
    <?php else: ?>
        <p class="text-muted">Pembatalan transaksi hanya dapat dilakukan oleh Akuntansi atau Admin Perusahaan.</p>
    <?php endif; ?>
<?php endif; ?>

<h4>Riwayat kas dan bank</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis</th><th>Akun kas/bank</th><th>Total</th><th>Lampiran</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $item): ?>
            <tr>
                <td><?= html_escape($item->tanggal) ?></td>
                <td><?= html_escape($item->nomor_transaksi) ?></td>
                <td><?= html_escape($types[$item->jenis_transaksi]) ?></td>
                <td><?= html_escape($item->kode_akun . ' - ' . $item->nama_akun) ?></td>
                <td>Rp <?= html_escape(format_nominal($item->total)) ?></td>
                <td><?php if ($item->lampiran_nama): ?><a href="<?= site_url('lampiran/unduh/' . (int) $item->id_transaksi) ?>">Unduh</a><?php else: ?>-<?php endif; ?></td>
                <td><?= html_escape($item->status) ?></td>
                <td><a class="btn btn-default btn-xs" href="<?= site_url('kas_bank?id=' . (int) $item->id_transaksi) ?>">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
            <tr><td colspan="8" class="text-center">Belum ada transaksi kas dan bank.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(function () {
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var options = <?= json_encode(array('cash' => $map($cash_accounts), 'all' => $map($all_accounts)),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;

    function fillSelect(select, list, selected, placeholder) {
        select.append($('<option>').val('').text(placeholder));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', !!item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }

    function showError(message) {
        $('#cash-message').empty().append($('<div class="alert alert-danger">').text(message));
    }

    function addLine(values) {
        values = values || {};
        var transfer = $('#cash-type').val() === 'transfer_kas';
        var row = $('<tr>');
        var account = $('<select class="form-control account-search" data-field="id_akun" aria-label="Akun rincian" required>');
        fillSelect(account, transfer ? options.cash : options.all, values.id_akun, '-- Pilih akun --');
        row.append($('<td>').append(account));
        row.append($('<td>').append($('<input class="form-control" maxlength="255" data-field="deskripsi">').val(values.deskripsi || '')));
        var amount = $('<input class="form-control" type="number" min="0.01" step="0.01" required data-field="jumlah">');
        MoneyInput.init(amount[0], values.subtotal || values.jumlah || '');
        row.append($('<td>').append(amount));
        row.append($('<td>').append($('<button type="button" class="btn btn-danger btn-xs">').text('Hapus')
            .on('click', function () { row.remove(); })));
        $('#cash-lines tbody').append(row);
    }

    function typeChanged(keepLines) {
        var type = $('#cash-type').val();
        var transfer = type === 'transfer_kas';
        $('#cash-account-label').text(type === 'kas_masuk' ? 'Diterima di akun kas/bank'
            : (type === 'kas_keluar' ? 'Dibayar dari akun kas/bank' : 'Akun kas/bank asal'));
        $('#line-account-head').text(type === 'kas_masuk' ? 'Akun sumber (kredit)'
            : (type === 'kas_keluar' ? 'Akun tujuan/beban (debit)' : 'Akun kas/bank tujuan'));
        $('#add-cash-line').toggle(!transfer);
        if (!keepLines) {
            $('#cash-lines tbody').empty();
            addLine();
        }
    }

    if ($('#cash-form').length) {
        fillSelect($('#cash-account'), options.cash, transaction ? transaction.id_akun_kas : null, '-- Pilih akun kas/bank --');
        if (transaction) {
            $('#cash-type').val(transaction.jenis_transaksi);
            $('#cash-reference').val(transaction.referensi || '');
            $('#cash-description').val(transaction.deskripsi || '');
            typeChanged(true);
            (transaction.details || []).forEach(addLine);
        } else {
            typeChanged(false);
        }
        $('#cash-type').on('change', function () { typeChanged(false); });
        $('#add-cash-line').on('click', function () { addLine(); });
    }

    function request(url, fields, file) {
        var formData = new FormData();
        Object.keys(fields).forEach(function (key) { formData.append(key, fields[key]); });
        if (file) {
            formData.append('lampiran', file);
        }
        if (csrf) {
            formData.append(csrf.name, csrf.value);
        }
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: formData, processData: false, contentType: false}).done(function () {
            location.href = <?= json_encode(site_url('kas_bank')) ?>;
        }).fail(function (xhr) {
            showError(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.');
            $('button').prop('disabled', false);
        });
    }

    $('#cash-form').on('submit', function (event) {
        event.preventDefault();
        var details = [];
        $('#cash-lines tbody tr').each(function () {
            var row = {};
            $(this).find('[data-field]').each(function () { row[$(this).data('field')] = $(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val(); });
            details.push(row);
        });
        if (!details.length) {
            showError('Tambahkan minimal satu rincian.');
            return;
        }
        var file = $('#cash-file')[0].files[0];
        if (file && file.size > 5242880) {
            showError('Ukuran lampiran melebihi batas 5 MB.');
            return;
        }
        request(<?= json_encode(site_url('kas_bank/simpan')) ?>, {
            id: $('#cash-id').val(), tanggal: $('#cash-date').val(),
            data: JSON.stringify({
                jenis: $('#cash-type').val(), id_akun_kas: $('#cash-account').val(),
                referensi: $('#cash-reference').val(), deskripsi: $('#cash-description').val(), details: details
            }),
            hapus_lampiran: $('#cash-remove-file').prop('checked') ? '1' : '0'
        }, file);
    });

    $('#post-cash').on('click', function () {
        if (confirm('Posting transaksi ini dan buat jurnal otomatis?')) {
            request(<?= json_encode(site_url('kas_bank/posting')) ?>, {id: transaction.id_transaksi});
        }
    });

    $('#cancel-cash').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan transaksi dan buat jurnal pembalik?')) {
            return;
        }
        request(<?= json_encode(site_url('kas_bank/batalkan')) ?>, {
            id: transaction.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()
        });
    });
});
</script>
