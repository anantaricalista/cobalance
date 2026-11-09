<?php
$editable = !$transaction || $transaction->status === 'draft';
$accountMap = array();
foreach ($accounts as $account) {
    $accountMap[] = array(
        'id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable
    );
}
?>
<h3>Penyesuaian Akuntansi</h3>
<p>Catat jurnal manual untuk koreksi atau penyesuaian. Setiap transaksi wajib seimbang dan hanya dapat menggunakan akun rinci.</p>
<p><a href="<?= site_url('penyesuaian_akuntansi') ?>">Transaksi baru</a></p>
<div id="adjustment-message" role="alert"></div>
<?php if ($transaction): ?>
    <p><a href="<?= site_url('penyesuaian_akuntansi') ?>">&larr; Kembali ke daftar</a></p>
    <h4><?= html_escape($transaction->deskripsi) ?></h4>
    <p><strong>Nomor:</strong> <?= html_escape($transaction->nomor_transaksi) ?> |
        <strong>Tanggal:</strong> <?= html_escape($transaction->tanggal) ?> |
        <strong>Status:</strong> <?= html_escape($transaction->status) ?></p>
    <?php if ($transaction->lampiran_nama): ?>
        <p><strong>Lampiran:</strong> <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
    <?php if ($editable): ?>
        <form id="adjustment-form" enctype="multipart/form-data">
            <input id="adjustment-id" type="hidden" value="<?= (int) $transaction->id_transaksi ?>">
            <div class="form-group"><label for="adjustment-date">Tanggal</label>
                <input id="adjustment-date" class="form-control" type="date" value="<?= html_escape($transaction->tanggal) ?>" required></div>
            <div class="form-group"><label for="adjustment-description">Keterangan</label>
                <input id="adjustment-description" class="form-control" maxlength="255" value="<?= html_escape($transaction->deskripsi) ?>"></div>
            <?php if ($transaction->lampiran_nama): ?>
                <div class="form-group"><label><input id="adjustment-remove-file" type="checkbox" value="1"> Hapus lampiran yang ada</label></div>
            <?php endif; ?>
            <div class="form-group"><label for="adjustment-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
                <input id="adjustment-file" type="file" accept="application/pdf,image/jpeg,image/png"></div>
            <div id="adjustment-lines" class="table-responsive"><table class="table table-bordered">
                <thead><tr><th>Akun</th><th>Debet</th><th>Kredit</th><th>Keterangan baris</th><th></th></tr></thead>
                <tbody></tbody>
                <tfoot><tr><th colspan="2" class="text-right">Total debet: <span id="adjustment-debit-total">Rp 0</span></th>
                    <th colspan="3">Total kredit: <span id="adjustment-credit-total">Rp 0</span></th></tr></tfoot>
            </table></div>
            <button id="adjustment-add-line" type="button" class="btn btn-default">Tambah baris</button>
            <button type="submit" class="btn btn-primary">Simpan draft</button>
        </form>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>Akun</th><th>Debet</th><th>Kredit</th><th>Keterangan</th></tr></thead><tbody>
            <?php foreach ($transaction->details as $line): ?>
                <tr><td><?= html_escape($line->kode_akun . ' - ' . $line->nama_akun) ?></td>
                    <td>Rp <?= html_escape($line->debit) ?></td><td>Rp <?= html_escape($line->kredit) ?></td>
                    <td><?= html_escape($line->deskripsi) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
    <?php endif; ?>
    <?php if ($transaction->status === 'draft'): ?>
        <p>Pastikan draft telah disimpan sebelum posting.</p>
        <button id="adjustment-post" type="button" class="btn btn-success">Posting transaksi tersimpan</button>
    <?php elseif ($transaction->status === 'diposting'): ?>
        <h4>Pembatalan</h4>
        <p>Pembatalan membuat jurnal pembalik.</p>
        <form id="adjustment-cancel" class="form-inline">
            <label for="cancel-date">Tanggal</label>
            <input id="cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            <label for="cancel-reason">Alasan</label>
            <input id="cancel-reason" class="form-control" maxlength="255" required>
            <button type="submit" class="btn btn-danger">Batalkan transaksi</button>
        </form>
    <?php endif; ?>
