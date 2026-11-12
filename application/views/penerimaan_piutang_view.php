<?php
$editable = !$transaction || $transaction->status === 'draft';
$customer_options = array();
foreach ($customers as $customer) {
    $customer_options[] = array(
        'id' => $customer->id_pelanggan,
        'label' => $customer->kode_pelanggan . ' - ' . $customer->nama_pelanggan
    );
}
$cash_options = array();
foreach ($cash_accounts as $account) {
    $cash_options[] = array(
        'id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable
    );
}
?>
<h3>Penerimaan Piutang</h3>
<p class="text-muted" role="note">Nominal: gunakan titik untuk ribuan dan koma untuk desimal, misalnya <strong>1.000,50</strong>. Angka diformat setelah kolom ditinggalkan.</p>
<p>Penerimaan dialokasikan ke tagihan pelanggan (penjualan kredit atau saldo awal piutang). Saat diposting, jurnal otomatis mendebit kas/bank dan mengkredit piutang usaha.</p>

<?php if ($transaction): ?>
    <p><a href="<?= site_url('penerimaan_piutang') ?>">&larr; Kembali ke daftar penerimaan piutang</a></p>
    <h4><?= $editable ? 'Draft penerimaan piutang' : 'Rincian penerimaan piutang' ?></h4>
    <p>
        <strong>Nomor:</strong> <?= html_escape($transaction->nomor_transaksi) ?>
        &nbsp; <strong>Status:</strong> <?= html_escape($transaction->status) ?>
        <?php if (!$editable): ?>
            &nbsp; <strong>Pelanggan:</strong> <?= html_escape($transaction->nama_pelanggan) ?>
            &nbsp; <strong>Total:</strong> Rp <?= html_escape(format_nominal($transaction->total)) ?>
        <?php endif; ?>
    </p>
    <?php if (!$editable): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Tagihan</th><th>Tanggal</th><th>Total tagihan</th><th>Dibayar</th></tr></thead>
                <tbody>
                <?php foreach ($transaction->alokasi as $row): ?>
                    <tr>
                        <td><?= html_escape($row['nomor_transaksi']) ?></td>
                        <td><?= html_escape($row['tanggal']) ?></td>
                        <td>Rp <?= html_escape(format_nominal($row['total'])) ?></td>
                        <td>Rp <?= html_escape(format_nominal($row['jumlah'])) ?></td>
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

<div id="receipt-message" role="alert"></div>
<?php if ($editable): ?>
    <form id="receipt-form" enctype="multipart/form-data">
        <fieldset>
            <input id="receipt-id" type="hidden" value="<?= $transaction ? (int) $transaction->id_transaksi : '' ?>">
            <div class="row">
                <div class="form-group col-sm-3">
                    <label for="receipt-date">Tanggal</label>
                    <input class="form-control" id="receipt-date" type="date" required
                        value="<?= html_escape($transaction ? $transaction->tanggal : date('Y-m-d')) ?>">
                </div>
                <div class="form-group col-sm-5">
                    <label for="receipt-customer">Pelanggan</label>
                    <select class="form-control" id="receipt-customer" required></select>
                </div>
                <div class="form-group col-sm-4">
                    <label for="receipt-cash">Diterima di akun kas/bank</label>
                    <select class="form-control" id="receipt-cash" required></select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-4">
                    <label for="receipt-reference">Referensi</label>
                    <input class="form-control" id="receipt-reference" maxlength="100">
                </div>
                <div class="form-group col-sm-8">
                    <label for="receipt-description">Keterangan</label>
                    <input class="form-control" id="receipt-description" maxlength="255">
                </div>
            </div>
            <h4>Alokasi ke tagihan</h4>
            <p class="text-muted">Centang tagihan yang dibayar, lalu isi jumlah yang diterima (tidak boleh melebihi sisa).</p>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed" id="invoice-table">
                    <thead><tr><th>Bayar</th><th>Tagihan</th><th>Tanggal</th><th>Jatuh tempo</th><th>Total</th><th>Sisa</th><th>Jumlah diterima</th></tr></thead>
                    <tbody><tr><td colspan="7" class="text-center">Pilih pelanggan untuk menampilkan tagihan.</td></tr></tbody>
                </table>
            </div>
            <div class="form-group">
                <label for="receipt-file">Lampiran bukti (PDF/JPG/PNG, maks. 5 MB)</label>
                <input id="receipt-file" type="file" accept="application/pdf,image/jpeg,image/png">
                <?php if ($transaction && $transaction->lampiran_nama): ?>
                    <p>Lampiran saat ini:
                        <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a>
                        <label><input type="checkbox" id="receipt-remove-file"> Hapus lampiran</label>
                    </p>
                    <p class="text-muted">Memilih file baru akan menggantikan lampiran saat ini.</p>
                <?php endif; ?>
            </div>
            <p><button type="submit" class="btn btn-primary">Simpan draft</button></p>
        </fieldset>
    </form>
