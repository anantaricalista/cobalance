<h3>Retur Pembelian dan Penjualan</h3>
<p>Setiap retur mencakup satu rincian barang atau jasa. Retur tunai menggunakan akun kas/bank transaksi asal;
    retur kredit mengurangi sisa tagihan dan tidak boleh melebihi sisa tagihan.</p>
<p><a href="<?= site_url('persediaan') ?>">Saldo dan riwayat stok</a> |
    <a href="<?= site_url('retur_persediaan') ?>">Retur baru</a></p>
<div id="return-message" role="alert"></div>
<?php if ($transaction): ?>
    <h4><?= html_escape($transaction->nomor_transaksi) ?> &mdash; <?= html_escape($transaction->status) ?></h4>
    <p>Asal: <?= html_escape($transaction->nomor_asal) ?> |
        Jenis: <?= html_escape($transaction->jenis_transaksi) ?> |
        Produk: <?= html_escape($transaction->kode_produk . ' - ' . $transaction->nama_produk) ?> |
        Jumlah: <?= html_escape($transaction->jumlah . ' ' . $transaction->satuan) ?></p>
    <p>Sebelum pajak: Rp <?= html_escape(format_nominal($transaction->nilai_sebelum_pajak)) ?> |
        Pajak: Rp <?= html_escape(format_nominal($transaction->nilai_pajak)) ?> |
        Total: Rp <?= html_escape(format_nominal($transaction->subtotal)) ?></p>
    <?php if ($transaction->lampiran_nama): ?>
        <p><a href="<?= site_url('lampiran/unduh/' . (int) $transaction->id_transaksi) ?>"><?= html_escape($transaction->lampiran_nama) ?></a></p>
    <?php endif; ?>
    <?php if ($transaction->status === 'draft'): ?>
        <p>Nilai akhir dihitung saat posting. Draft tidak dapat diubah; buat draft baru jika ada kesalahan.</p>
        <?php if ($this->can_post_transactions): ?>
            <button id="return-post" class="btn btn-success" type="button">Posting retur</button>
        <?php else: ?>
            <p class="text-info" role="status">Draft tersimpan dan menunggu posting oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
    <?php elseif ($transaction->status === 'diposting'): ?>
        <?php if ($this->can_post_transactions): ?>
            <h4>Pembatalan</h4>
            <p>Retur barang hanya dapat dibatalkan bila tidak ada mutasi stok berikutnya untuk produk tersebut.</p>
            <form id="return-cancel" class="form-inline">
                <label for="return-cancel-date">Tanggal</label>
                <input id="return-cancel-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                <label for="return-cancel-reason">Alasan</label>
                <input id="return-cancel-reason" class="form-control" maxlength="255" required>
                <button type="submit" class="btn btn-danger">Batalkan</button>
            </form>
        <?php else: ?>
            <p class="text-muted">Pembatalan transaksi hanya dapat dilakukan oleh Akuntansi atau Admin Perusahaan.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <form id="return-form" enctype="multipart/form-data">
        <div class="row">
            <div class="form-group col-sm-3"><label for="return-date">Tanggal</label>
                <input id="return-date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group col-sm-9"><label for="return-source">Rincian transaksi asal</label>
                <select id="return-source" class="form-control" required>
                    <option value="">-- Pilih rincian --</option>
                    <?php foreach ($sources as $source): ?>
                        <option value="<?= (int) $source->id_detail_transaksi ?>" <?= !$source->tipe_produk ? 'disabled' : '' ?>><?= html_escape(
                            $source->tanggal . ' | ' . $source->nomor_transaksi . ' | ' . $source->jenis_transaksi .
                            ' (' . $source->cara_pembayaran . ') | ' . $source->kode_produk . ' - ' . $source->deskripsi .
                            ' | jumlah asal ' . $source->jumlah . ' ' . $source->satuan .
                            (!$source->tipe_produk ? ' | riwayat akun tidak tersedia' : '')) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>
        <div class="row">
            <div class="form-group col-sm-4"><label for="return-quantity">Jumlah retur</label>
                <input id="return-quantity" class="form-control" type="number" min="0.0001" step="0.0001" required></div>
            <div class="form-group col-sm-8"><label for="return-account">Akun laba/rugi selisih persediaan</label>
                <select id="return-account" class="form-control">
                    <option value="">-- Pilih jika retur pembelian barang --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?= (int) $account->id_akun ?>" <?= !$account->selectable ? 'disabled' : '' ?>><?= html_escape(str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>
        <p class="text-muted">Akun selisih diperlukan apabila harga pokok rata-rata saat retur pembelian barang berbeda dari nilai faktur.</p>
        <div class="form-group"><label for="return-description">Keterangan</label>
            <input id="return-description" class="form-control" maxlength="255" required></div>
        <div class="form-group"><label for="return-file">Lampiran (PDF/JPG/PNG, maks. 5 MB)</label>
            <input id="return-file" type="file" accept="application/pdf,image/jpeg,image/png"></div>
        <button class="btn btn-primary" type="submit">Simpan draft</button>
    </form>
<?php endif; ?>
<h4>Riwayat retur</h4>
<div class="table-responsive"><table class="table table-bordered table-striped">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis</th><th>Transaksi asal</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transactions as $row): ?>
        <tr><td><?= html_escape($row->tanggal) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
            <td><?= html_escape($row->jenis_transaksi) ?></td><td><?= html_escape($row->nomor_asal) ?></td>
            <td><?= html_escape(format_nominal($row->total)) ?></td><td><?= html_escape($row->status) ?></td>
            <td><a href="<?= site_url('retur_persediaan?id=' . (int) $row->id_transaksi) ?>">Lihat</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$transactions): ?><tr><td colspan="7">Belum ada retur.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<script>
$(function () {
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;
    var currentId = <?= $transaction ? (int) $transaction->id_transaksi : 'null' ?>;
    function request(url, data) {
        if (csrf) data.append(csrf.name, csrf.value);
        $('button').prop('disabled', true);
        $.ajax({url: url, method: 'POST', data: data, processData: false, contentType: false})
            .done(function (result) {
                location.href = <?= json_encode(site_url('retur_persediaan?id=')) ?> + encodeURIComponent(result.result.id_transaksi);
            }).fail(function (xhr) {
                $('#return-message').empty().append($('<div class="alert alert-danger">').text(
                    xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'));
                $('button').prop('disabled', false);
            });
    }
    $('#return-form').on('submit', function (event) {
        event.preventDefault();
        var data = new FormData();
        data.append('tanggal', $('#return-date').val());
        data.append('id_detail_asal', $('#return-source').val());
        data.append('jumlah', $('#return-quantity').val());
        data.append('id_akun_selisih', $('#return-account').val());
        data.append('deskripsi', $('#return-description').val());
        if ($('#return-file')[0].files[0]) data.append('lampiran', $('#return-file')[0].files[0]);
        request(<?= json_encode(site_url('retur_persediaan/simpan')) ?>, data);
    });
    $('#return-post').on('click', function () {
        if (!confirm('Posting retur dan buat jurnal?')) return;
        var data = new FormData();
        data.append('id', currentId);
        request(<?= json_encode(site_url('retur_persediaan/posting')) ?>, data);
    });
    $('#return-cancel').on('submit', function (event) {
        event.preventDefault();
        if (!confirm('Batalkan retur dan buat jurnal pembalik?')) return;
        var data = new FormData();
        data.append('id', currentId);
        data.append('tanggal', $('#return-cancel-date').val());
        data.append('alasan', $('#return-cancel-reason').val());
        request(<?= json_encode(site_url('retur_persediaan/batalkan')) ?>, data);
    });
});
</script>