<?php else: ?>
    <form id="adjustment-form" enctype="multipart/form-data">
        <div class="form-group"><label for="adjustment-date">Tanggal</label>
            <input id="adjustment-date" class="form-control" type="date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="form-group"><label for="adjustment-description">Keterangan</label>
            <input id="adjustment-description" class="form-control" maxlength="255" required></div>
        <div class="form-group"><label for="adjustment-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
            <input id="adjustment-file" type="file" accept="application/pdf,image/jpeg,image/png"></div>
        <div id="adjustment-lines" class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>Akun</th><th>Debet</th><th>Kredit</th><th>Keterangan baris</th><th></th></tr></thead>
            <tbody></tbody>
            <tfoot><tr><th colspan="2" class="text-right">Total debet: <span id="adjustment-debit-total">Rp 0</span></th>
                <th colspan="3">Total kredit: <span id="adjustment-credit-total">Rp 0</span></th></tr></tfoot>
        </table></div>
        <button id="adjustment-add-line" type="button" class="btn btn-default">Tambah baris</button>
        <button type="submit" class="btn btn-primary">Simpan draft</button>
    </form>
<?php endif; ?>
<h4>Riwayat penyesuaian akuntansi</h4>
<div class="table-responsive"><table class="table table-striped table-bordered">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Keterangan</th><th>Total debet</th><th>Total kredit</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transactions as $item): ?>
        <tr><td><?= html_escape($item->tanggal) ?></td><td><?= html_escape($item->nomor_transaksi) ?></td>
            <td><?= html_escape($item->deskripsi) ?></td><td>Rp <?= html_escape($item->total_debit) ?></td>
            <td>Rp <?= html_escape($item->total_kredit) ?></td><td><?= html_escape($item->status) ?></td>
            <td><a href="<?= site_url('penyesuaian_akuntansi?id=' . (int) $item->id_transaksi) ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="7">Belum ada penyesuaian akuntansi.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<script>
