<h3>Opname &amp; Penyesuaian Persediaan</h3>
<p class="text-muted" role="note">Nominal: pemisah ribuan ditambahkan otomatis; gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>.</p>
<p>Periksa usulan opname, buat draft penyesuaian, lalu posting untuk memperbarui stok dan jurnal. Jumlah memakai koma untuk desimal dan tidak menampilkan nol yang tidak diperlukan.</p>
<p><a href="<?= site_url('persediaan') ?>">Saldo dan riwayat stok</a></p>
<?php if ($this->session->flashdata('sukses')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('sukses')) ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
<div id="adjustment-message" role="alert"></div>
<section id="usulan-opname">
    <h4>Usulan Opname Gudang</h4>
    <p class="text-muted">Pilih <strong>Tinjau &amp; sesuaikan</strong> untuk memuat jumlah fisik ke form. Usulan selesai setelah penyesuaian diposting.</p>
    <div class="table-responsive"><table class="table table-bordered table-striped">
        <thead><tr><th>Waktu</th><th>Produk</th><th>Sistem</th><th>Fisik</th><th>Selisih</th><th>Stok sekarang</th><th>Catatan</th><th>Oleh</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($proposals as $proposal): ?>
            <tr>
                <td><?= html_escape(format_tanggal_indonesia($proposal->dibuat_pada)) ?></td>
                <td><?= html_escape($proposal->kode_produk . ' - ' . $proposal->nama_produk) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($proposal->jumlah_sistem)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($proposal->jumlah_fisik)) ?> <?= html_escape($proposal->satuan) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($proposal->selisih_jumlah)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($proposal->jumlah_sekarang)) ?> <?= html_escape($proposal->satuan) ?></td>
                <td><?= html_escape($proposal->catatan) ?></td>
                <td><?= html_escape($proposal->nama_lengkap ?: 'Pengguna tidak diketahui') ?></td>
                <td><?php
                    $proposalStatuses = array('menunggu' => array('Menunggu', 'warning'), 'diproses' => array('Selesai', 'success'), 'ditolak' => array('Ditolak', 'default'));
                    $proposalStatus = isset($proposalStatuses[$proposal->status]) ? $proposalStatuses[$proposal->status] : array($proposal->status, 'default');
                ?><span class="label label-<?= $proposalStatus[1] ?>"><?= html_escape($proposalStatus[0]) ?></span></td>
                <td><?php if ($proposal->status === 'menunggu' && !$transaction): ?>
                    <button class="btn btn-xs btn-primary review-opname" type="button"
                        data-id="<?= (int) $proposal->id_usulan_opname ?>" data-product="<?= (int) $proposal->id_produk ?>"
                        data-quantity="<?= html_escape($proposal->jumlah_fisik) ?>" data-note="<?= html_escape($proposal->catatan) ?>"
                        data-product-name="<?= html_escape($proposal->kode_produk . ' - ' . $proposal->nama_produk) ?>">Tinjau &amp; sesuaikan</button>
                    <form method="post" action="<?= site_url('usulan_opname/ubah') ?>" style="display:inline">
                        <input type="hidden" name="usulan_form_token" value="<?= html_escape($proposal_form_token) ?>">
                        <?php if ($this->config->item('csrf_protection')): ?>
                            <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="id" value="<?= (int) $proposal->id_usulan_opname ?>">
                        <input type="hidden" name="status" value="ditolak">
                        <button type="submit" class="btn btn-xs btn-default">Tolak</button>
                    </form>
                <?php elseif ($proposal->status === 'menunggu'): ?>
                    <a class="btn btn-xs btn-primary" href="<?= site_url('penyesuaian_persediaan#usulan-opname') ?>">Kembali ke daftar</a>
                <?php elseif ($proposal->status === 'diproses'): ?>
                    <span class="text-success">Penyesuaian sudah diposting</span>
                <?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$proposals): ?><tr><td colspan="10">Belum ada usulan opname.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</section>
