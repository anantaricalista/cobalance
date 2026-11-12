<?php
$editable = !$transaction || $transaction->status === 'draft';
$equityMap = array();
foreach ($equity_accounts as $account) {
    $equityMap[] = array(
        'id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable
    );
}
$cashMap = array();
foreach ($cash_accounts as $account) {
    $cashMap[] = array(
        'id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable
    );
}
?>
<h3>Modal dan Prive</h3>
<p class="text-muted" role="note">Nominal: pemisah ribuan ditambahkan otomatis; gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>.</p>
<p>Setoran modal mendebit kas/bank dan mengkredit akun Modal pada Pengaturan Akun. Prive mendebit akun ekuitas yang dipilih dan mengkredit kas/bank.</p>
<p><a href="<?= site_url('modal_prive') ?>">Transaksi baru</a></p>
<div id="capital-message" role="alert"></div>
<?php if ($transaction): ?>
    <p><a href="<?= site_url('modal_prive') ?>">&larr; Kembali ke daftar</a></p>
    <h4><?= html_escape($transaction->jenis_transaksi === 'setoran_modal' ? 'Setoran modal' : 'Prive') ?></h4>
    <p><strong>Nomor:</strong> <?= html_escape($transaction->nomor_transaksi) ?> |
        <strong>Tanggal:</strong> <?= html_escape($transaction->tanggal) ?> |
        <strong>Status:</strong> <?= html_escape($transaction->status) ?> |
        <strong>Jumlah:</strong> Rp <?= html_escape(format_nominal($transaction->total)) ?></p>
    <p>Kas/bank: <?= html_escape($transaction->kode_kas . ' - ' . $transaction->nama_kas) ?> |
        Akun lawan: <?= html_escape($transaction->kode_lawan . ' - ' . $transaction->nama_lawan) ?></p>
    <p><?= html_escape($transaction->deskripsi) ?></p>
    <?php if ($transaction->lampiran_nama): ?>
        <p><strong>Lampiran:</strong> <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
    <?php if ($editable): ?>
        <form id="capital-form" enctype="multipart/form-data">
            <input id="capital-id" type="hidden" value="<?= (int) $transaction->id_transaksi ?>">
            <div class="row">
                <div class="form-group col-sm-4"><label for="capital-type">Jenis</label>
                    <select id="capital-type" class="form-control" disabled>
                        <option value="setoran_modal" <?= $transaction->jenis_transaksi === 'setoran_modal' ? 'selected' : '' ?>>Setoran modal</option>
                        <option value="prive" <?= $transaction->jenis_transaksi === 'prive' ? 'selected' : '' ?>>Prive</option>
                    </select></div>
                <div class="form-group col-sm-4"><label for="capital-date">Tanggal</label>
                    <input id="capital-date" class="form-control" type="date" value="<?= html_escape($transaction->tanggal) ?>" required></div>
                <div class="form-group col-sm-4"><label for="capital-cash">Akun kas/bank</label>
                    <select id="capital-cash" class="form-control" required></select></div>
            </div>
            <div class="form-group" id="equity-container"><label for="capital-equity">Akun prive (ekuitas)</label>
                <select id="capital-equity" class="form-control"></select></div>
            <div class="form-group"><label for="capital-amount">Jumlah</label>
                <input id="capital-amount" class="form-control" type="text" inputmode="decimal" required></div>
            <div class="form-group"><label for="capital-description">Keterangan</label>
                <input id="capital-description" class="form-control" maxlength="255" value="<?= html_escape($transaction->deskripsi) ?>"></div>
            <div class="form-group"><label for="capital-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
                <input id="capital-file" type="file" accept="application/pdf,image/jpeg,image/png">
                <?php if ($transaction->lampiran_nama): ?>
                    <p>Lampiran saat ini: <?= html_escape($transaction->lampiran_nama) ?>
                        <label><input id="capital-remove-file" type="checkbox" value="1"> Hapus lampiran</label></p>
                <?php endif; ?></div>
            <button type="submit" class="btn btn-primary">Perbarui draft</button>
        </form>
    <?php elseif ($transaction->status === 'diposting'): ?>
        <?php if ($this->can_post_transactions): ?>
            <h4>Pembatalan</h4>
            <p>Pembatalan membuat jurnal pembalik.</p>
            <form id="capital-cancel" class="form-inline">
                <label for="cancel-date">Tanggal</label>
                <input id="cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                <label for="cancel-reason">Alasan</label>
                <input id="cancel-reason" class="form-control" maxlength="255" required>
                <button type="submit" class="btn btn-danger">Batalkan transaksi</button>
            </form>
        <?php else: ?>
            <p class="text-muted">Pembatalan transaksi hanya dapat dilakukan oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($transaction->status === 'draft'): ?>
        <?php if ($this->can_post_transactions): ?>
            <p>Pastikan draft telah diperbarui sebelum posting.</p>
            <button id="capital-post" type="button" class="btn btn-success">Posting transaksi tersimpan</button>
        <?php else: ?>
            <p class="text-info" role="status">Draft tersimpan dan menunggu posting oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <form id="capital-form" enctype="multipart/form-data">
        <div class="row">
            <div class="form-group col-sm-3"><label for="capital-type">Jenis</label>
                <select id="capital-type" class="form-control">
                    <option value="setoran_modal">Setoran modal</option>
                    <option value="prive">Prive</option>
                </select></div>
            <div class="form-group col-sm-3"><label for="capital-date">Tanggal</label>
                <input id="capital-date" class="form-control" type="date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group col-sm-6"><label for="capital-cash">Akun kas/bank</label>
                <select id="capital-cash" class="form-control" required></select></div>
        </div>
        <div class="form-group" id="equity-container"><label for="capital-equity">Akun prive (ekuitas)</label>
            <select id="capital-equity" class="form-control"></select></div>
        <div class="form-group"><label for="capital-amount">Jumlah</label>
            <input id="capital-amount" class="form-control" type="text" inputmode="decimal" required></div>
        <div class="form-group"><label for="capital-description">Keterangan</label>
            <input id="capital-description" class="form-control" maxlength="255"></div>
        <div class="form-group"><label for="capital-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
            <input id="capital-file" type="file" accept="application/pdf,image/jpeg,image/png"></div>
        <button type="submit" class="btn btn-primary">Simpan draft</button>
    </form>