$(function () {
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var accounts = <?= json_encode($accountMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;

    function showError(message) {
        $('#adjustment-message').empty().append($('<div class="alert alert-danger">').text(message));
    }
    function optionList(selected) {
        var select = $('<select class="form-control adjustment-account" required>').append($('<option>').val('').text('-- Pilih akun --'));
        accounts.forEach(function (account) {
            select.append($('<option>').val(account.id).text(account.label).prop('disabled', !!account.disabled));
        });
        select.val(selected == null ? '' : String(selected));
        return select;
    }
    function addLine(line) {
        var row = $('<tr>');
        var account = $('<td>').append(optionList(line ? line.id_akun : null));
        var debit = $('<input class="form-control adjustment-debit" type="text" inputmode="decimal">');
        var credit = $('<input class="form-control adjustment-credit" type="text" inputmode="decimal">');
        var description = $('<input class="form-control adjustment-line-description" maxlength="255">');
        row.append(account, $('<td>').append(debit), $('<td>').append(credit), $('<td>').append(description),
            $('<td>').append($('<button type="button" class="btn btn-danger adjustment-remove-line">').text('Hapus')));
        $('#adjustment-lines tbody').append(row);
        MoneyInput.init(debit[0], line ? line.debit : '');
        MoneyInput.init(credit[0], line ? line.kredit : '');
        description.val(line ? line.deskripsi : '');
    }
    function canonical(input) {
        return MoneyInput.value(input).replace(',', '.');
    }
    function formatTotal(cents) {
        var whole = String(Math.floor(cents / 100)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var fraction = String(cents % 100).padStart(2, '0');
        return 'Rp ' + whole + ',' + fraction;
    }
    function cents(input) {
        var value = canonical(input);
        if (!/^\d+(?:\.\d{1,2})?$/.test(value)) return 0;
        var parts = value.split('.');
        return Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0'));
    }
    function totals() {
        var debit = 0, credit = 0;
        $('#adjustment-lines tbody tr').each(function () {
            debit += cents($(this).find('.adjustment-debit')[0]);
            credit += cents($(this).find('.adjustment-credit')[0]);
        });
        $('#adjustment-debit-total').text(formatTotal(debit));
        $('#adjustment-credit-total').text(formatTotal(credit));
    }
    $('#adjustment-add-line').on('click', function () { addLine(null); });
    $('#adjustment-lines').on('click', '.adjustment-remove-line', function () {
        $(this).closest('tr').remove();
        totals();
    }).on('input change', '.adjustment-debit, .adjustment-credit', totals);

    if ($('#adjustment-form').length) {
        if (transaction && transaction.details.length) {
            transaction.details.forEach(addLine);
        } else {
            addLine(null);
            addLine(null);
        }
        totals();
        $('#adjustment-form').on('submit', function (event) {
            event.preventDefault();
            var details = [];
            var valid = true;
            $('#adjustment-lines tbody tr').each(function () {
                var row = $(this);
                var debit = canonical(row.find('.adjustment-debit')[0]);
                var credit = canonical(row.find('.adjustment-credit')[0]);
                if (!row.find('.adjustment-account').val() || (!!Number(debit) === !!Number(credit))) valid = false;
                details.push({
                    id_akun: row.find('.adjustment-account').val(),
                    debit: debit || '0',
                    kredit: credit || '0',
                    deskripsi: row.find('.adjustment-line-description').val()
                });
            });
            if (!valid || details.length < 2) {
                showError('Pilih akun dan isi hanya debit atau kredit pada setiap baris; minimal dua baris diperlukan.');
                return;
            }
            var totalDebit = 0, totalCredit = 0;
            $('#adjustment-lines tbody tr').each(function () {
                totalDebit += cents($(this).find('.adjustment-debit')[0]);
                totalCredit += cents($(this).find('.adjustment-credit')[0]);
            });
            if (!totalDebit || totalDebit !== totalCredit) {
                showError('Total debit dan kredit harus sama dan lebih dari nol.');
                return;
            }
            var file = $('#adjustment-file')[0].files[0];
            if (file && file.size > 5242880) {
                showError('Ukuran lampiran melebihi batas 5 MB.');
                return;
            }
            var data = new FormData();
            data.append('id', transaction ? transaction.id_transaksi : '');
            data.append('tanggal', $('#adjustment-date').val());
            data.append('deskripsi', $('#adjustment-description').val());
            data.append('details', JSON.stringify(details));
            data.append('hapus_lampiran', $('#adjustment-remove-file').prop('checked') ? '1' : '0');
            if (file) data.append('lampiran', file);
            if (csrf) data.append(csrf.name, csrf.value);
            $('button').prop('disabled', true);
            $.ajax({url: <?= json_encode(site_url('penyesuaian_akuntansi/simpan')) ?>,
                method: 'POST', data: data, processData: false, contentType: false})
                .done(function () { location.href = <?= json_encode(site_url('penyesuaian_akuntansi')) ?>; })
                .fail(function (xhr) {
                    showError(xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.');
                    $('button').prop('disabled', false);
                });
        });
    }
    function request(url, fields) {
        var data = fields;
        if (csrf) data[csrf.name] = csrf.value;
        $('button').prop('disabled', true);
        $.post(url, data).done(function () {
            location.href = <?= json_encode(site_url('penyesuaian_akuntansi')) ?>;
        }).fail(function (xhr) {
            showError(xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.');
            $('button').prop('disabled', false);
        });
    }
    $('#adjustment-post').on('click', function () {
        if (confirm('Posting penyesuaian dan buat jurnal?')) {
            request(<?= json_encode(site_url('penyesuaian_akuntansi/posting')) ?>, {id: transaction.id_transaksi});
        }
    });
    $('#adjustment-cancel').on('submit', function (event) {
        event.preventDefault();
        if (confirm('Batalkan transaksi dan buat jurnal pembalik?')) {
            request(<?= json_encode(site_url('penyesuaian_akuntansi/batalkan')) ?>, {
                id: transaction.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()
            });
        }
    });
});
</script>