<?php endif; ?>

<?php if ($transaction && $transaction->status === 'draft'): ?>
    <button type="button" id="post-receipt" class="btn btn-success">Posting penerimaan tersimpan</button>
    <p>Pastikan draft sudah disimpan sebelum melakukan posting.</p>
<?php elseif ($transaction && $transaction->status === 'diposting'): ?>
    <h4>Pembatalan</h4>
    <p>Pembatalan membuat jurnal pembalik dan mengembalikan sisa piutang tagihan.</p>
    <form id="cancel-receipt" class="form-inline">
        <label for="cancel-date">Tanggal</label>
        <input id="cancel-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
        <label for="cancel-reason">Alasan</label>
        <input id="cancel-reason" class="form-control" required maxlength="255">
        <button type="submit" class="btn btn-danger">Batalkan penerimaan</button>
    </form>
<?php endif; ?>

<h4>Riwayat penerimaan piutang</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Pelanggan</th><th>Total</th><th>Lampiran</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $item): ?>
            <tr>
                <td><?= html_escape($item->tanggal) ?></td>
                <td><?= html_escape($item->nomor_transaksi) ?></td>
                <td><?= html_escape($item->nama_pelanggan) ?></td>
                <td>Rp <?= html_escape(format_nominal($item->total)) ?></td>
                <td><?php if ($item->lampiran_nama): ?><a href="<?= site_url('lampiran/unduh/' . (int) $item->id_transaksi) ?>">Unduh</a><?php else: ?>-<?php endif; ?></td>
                <td><?= html_escape($item->status) ?></td>
                <td><a class="btn btn-default btn-xs" href="<?= site_url('penerimaan_piutang?id=' . (int) $item->id_transaksi) ?>">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
            <tr><td colspan="7" class="text-center">Belum ada penerimaan piutang.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(function () {
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var options = <?= json_encode(array('customers' => $customer_options, 'cash' => $cash_options),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;
    var saved = {};
    if (transaction && transaction.alokasi) {
        transaction.alokasi.forEach(function (row) { saved[row.id_transaksi_tagihan] = row.jumlah; });
    }

    function fillSelect(select, list, selected, placeholder) {
        select.append($('<option>').val('').text(placeholder));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', !!item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }

    function showError(message) {
        $('#receipt-message').empty().append($('<div class="alert alert-danger">').text(message));
    }

    function renderInvoices(rows) {
        var body = $('#invoice-table tbody').empty();
        if (!rows.length) {
            body.append($('<tr>').append($('<td colspan="7" class="text-center">').text('Pelanggan ini tidak memiliki piutang terbuka.')));
            return;
        }
        rows.forEach(function (row) {
            var tr = $('<tr>').attr('data-id', row.id_transaksi).attr('data-sisa', row.sisa);
            var amount = $('<input class="form-control" type="number" min="0.01" step="0.01" max="' + row.sisa + '">')
                .val(saved[row.id_transaksi] != null ? saved[row.id_transaksi] : row.sisa).prop('disabled', saved[row.id_transaksi] == null);
            MoneyInput.init(amount[0], amount.val());
            var check = $('<input type="checkbox">').prop('checked', saved[row.id_transaksi] != null).on('change', function () {
                amount.prop('disabled', !this.checked).prop('required', this.checked);
            });
            amount.prop('required', check.prop('checked'));
            tr.append($('<td>').append(check))
                .append($('<td>').text(row.nomor_transaksi))
                .append($('<td>').text(row.tanggal))
                .append($('<td>').text(row.jatuh_tempo || '-'))
                .append($('<td>').text('Rp ' + MoneyInput.display(row.total)))
                .append($('<td>').text('Rp ' + MoneyInput.display(row.sisa)))
                .append($('<td>').append(amount));
            body.append(tr);
        });
    }

    function loadInvoices() {
        var customer = $('#receipt-customer').val();
        if (!customer) {
            return;
        }
        $.getJSON(<?= json_encode(site_url('penerimaan_piutang/tagihan')) ?>, {id_pelanggan: customer}).done(function (response) {
            renderInvoices(response.data || []);
        }).fail(function (xhr) {
            showError(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Daftar tagihan gagal dimuat.');
        });
    }

    if ($('#receipt-customer').length) {
        fillSelect($('#receipt-customer'), options.customers, transaction ? transaction.id_pelanggan : null, '-- Pilih pelanggan --');
        fillSelect($('#receipt-cash'), options.cash, transaction ? transaction.id_akun_kas : null, '-- Pilih akun kas/bank --');
        if (transaction) {
            $('#receipt-reference').val(transaction.referensi || '');
            $('#receipt-description').val(transaction.deskripsi || '');
        }
        $('#receipt-customer').on('change', function () { saved = {}; loadInvoices(); });
        loadInvoices();
    }

    function request(url, formData) {
        if (csrf) {
            formData.append(csrf.name, csrf.value);
        }
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: formData, processData: false, contentType: false}).done(function () {
            location.href = <?= json_encode(site_url('penerimaan_piutang')) ?>;
        }).fail(function (xhr) {
            showError(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.');
            $('button').prop('disabled', false);
        });
    }

    function simple(fields) {
        var formData = new FormData();
        Object.keys(fields).forEach(function (key) { formData.append(key, fields[key]); });
        return formData;
    }

    $('#receipt-form').on('submit', function (event) {
        event.preventDefault();
        var allocations = [];
        $('#invoice-table tbody tr[data-id]').each(function () {
            var row = $(this);
            if (row.find('input[type=checkbox]').prop('checked')) {
                allocations.push({id_transaksi_tagihan: row.data('id'), jumlah: MoneyInput.value(row.find('[data-money]')[0])});
            }
        });
        if (!allocations.length) {
            showError('Pilih minimal satu tagihan yang dibayar.');
            return;
        }
        var formData = simple({
            id: $('#receipt-id').val(), tanggal: $('#receipt-date').val(),
            data: JSON.stringify({
                id_pelanggan: $('#receipt-customer').val(), id_akun_kas: $('#receipt-cash').val(),
                referensi: $('#receipt-reference').val(), deskripsi: $('#receipt-description').val(),
                alokasi: allocations
            }),
            hapus_lampiran: $('#receipt-remove-file').prop('checked') ? '1' : '0'
        });
        var file = $('#receipt-file')[0].files[0];
        if (file) {
            if (file.size > 5242880) {
                showError('Ukuran lampiran melebihi batas 5 MB.');
                return;
            }
            formData.append('lampiran', file);
        }
        request(<?= json_encode(site_url('penerimaan_piutang/simpan')) ?>, formData);
    });

    $('#post-receipt').on('click', function () {
        if (confirm('Posting penerimaan piutang ini dan buat jurnal otomatis?')) {
            request(<?= json_encode(site_url('penerimaan_piutang/posting')) ?>, simple({id: transaction.id_transaksi}));
        }
    });

    $('#cancel-receipt').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan penerimaan piutang dan buat jurnal pembalik?')) {
            return;
        }
        request(<?= json_encode(site_url('penerimaan_piutang/batalkan')) ?>, simple({
            id: transaction.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()
        }));
    });
});
</script>
