<h3>Penyesuaian Persediaan</h3>
<p>Setiap transaksi untuk satu produk. Stock opname menggunakan jumlah fisik yang dimasukkan dan membandingkannya dengan saldo pada saat posting.</p>
<p><a href="<?= site_url('persediaan') ?>">Saldo dan riwayat stok</a> | <a href="<?= site_url('penyesuaian_persediaan') ?>">Penyesuaian baru</a></p>
<div id="adjustment-message" role="alert"></div>
<?php if ($transaction): ?>
    <h4><?= html_escape($transaction->nomor_transaksi) ?> &mdash; <?= html_escape($transaction->status) ?></h4>
    <p>Produk: <?= html_escape($transaction->kode_produk . ' - ' . $transaction->nama_produk) ?> |
        Jenis: <?= html_escape($transaction->jenis) ?> | Jumlah input: <?= html_escape($transaction->jumlah) ?> |
        Nilai tercatat: Rp <?= html_escape($transaction->subtotal) ?></p>
    <p>Akun selisih: <?= html_escape($transaction->kode_akun . ' - ' . $transaction->nama_akun) ?> |
        Keterangan: <?= html_escape($transaction->deskripsi) ?></p>
    <?php if ($transaction->lampiran_nama): ?>
        <p><a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
    <?php if ($transaction->status === 'draft'): ?>
        <p>Draft tidak dapat diubah. Jika data salah, buat draft baru.</p>
        <button id="adjustment-post" class="btn btn-success" type="button">Posting draft</button>
    <?php elseif ($transaction->status === 'diposting'): ?>
        <h4>Pembatalan</h4>
        <p>Pembatalan hanya dapat dilakukan jika belum ada mutasi stok berikutnya untuk produk ini.</p>
        <form id="adjustment-cancel" class="form-inline">
            <label for="cancel-date">Tanggal</label>
            <input id="cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            <label for="cancel-reason">Alasan</label>
            <input id="cancel-reason" class="form-control" maxlength="255" required>
            <button type="submit" class="btn btn-danger">Batalkan</button>
        </form>
    <?php endif; ?>
<?php else: ?>
    <form id="adjustment-form" enctype="multipart/form-data">
        <div class="row">
            <div class="form-group col-sm-3"><label for="adjustment-date">Tanggal</label>
                <input class="form-control" id="adjustment-date" type="date" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group col-sm-5"><label for="adjustment-product">Produk</label>
                <select id="adjustment-product" class="form-control" required>
                    <option value="">-- Pilih produk --</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product->id_produk ?>"><?= html_escape($product->kode_produk . ' - ' . $product->nama_produk) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="form-group col-sm-4"><label for="adjustment-kind">Jenis</label>
                <select id="adjustment-kind" class="form-control">
                    <option value="opname">Stock opname (jumlah fisik)</option>
                    <option value="masuk">Penambahan stok</option>
                    <option value="keluar">Pengurangan stok</option>
                </select></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4"><label for="adjustment-quantity">Jumlah</label>
                <input id="adjustment-quantity" class="form-control" type="number" min="0" max="99999999999999.9999" step="0.0001" required></div>
            <div class="form-group col-sm-4"><label for="adjustment-value">Total nilai perolehan</label>
                <input id="adjustment-value" class="form-control" type="text" inputmode="decimal"></div>
            <div class="form-group col-sm-4"><label for="adjustment-account">Akun selisih pendapatan/beban</label>
                <select id="adjustment-account" class="form-control" required>
                    <option value="">-- Pilih akun --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= (int) $account->id_akun ?>" <?= !$account->selectable ? 'disabled' : '' ?>><?= html_escape(str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>
        <p class="text-muted">Nilai perolehan hanya digunakan untuk penambahan ketika saldo tidak memiliki harga pokok rata-rata. Jika tersedia, sistem menggunakan harga pokok rata-rata saat posting.</p>
        <div class="form-group"><label for="adjustment-description">Keterangan</label>
            <input id="adjustment-description" class="form-control" maxlength="255" required></div>
        <div class="form-group"><label for="adjustment-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
            <input id="adjustment-file" type="file" accept="application/pdf,image/jpeg,image/png"></div>
        <button class="btn btn-primary" type="submit">Simpan draft</button>
    </form>
<?php endif; ?>
<h4>Riwayat penyesuaian</h4>
<div class="table-responsive"><table class="table table-bordered table-striped">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Produk</th><th>Jenis</th><th>Jumlah input</th><th>Nilai</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transactions as $row): ?>
        <tr><td><?= html_escape($row->tanggal) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
            <td><?= html_escape($row->nama_produk) ?></td><td><?= html_escape($row->jenis) ?></td>
            <td><?= html_escape($row->jumlah) ?></td><td><?= html_escape($row->total) ?></td>
            <td><?= html_escape($row->status) ?></td><td><a href="<?= site_url('penyesuaian_persediaan?id=' . (int) $row->id_transaksi) ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="8">Belum ada penyesuaian.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<script>
$(function () {
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;
    var currentId = <?= $transaction ? (int) $transaction->id_transaksi : 'null' ?>;
    if ($('#adjustment-value').length) MoneyInput.init($('#adjustment-value')[0], '');
    if ($('#adjustment-form').length) {
        var query = new URLSearchParams(location.search);
        if (query.get('produk') && /^\d+$/.test(query.get('produk'))) $('#adjustment-product').val(query.get('produk'));
        if (query.get('jumlah') && /^\d+(\.\d+)?$/.test(query.get('jumlah'))) { $('#adjustment-kind').val('opname'); $('#adjustment-quantity').val(query.get('jumlah')); }
        if (query.get('ket')) $('#adjustment-description').val(query.get('ket').substring(0, 255));
    }
    function request(url, data) {
        if (csrf) data.append(csrf.name, csrf.value);
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: data, processData: false, contentType: false})
            .done(function (result) {
                location.href = <?= json_encode(site_url('penyesuaian_persediaan?id=')) ?> + encodeURIComponent(result.result.id_transaksi);
            })
            .fail(function (xhr) {
                $('#adjustment-message').empty().append($('<div class="alert alert-danger">').text(
                    xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'));
                $('button').prop('disabled', false);
            });
    }
    $('#adjustment-form').on('submit', function (event) {
        event.preventDefault();
        var data = new FormData();
        data.append('tanggal', $('#adjustment-date').val());
        data.append('produk', $('#adjustment-product').val());
        data.append('jenis', $('#adjustment-kind').val());
        data.append('jumlah', $('#adjustment-quantity').val());
        data.append('nilai', MoneyInput.value($('#adjustment-value')[0]));
        data.append('akun', $('#adjustment-account').val());
        data.append('deskripsi', $('#adjustment-description').val());
        if ($('#adjustment-file')[0].files[0]) data.append('lampiran', $('#adjustment-file')[0].files[0]);
        request(<?= json_encode(site_url('penyesuaian_persediaan/simpan')) ?>, data);
    });
    $('#adjustment-post').on('click', function () {
        if (!confirm('Posting penyesuaian dan buat jurnal?')) return;
        var data = new FormData();
        data.append('id', currentId);
        request(<?= json_encode(site_url('penyesuaian_persediaan/posting')) ?>, data);
    });
    $('#adjustment-cancel').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan penyesuaian dan buat jurnal pembalik?')) return;
        var data = new FormData();
        data.append('id', currentId);
        data.append('tanggal', $('#cancel-date').val());
        data.append('alasan', $('#cancel-reason').val());
        request(<?= json_encode(site_url('penyesuaian_persediaan/batalkan')) ?>, data);
    });
});
</script>
