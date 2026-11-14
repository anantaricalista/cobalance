<h3>Beban Penjualan Jasa</h3>
<p>Catat biaya baru yang berkaitan langsung dengan rincian jasa pada penjualan terposting. Jangan masukkan kembali biaya yang sudah dicatat pada modul lain. Tidak ada beban otomatis jika tidak ada biaya.</p>
<p class="text-muted">Nominal dicatat tanpa rincian pajak. Gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>. Beban kredit menjadi tagihan pada modul Pembayaran Utang.</p>

<div id="service-expense-message" role="alert"></div>
<form id="service-expense-form">
    <input type="hidden" id="expense-id">
    <h4 id="expense-form-title">Beban baru</h4>
    <div class="row">
        <div class="form-group col-sm-8">
            <label for="expense-sale">Rincian jasa terjual</label>
            <select id="expense-sale" class="form-control" required>
                <option value="">-- Pilih jasa terjual --</option>
                <?php foreach ($sales as $sale): ?>
                    <option value="<?= (int) $sale->id_detail_transaksi ?>"><?= html_escape($sale->nomor_transaksi . ' - ' . format_tanggal_indonesia($sale->tanggal) . ' - ' . $sale->deskripsi . ' (rincian #' . $sale->id_detail_transaksi . ') - ' . $sale->nama_pelanggan) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-sm-4">
            <label for="expense-date">Tanggal beban</label>
            <input id="expense-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-6">
            <label for="expense-account">Akun beban</label>
            <select id="expense-account" class="form-control account-search" required>
                <option value="">-- Pilih akun beban --</option>
                <?php foreach ($expense_accounts as $account): ?>
                    <option value="<?= (int) $account->id_akun ?>" <?= $account->selectable ? '' : 'disabled' ?>><?= html_escape(str_repeat('- ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-sm-3">
            <label for="expense-amount">Jumlah</label>
            <input id="expense-amount" type="number" min="0.01" step="0.01" class="form-control" required>
        </div>
        <div class="form-group col-sm-3">
            <label for="expense-payment">Cara pembayaran</label>
            <select id="expense-payment" class="form-control"><option value="tunai">Tunai</option><option value="kredit">Kredit</option></select>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-sm-6" id="expense-cash-group">
            <label for="expense-cash">Akun kas/bank</label>
            <select id="expense-cash" class="form-control account-search">
                <option value="">-- Pilih akun kas/bank --</option>
                <?php foreach ($cash_accounts as $account): ?>
                    <option value="<?= (int) $account->id_akun ?>" <?= $account->selectable ? '' : 'disabled' ?>><?= html_escape(str_repeat('- ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-sm-6 expense-credit-group">
            <label for="expense-supplier">Pemasok</label>
            <select id="expense-supplier" class="form-control"><option value="">-- Pilih pemasok --</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= (int) $supplier->id_pemasok ?>"><?= html_escape($supplier->nama_pemasok) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-sm-3 expense-credit-group">
            <label for="expense-due">Jatuh tempo</label>
            <input id="expense-due" type="date" class="form-control">
        </div>
    </div>
    <div class="form-group">
        <label for="expense-description">Keterangan biaya</label>
        <input id="expense-description" class="form-control" maxlength="255" required>
    </div>
    <button class="btn btn-primary" type="submit">Simpan draft</button>
    <button class="btn btn-default" type="button" id="expense-reset">Beban baru</button>
</form>

<h4>Ringkasan jasa terjual</h4>
<p>Selisih adalah pendapatan sebelum pajak setelah retur terposting dikurangi beban langsung yang diposting di modul ini; bukan laba bersih perusahaan.</p>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Penjualan</th><th>Jasa</th><th>Pendapatan sebelum pajak</th><th>Retur sebelum pajak</th><th>Beban langsung</th><th>Selisih</th></tr></thead>
        <tbody>
        <?php foreach ($sales as $sale): ?>
            <tr>
                <td><?= html_escape($sale->nomor_transaksi . ' - ' . format_tanggal_indonesia($sale->tanggal)) ?></td>
                <td><?= html_escape($sale->deskripsi . ' (rincian #' . $sale->id_detail_transaksi . ')') ?></td>
                <td>Rp <?= html_escape(format_nominal($sale->nilai_sebelum_pajak)) ?></td>
                <td>Rp <?= html_escape(format_nominal($sale->retur)) ?></td>
                <td>Rp <?= html_escape(format_nominal($sale->beban)) ?></td>
                <td>Rp <?= html_escape(format_nominal(kurangi_nominal(kurangi_nominal($sale->nilai_sebelum_pajak, $sale->retur), $sale->beban))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$sales): ?><tr><td colspan="6">Belum ada jasa pada penjualan terposting.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<h4>Riwayat beban penjualan jasa</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Penjualan</th><th>Keterangan</th><th>Akun beban</th><th>Pembayaran</th><th>Jumlah</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($transactions as $item): ?>
            <tr>
                <td><?= html_escape(format_tanggal_indonesia($item->tanggal)) ?></td><td><?= html_escape($item->nomor_transaksi) ?></td>
                <td><?= html_escape($item->nomor_penjualan . ' - ' . $item->jasa . ' (rincian #' . $item->id_detail_asal . ')') ?></td><td><?= html_escape($item->rincian) ?></td>
                <td><?= html_escape($item->kode_akun . ' - ' . $item->nama_akun) ?></td>
                <td>
                    <?= html_escape(ucfirst($item->cara_pembayaran)) ?><br>
                    <?php if ($item->cara_pembayaran === 'tunai'): ?>
                        <?= html_escape($item->kode_kas . ' - ' . $item->nama_kas) ?>
                    <?php else: ?>
                        <?= html_escape($item->nama_pemasok) ?> (jatuh tempo <?= html_escape(format_tanggal_indonesia($item->jatuh_tempo)) ?>)
                    <?php endif; ?>
                </td>
                <td>Rp <?= html_escape(format_nominal($item->total)) ?></td><td><?= html_escape($item->status) ?></td>
                <td>
                    <?php if ($item->status === 'draft'): ?>
                        <button type="button" class="btn btn-default btn-xs expense-edit" data-id="<?= (int) $item->id_transaksi ?>">Edit</button>
                        <?php if ($this->can_post_transactions): ?><button type="button" class="btn btn-success btn-xs expense-post" data-id="<?= (int) $item->id_transaksi ?>">Posting</button><?php endif; ?>
                    <?php elseif ($item->status === 'diposting' && $this->can_post_transactions): ?>
                        <button type="button" class="btn btn-danger btn-xs expense-cancel" data-id="<?= (int) $item->id_transaksi ?>">Batalkan</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$transactions): ?><tr><td colspan="9">Belum ada beban penjualan jasa.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($this->can_post_transactions): ?>
<form id="expense-cancel-form" class="form-inline" style="display:none">
    <h4>Pembatalan beban</h4>
    <input type="hidden" id="expense-cancel-id">
    <label for="expense-cancel-date">Tanggal</label>
    <input id="expense-cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
    <label for="expense-cancel-reason">Alasan</label>
    <input id="expense-cancel-reason" class="form-control" maxlength="255" required>
    <button type="submit" class="btn btn-danger">Batalkan beban</button>
</form>
<?php endif; ?>
<script>
$(function () {
    var transactions = <?= json_encode($transactions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var base = <?= json_encode(site_url('beban_penjualan_jasa')) ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array('name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;
    MoneyInput.init($('#expense-amount')[0], '');

    function paymentChanged() {
        var credit = $('#expense-payment').val() === 'kredit';
        $('#expense-cash-group').toggle(!credit);
        $('#expense-cash').prop('required', !credit).prop('disabled', credit).trigger('change.select2');
        $('.expense-credit-group').toggle(credit);
        $('#expense-supplier, #expense-due').prop('required', credit).prop('disabled', !credit);
    }

    function resetForm() {
        $('#service-expense-form')[0].reset();
        $('#expense-id').val('');
        $('#expense-form-title').text('Beban baru');
        MoneyInput.set($('#expense-amount')[0], '');
        $('#expense-account, #expense-cash').trigger('change.select2');
        paymentChanged();
    }

    function request(path, fields) {
        if (csrf) fields[csrf.name] = csrf.value;
        $('#service-expense-message').empty();
        $('#service-expense-form button, .expense-post, .expense-cancel, #expense-cancel-form button').prop('disabled', true);
        $.ajax({url: base + '/' + path, type: 'POST', data: fields, dataType: 'json'})
            .done(function () { location.href = base; })
            .fail(function (xhr) {
                $('#service-expense-message').empty().append($('<div class="alert alert-danger">').text(
                    xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal.'));
                $('#service-expense-form button, .expense-post, .expense-cancel, #expense-cancel-form button').prop('disabled', false);
            });
    }

    $('#expense-payment').on('change', paymentChanged);
    $('#expense-reset').on('click', resetForm);
    paymentChanged();
    $('#service-expense-form').on('submit', function (event) {
        event.preventDefault();
        request('simpan', {id: $('#expense-id').val(), tanggal: $('#expense-date').val(), data: JSON.stringify({
            id_detail_asal: $('#expense-sale').val(), id_akun: $('#expense-account').val(),
            jumlah: MoneyInput.value($('#expense-amount')[0]), cara_pembayaran: $('#expense-payment').val(),
            id_akun_kas: $('#expense-cash').val(), id_pemasok: $('#expense-supplier').val(),
            jatuh_tempo: $('#expense-due').val(), deskripsi: $('#expense-description').val()
        })});
    });
    $('.expense-edit').on('click', function () {
        var id = String($(this).data('id'));
        var item = transactions.find(function (row) { return String(row.id_transaksi) === id; });
        if (!item) return;
        $('#expense-id').val(id);
        $('#expense-form-title').text('Edit draft ' + item.nomor_transaksi);
        $('#expense-sale').val(String(item.id_detail_asal));
        $('#expense-date').val(item.tanggal);
        $('#expense-account').val(String(item.id_akun)).trigger('change.select2');
        MoneyInput.set($('#expense-amount')[0], item.total);
        $('#expense-payment').val(item.cara_pembayaran);
        $('#expense-cash').val(item.id_akun_kas == null ? '' : String(item.id_akun_kas)).trigger('change.select2');
        $('#expense-supplier').val(item.id_pemasok == null ? '' : String(item.id_pemasok));
        $('#expense-due').val(item.jatuh_tempo || '');
        $('#expense-description').val(item.rincian);
        paymentChanged();
        document.getElementById('service-expense-form').scrollIntoView();
    });
    $('.expense-post').on('click', function () {
        if (confirm('Posting beban jasa ini?')) request('posting', {id: $(this).data('id')});
    });
    $('.expense-cancel').on('click', function () {
        $('#expense-cancel-id').val($(this).data('id'));
        $('#expense-cancel-form').show();
        document.getElementById('expense-cancel-form').scrollIntoView();
    });
    $('#expense-cancel-form').on('submit', function (event) {
        event.preventDefault();
        if (confirm('Batalkan beban jasa ini dan buat jurnal pembalik?')) {
            request('batalkan', {id: $('#expense-cancel-id').val(), tanggal: $('#expense-cancel-date').val(), alasan: $('#expense-cancel-reason').val()});
        }
    });
});
</script>