<?php if ($transaction): ?>
    <p><a class="btn btn-default btn-sm" href="<?= site_url('penyesuaian_persediaan') ?>">&larr; Kembali ke Opname &amp; Penyesuaian</a></p>
    <h4><?= html_escape($transaction->nomor_transaksi) ?> &mdash; <?= html_escape($transaction->status) ?></h4>
    <p>Produk: <?= html_escape($transaction->kode_produk . ' - ' . $transaction->nama_produk) ?> |
        Jenis: <?= html_escape($transaction->jenis) ?> | Jumlah input: <?= html_escape(format_jumlah_indonesia($transaction->jumlah)) ?> |
        Nilai tercatat: Rp <?= html_escape(format_nominal($transaction->subtotal)) ?></p>
    <p>Akun selisih: <?= html_escape($transaction->kode_akun . ' - ' . $transaction->nama_akun) ?> |
        Keterangan: <?= html_escape($transaction->deskripsi) ?></p>
    <?php if ($transaction->lampiran_nama): ?>
        <p><a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
    <?php if ($transaction->status === 'draft'): ?>
        <p>Draft tidak dapat diubah. Jika data salah, buat draft baru.</p>
        <?php if ($this->can_post_transactions): ?>
            <button id="adjustment-post" class="btn btn-success" type="button">Posting draft</button>
        <?php else: ?>
            <p class="text-info" role="status">Draft tersimpan dan menunggu posting oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
    <?php elseif ($transaction->status === 'diposting'): ?>
        <?php if ($this->can_post_transactions): ?>
            <h4>Pembatalan</h4>
            <p>Pembatalan hanya dapat dilakukan jika belum ada mutasi stok berikutnya untuk produk ini.</p>
            <form id="adjustment-cancel" class="form-inline">
                <label for="cancel-date">Tanggal</label>
                <input id="cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                <label for="cancel-reason">Alasan</label>
                <input id="cancel-reason" class="form-control" maxlength="255" required>
                <button type="submit" class="btn btn-danger">Batalkan</button>
            </form>
        <?php else: ?>
            <p class="text-muted">Pembatalan transaksi hanya dapat dilakukan oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
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
            <div class="form-group col-sm-4"><label for="adjustment-quantity">Jumlah fisik / jumlah penyesuaian</label>
                <input id="adjustment-quantity" class="form-control" type="text" inputmode="decimal"
                    maxlength="19" pattern="(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,4})?"
                    placeholder="Contoh: 1.250,5" title="Gunakan koma untuk desimal, maksimal empat angka."
                    aria-describedby="adjustment-quantity-help" required>
                <span id="adjustment-quantity-help" class="help-block">Gunakan koma untuk desimal, maksimal empat angka. Contoh: 2 atau 1.250,5.</span></div>
            <div class="form-group col-sm-4"><label for="adjustment-value">Total nilai perolehan</label>
                <input id="adjustment-value" class="form-control" type="text" inputmode="decimal"></div>
            <div class="form-group col-sm-4"><label for="adjustment-account">Akun selisih pendapatan/beban</label>
                <select id="adjustment-account" class="form-control account-search" required>
                    <option value="">-- Pilih akun --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= (int) $account->id_akun ?>" <?= !$account->selectable ? 'disabled' : '' ?>><?= html_escape(str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>
        <p class="text-muted">Nilai perolehan hanya digunakan untuk penambahan ketika saldo tidak memiliki harga pokok rata-rata. Jika tersedia, sistem menggunakan harga pokok rata-rata saat posting.</p>
        <div id="opname-review-note" class="alert alert-info" style="display:none"></div>
        <button id="clear-opname-review" class="btn btn-link btn-sm" type="button" style="display:none">Batalkan pilihan usulan</button>
        <input id="adjustment-proposal" type="hidden" value="">
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
        <tr><td><?= html_escape(format_tanggal_indonesia($row->tanggal)) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
            <td><?= html_escape($row->nama_produk) ?></td><td><?= html_escape($row->jenis) ?></td>
            <td><?= html_escape(format_jumlah_indonesia($row->jumlah)) ?></td><td><?= html_escape(format_nominal($row->total)) ?></td>
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
    function formatQuantity(value) {
        var parts = String(value).split('.');
        var whole = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        var fraction = (parts[1] || '').replace(/0+$/, '');
        return whole + (fraction ? ',' + fraction : '');
    }
    function parseQuantity(value) {
        value = $.trim(value);
        var pattern = /^(?:\d{1,14}|\d{1,3}(?:\.\d{3}){1,4})(?:,\d{1,4})?$/;
        if (!pattern.test(value)) return null;
        return value.replace(/\./g, '').replace(',', '.');
    }
    if ($('#adjustment-form').length) {
        var query = new URLSearchParams(location.search);
        if (query.get('produk') && /^\d+$/.test(query.get('produk'))) $('#adjustment-product').val(query.get('produk'));
        if (query.get('jumlah') && /^\d+(\.\d+)?$/.test(query.get('jumlah'))) {
            $('#adjustment-kind').val('opname');
            $('#adjustment-quantity').val(formatQuantity(query.get('jumlah')));
        }
        if (query.get('ket')) $('#adjustment-description').val(query.get('ket').substring(0, 255));
    }
    function request(url, data, destination) {
        if (csrf) data.append(csrf.name, csrf.value);
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: data, processData: false, contentType: false})
            .done(function (result) {
                location.href = destination || (<?= json_encode(site_url('penyesuaian_persediaan?id=')) ?> + encodeURIComponent(result.result.id_transaksi));
            })
            .fail(function (xhr) {
                $('#adjustment-message').empty().append($('<div class="alert alert-danger">').text(
                    xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'));
                $('button').prop('disabled', false);
            });
    }
    $('#adjustment-form').on('submit', function (event) {
        event.preventDefault();
        var quantity = parseQuantity($('#adjustment-quantity').val());
        if (quantity === null) {
            $('#adjustment-message').empty().append($('<div class="alert alert-danger">').text(
                'Jumlah tidak valid. Gunakan koma untuk desimal, maksimal empat angka.'));
            return;
        }
        var data = new FormData();
        data.append('tanggal', $('#adjustment-date').val());
        data.append('produk', $('#adjustment-product').val());
        data.append('jenis', $('#adjustment-kind').val());
        data.append('jumlah', quantity);
        data.append('nilai', MoneyInput.value($('#adjustment-value')[0]));
        data.append('akun', $('#adjustment-account').val());
        data.append('deskripsi', $('#adjustment-description').val());
        if ($('#adjustment-proposal').val()) data.append('usulan', $('#adjustment-proposal').val());
        if ($('#adjustment-file')[0].files[0]) data.append('lampiran', $('#adjustment-file')[0].files[0]);
        request(<?= json_encode(site_url('penyesuaian_persediaan/simpan')) ?>, data);
    });
    $('#adjustment-post').on('click', function () {
        if (!confirm('Posting penyesuaian dan buat jurnal?')) return;
        var data = new FormData();
        data.append('id', currentId);
        request(<?= json_encode(site_url('penyesuaian_persediaan/posting')) ?>, data,
            <?= json_encode(site_url('penyesuaian_persediaan')) ?>);
    });
    $('#adjustment-cancel').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan penyesuaian dan buat jurnal pembalik?')) return;
        var data = new FormData();
        data.append('id', currentId);
        data.append('tanggal', $('#cancel-date').val());
        data.append('alasan', $('#cancel-reason').val());
        request(<?= json_encode(site_url('penyesuaian_persediaan/batalkan')) ?>, data,
            <?= json_encode(site_url('penyesuaian_persediaan')) ?>);
    });
    $('.review-opname').on('click', function () {
        var button = $(this);
        $('#adjustment-proposal').val(button.data('id'));
        $('#adjustment-product').val(button.data('product')).prop('disabled', true);
        $('#adjustment-kind').val('opname').prop('disabled', true);
        $('#adjustment-quantity').val(formatQuantity(button.attr('data-quantity')));
        $('#adjustment-description').val(String(button.data('note')).substring(0, 255));
        $('#opname-review-note').text('Meninjau opname #' + button.data('id') + ' untuk ' +
            button.data('product-name') + '. Periksa kembali jumlah fisik dan selisih sebelum menyimpan draft.').show();
        $('#clear-opname-review').show();
        $('#adjustment-message').empty();
        $('html, body').animate({scrollTop: $('#adjustment-form').offset().top - 20}, 250);
        $('#adjustment-quantity').trigger('focus');
    });
    $('#clear-opname-review').on('click', function () {
        $('#adjustment-proposal').val('');
        $('#adjustment-product, #adjustment-kind').prop('disabled', false);
        $('#opname-review-note').hide().empty();
        $('#clear-opname-review').hide();
    });
});
</script>