<?php endif; ?>
<h4>Riwayat transaksi Modal dan Prive</h4>
<div class="table-responsive"><table class="table table-striped table-bordered">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis</th><th>Kas/bank</th><th>Akun lawan</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transactions as $item): ?>
        <tr><td><?= html_escape($item->tanggal) ?></td><td><?= html_escape($item->nomor_transaksi) ?></td>
            <td><?= html_escape($item->jenis_transaksi === 'setoran_modal' ? 'Setoran modal' : 'Prive') ?></td>
            <td><?= html_escape($item->kode_kas . ' - ' . $item->nama_kas) ?></td>
            <td><?= html_escape($item->kode_lawan . ' - ' . $item->nama_lawan) ?></td>
            <td>Rp <?= html_escape(format_nominal($item->total)) ?></td><td><?= html_escape($item->status) ?></td>
            <td><a href="<?= site_url('modal_prive?id=' . (int) $item->id_transaksi) ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="8">Belum ada transaksi modal atau prive.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<script>
$(function () {
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var accounts = <?= json_encode(array('cash' => $cashMap, 'equity' => $equityMap),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;

    function fill(select, options, selected, placeholder) {
        select.empty().append($('<option>').val('').text(placeholder));
        options.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', !!item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }
    function showError(message) {
        $('#capital-message').empty().append($('<div class="alert alert-danger">').text(message));
    }
    function updateType() {
        var prive = $('#capital-type').val() === 'prive';
        $('#equity-container').toggle(prive);
        $('#capital-equity').prop('required', prive);
    }
    if ($('#capital-form').length) {
        fill($('#capital-cash'), accounts.cash, transaction ? transaction.id_akun_kas : null, '-- Pilih kas/bank --');
        fill($('#capital-equity'), accounts.equity,
            transaction && transaction.jenis_transaksi === 'prive' ? transaction.id_akun : null, '-- Pilih akun prive --');
        if (transaction) {
            $('#capital-type').val(transaction.jenis_transaksi);
            $('#capital-amount').val(transaction.total);
        }
        MoneyInput.init($('#capital-amount')[0], transaction ? transaction.total : '');
        updateType();
        $('#capital-type').on('change', updateType);
    }
    function request(url, fields, file) {
        var data = new FormData();
        Object.keys(fields).forEach(function (key) { data.append(key, fields[key]); });
        if (file) data.append('lampiran', file);
        if (csrf) data.append(csrf.name, csrf.value);
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: data, processData: false, contentType: false})
            .done(function () {
                location.href = <?= json_encode(site_url('modal_prive')) ?>;
            }).fail(function (xhr) {
                showError(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.');
                $('button').prop('disabled', false);
            });
    }
    $('#capital-form').on('submit', function (event) {
        event.preventDefault();
        var file = $('#capital-file')[0].files[0];
        if (file && file.size > 5242880) {
            showError('Ukuran lampiran melebihi batas 5 MB.');
            return;
        }
        request(<?= json_encode(site_url('modal_prive/simpan')) ?>, {
            id: transaction ? transaction.id_transaksi : '',
            tanggal: $('#capital-date').val(),
            jenis: $('#capital-type').val(),
            id_akun_kas: $('#capital-cash').val(),
            id_akun_modal: $('#capital-equity').val(),
            jumlah: MoneyInput.value($('#capital-amount')[0]),
            deskripsi: $('#capital-description').val(),
            hapus_lampiran: $('#capital-remove-file').prop('checked') ? '1' : '0'
        }, file);
    });
    $('#capital-post').on('click', function () {
        if (!confirm('Posting transaksi dan buat jurnal otomatis?')) return;
        request(<?= json_encode(site_url('modal_prive/posting')) ?>, {id: transaction.id_transaksi});
    });
    $('#capital-cancel').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan transaksi dan buat jurnal pembalik?')) return;
        request(<?= json_encode(site_url('modal_prive/batalkan')) ?>, {
            id: transaction.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()
        });
    });
});
</script>
