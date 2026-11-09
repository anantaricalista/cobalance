<?php
$editable = !$transaction || $transaction->status === 'draft';
$product_options = array();
foreach ($products as $product) {
    $product_options[] = array(
        'id' => $product->id_produk,
        'label' => $product->kode_produk . ' - ' . $product->nama_produk . ' (' . $product->satuan . ')',
        'price' => ''
    );
}
$supplier_options = array();
foreach ($suppliers as $supplier) {
    $supplier_options[] = array(
        'id' => $supplier->id_pemasok,
        'label' => $supplier->kode_pemasok . ' - ' . $supplier->nama_pemasok
    );
}
$tax_options = array();
foreach ($taxes as $tax) {
    $tax_options[] = array(
        'id' => $tax->id_pajak,
        'label' => $tax->kode_pajak . ' - ' . $tax->nama_pajak . ' (' . $tax->tarif_persen . '%)'
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
<h3>Pembelian</h3>
<p>Draft baru diposting menjadi jurnal otomatis. Pembelian barang menambah persediaan dan memperbarui harga pokok rata-rata; pembelian jasa dibebankan ke akun pembelian/beban pada master produk.</p>

<?php if ($transaction): ?>
    <p><a href="<?= site_url('pembelian') ?>">&larr; Kembali ke daftar pembelian</a></p>
<?php endif; ?>

<?php if ($transaction): ?>
    <h4><?= $editable ? 'Draft pembelian' : 'Rincian pembelian' ?></h4>
    <p>
        <strong>Nomor:</strong> <?= html_escape($transaction->nomor_transaksi) ?>
        &nbsp; <strong>Status:</strong> <?= html_escape($transaction->status) ?>
        <?php if (!$editable): ?>
            &nbsp; <strong>Total:</strong> Rp <?= html_escape($transaction->total) ?>
        <?php endif; ?>
    </p>
    <?php if (!$editable): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead><tr><th>Produk/Jasa</th><th>Jumlah</th><th>Harga</th><th>Potongan</th><th>Pajak</th><th>Nilai sebelum pajak</th><th>Nilai pajak</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($transaction->details as $detail): ?>
                    <tr>
                        <td><?= html_escape($detail['deskripsi']) ?></td>
                        <td><?= html_escape($detail['jumlah']) ?> <?= html_escape($detail['satuan']) ?></td>
                        <td>Rp <?= html_escape($detail['harga']) ?></td>
                        <td>Rp <?= html_escape($detail['diskon']) ?></td>
                        <td><?= html_escape($detail['nama_pajak'] ? $detail['nama_pajak'] . ' (' . $detail['mode_pajak'] . ')' : 'Tidak dikenakan') ?></td>
                        <td>Rp <?= html_escape($detail['nilai_sebelum_pajak']) ?></td>
                        <td>Rp <?= html_escape($detail['nilai_pajak']) ?></td>
                        <td>Rp <?= html_escape($detail['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php if (!$editable && $transaction->lampiran_nama): ?>
        <p><strong>Lampiran:</strong>
            <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
<?php endif; ?>

<?php if (!$transaction || $editable): ?>
    <div id="sale-message" role="alert"></div>
    <form id="sale-form" enctype="multipart/form-data">
        <fieldset>
            <input id="sale-id" type="hidden" value="<?= $transaction ? (int) $transaction->id_transaksi : '' ?>">
            <div class="row">
                <div class="form-group col-sm-3">
                    <label for="sale-date">Tanggal</label>
                    <input class="form-control" id="sale-date" type="date" required
                        value="<?= html_escape($transaction ? $transaction->tanggal : date('Y-m-d')) ?>">
                </div>
                <div class="form-group col-sm-5">
                    <label for="sale-supplier">Pemasok</label>
                    <select class="form-control" id="sale-supplier" required></select>
                </div>
                <div class="form-group col-sm-4">
                    <label for="sale-payment">Cara pembayaran</label>
                    <select class="form-control" id="sale-payment" required>
                        <option value="tunai">Tunai</option>
                        <option value="kredit">Kredit</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-sm-4" id="due-group">
                    <label for="sale-due">Jatuh tempo</label>
                    <input class="form-control" id="sale-due" type="date">
                </div>
                <div class="form-group col-sm-4" id="cash-group">
                    <label for="sale-cash">Akun kas/bank</label>
                    <select class="form-control" id="sale-cash"></select>
                </div>
                <div class="form-group col-sm-4">
                    <label for="sale-reference">No. faktur pemasok</label>
                    <input class="form-control" id="sale-reference" maxlength="100">
                </div>
                <div class="form-group col-sm-12">
                    <label for="sale-description">Keterangan</label>
                    <input class="form-control" id="sale-description" maxlength="255">
                </div>
            </div>
            <div class="form-group">
                <label for="sale-file">Lampiran bukti (PDF/JPG/PNG, maks. 5 MB)</label>
                <input id="sale-file" type="file" accept="application/pdf,image/jpeg,image/png">
                <?php if ($transaction && $transaction->lampiran_nama): ?>
                    <p>Lampiran saat ini:
                        <a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a>
                        <label><input type="checkbox" id="sale-remove-file"> Hapus lampiran</label>
                    </p>
                    <p class="text-muted">Memilih file baru akan menggantikan lampiran saat ini.</p>
                <?php endif; ?>
            </div>
            <h4>Rincian barang dan jasa</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed" id="sale-lines">
                    <thead>
                        <tr>
                            <th>Produk/Jasa</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th>Potongan per rincian</th>
                            <th>Mode pajak</th>
                            <th>Pajak</th>
                            <?php if ($editable): ?><th>Aksi</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <p><button type="button" class="btn btn-default" id="add-sale-line">Tambah rincian</button></p>
            <p><button type="submit" class="btn btn-primary">Simpan draft</button></p>
        </fieldset>
    </form>
<?php endif; ?>

<?php if ($transaction && $transaction->status === 'draft'): ?>
    <button type="button" id="post-sale" class="btn btn-success">Posting pembelian tersimpan</button>
    <p>Pastikan draft sudah disimpan sebelum melakukan posting.</p>
<?php elseif ($transaction && $transaction->status === 'diposting'): ?>
    <h4>Pembatalan</h4>
    <p>Pembatalan ditolak jika pembelian sudah dialokasikan atau persediaan telah berubah setelah transaksi ini.</p>
    <form id="cancel-sale" class="form-inline">
        <label for="cancel-date">Tanggal</label>
        <input id="cancel-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
        <label for="cancel-reason">Alasan</label>
        <input id="cancel-reason" class="form-control" required maxlength="255">
        <button type="submit" class="btn btn-danger">Batalkan pembelian</button>
    </form>
<?php endif; ?>

<h4>Riwayat pembelian</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Pemasok</th><th>Pembayaran</th><th>Total</th><th>Lampiran</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $item): ?>
            <tr>
                <td><?= html_escape($item->tanggal) ?></td>
                <td><?= html_escape($item->nomor_transaksi) ?></td>
                <td><?= html_escape($item->nama_pemasok) ?></td>
                <td><?= html_escape($item->cara_pembayaran === 'kredit' ? 'Kredit' : 'Tunai') ?></td>
                <td>Rp <?= html_escape($item->total) ?></td>
                <td><?php if ($item->lampiran_nama): ?><a href="<?= site_url('lampiran/unduh/' . (int) $item->id_transaksi) ?>">Unduh</a><?php else: ?>-<?php endif; ?></td>
                <td><?= html_escape($item->status) ?></td>
                <td><a class="btn btn-default btn-xs" href="<?= site_url('pembelian?id=' . (int) $item->id_transaksi) ?>">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?>
            <tr><td colspan="8" class="text-center">Belum ada transaksi pembelian.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
$(function () {
    var transaction = <?= json_encode($transaction, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var options = <?= json_encode(array(
        'suppliers' => $supplier_options,
        'products' => $product_options,
        'taxes' => $tax_options,
        'cash' => $cash_options
    ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var editable = <?= $editable ? 'true' : 'false' ?>;

    function fillSelect(select, list, selected, placeholder) {
        select.append($('<option>').val('').text(placeholder));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', !!item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }

    function addLine(values) {
        values = values || {};
        var row = $('<tr>');
        var product = $('<select class="form-control" data-field="id_produk" required>');
        fillSelect(product, options.products, values.id_produk, '-- Pilih produk/jasa --');
        product.on('change', function () {
            var selected = options.products.filter(function (item) { return String(item.id) === product.val(); })[0];
            if (selected) MoneyInput.set(row.find('[data-field="harga"]')[0], selected.price);
        });
        row.append($('<td>').append(product));
        [
            ['jumlah', '1.0000', '0.0001'],
            ['harga', '', '0.01'],
            ['diskon', '0.00', '0.01']
        ].forEach(function (field) {
            var input = $('<input class="form-control" type="number" min="0" step="' + field[2] + '" required>');
            input.attr('data-field', field[0]).val(values[field[0]] == null ? field[1] : values[field[0]]);
            if (field[0] === 'jumlah') input.attr('min', '0.0001');
            else MoneyInput.init(input[0], input.val());
            row.append($('<td>').append(input));
        });
        var mode = $('<select class="form-control" data-field="mode_pajak" required>')
            .append($('<option>').val('tidak_dikenakan').text('Tidak dikenakan'))
            .append($('<option>').val('belum_termasuk').text('Belum termasuk'))
            .append($('<option>').val('termasuk').text('Termasuk'));
        mode.val(values.mode_pajak || 'tidak_dikenakan');
        row.append($('<td>').append(mode));
        var tax = $('<select class="form-control" data-field="id_pajak">');
        fillSelect(tax, options.taxes, values.id_pajak, '-- Pilih pajak --');
        if (mode.val() === 'tidak_dikenakan') tax.prop('disabled', true);
        mode.on('change', function () {
            var taxed = mode.val() !== 'tidak_dikenakan';
            tax.prop('disabled', !taxed).prop('required', taxed);
            if (!taxed) tax.val('');
        });
        row.append($('<td>').append(tax));
        if (editable) {
            row.append($('<td>').append($('<button type="button" class="btn btn-danger btn-xs">')
                .text('Hapus').on('click', function () { row.remove(); })));
        }
        $('#sale-lines tbody').append(row);
    }

    if ($('#sale-supplier').length) {
        fillSelect($('#sale-supplier'), options.suppliers, transaction ? transaction.id_pemasok : null, '-- Pilih pemasok --');
        fillSelect($('#sale-cash'), options.cash, transaction ? transaction.id_akun_kas : null, '-- Pilih akun kas/bank --');
        if (transaction) {
            $('#sale-payment').val(transaction.cara_pembayaran || 'tunai');
            $('#sale-due').val(transaction.jatuh_tempo || '');
            $('#sale-reference').val(transaction.referensi || '');
            $('#sale-description').val(transaction.deskripsi || '');
            (transaction.details || []).forEach(addLine);
        } else {
            addLine();
        }
        function paymentChanged() {
            var credit = $('#sale-payment').val() === 'kredit';
            $('#due-group').toggle(credit);
            $('#sale-due').prop('required', credit);
            $('#cash-group').toggle(!credit);
            $('#sale-cash').prop('required', !credit);
        }
        $('#sale-payment').on('change', paymentChanged);
        paymentChanged();
        $('#add-sale-line').on('click', function () { addLine(); });
    }

    function request(url, data) {
        var formData = new FormData();
        Object.keys(data).forEach(function (key) { formData.append(key, data[key]); });
        if (data.lampiran !== undefined) {
            formData.delete('lampiran');
            if (data.lampiran) formData.append('lampiran', data.lampiran);
        }
        <?php if ($this->config->item('csrf_protection')): ?>
        formData.append(<?= json_encode($this->security->get_csrf_token_name()) ?>, <?= json_encode($this->security->get_csrf_hash()) ?>);
        <?php endif; ?>
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: formData, processData: false, contentType: false}).done(function () {
            location.href = <?= json_encode(site_url('pembelian')) ?>;
        }).fail(function (xhr) {
            $('#sale-message').empty().append($('<div class="alert alert-danger">').text(
                xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'
            ));
            $('button').prop('disabled', false);
        });
    }

    $('#sale-form').on('submit', function (event) {
        event.preventDefault();
        var data = {
            id_pemasok: $('#sale-supplier').val(),
            cara_pembayaran: $('#sale-payment').val(),
            jatuh_tempo: $('#sale-due').val(),
            id_akun_kas: $('#sale-cash').val(),
            referensi: $('#sale-reference').val(),
            deskripsi: $('#sale-description').val(),
            details: []
        };
        $('#sale-lines tbody tr').each(function () {
            var row = {};
            $(this).find('[data-field]').each(function () { row[$(this).data('field')] = $(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val(); });
            data.details.push(row);
        });
        request(<?= json_encode(site_url('pembelian/simpan')) ?>, {
            id: $('#sale-id').val(), tanggal: $('#sale-date').val(), data: JSON.stringify(data),
            hapus_lampiran: $('#sale-remove-file').prop('checked') ? '1' : '0',
            lampiran: $('#sale-file')[0].files[0] || null
        });
    });

    $('#post-sale').on('click', function () {
        if (confirm('Posting pembelian ini dan buat jurnal otomatis?')) {
            request(<?= json_encode(site_url('pembelian/posting')) ?>, {id: transaction.id_transaksi});
        }
    });

    $('#cancel-sale').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan pembelian dan buat jurnal pembalik?')) {
            return;
        }
        request(<?= json_encode(site_url('pembelian/batalkan')) ?>, {
            id: transaction.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()
        });
    });
});
</script>
