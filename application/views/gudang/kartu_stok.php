<h3>Kartu Stok</h3>
<p><strong><?= html_escape($produk->kode_produk . ' - ' . $produk->nama_produk) ?></strong>
    | Stok saat ini: <?= html_escape($produk->jumlah) ?> <?= html_escape($produk->satuan) ?></p>
<p><a href="<?= site_url('gudang/stok') ?>">&laquo; Kembali ke stok</a></p>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis transaksi</th><th>Status</th><th>Mutasi</th><th>Jumlah</th><th>Saldo</th></tr></thead>
        <tbody>
        <?php foreach ($kartu['mutasi'] as $row): ?>
            <tr>
                <td><?= html_escape(format_tanggal_indonesia($row->tanggal)) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
                <td><?= html_escape($row->jenis_transaksi) ?></td><td><?= html_escape($row->status) ?></td>
                <td><?= html_escape($row->jenis_mutasi) ?></td><td><?= html_escape($row->jumlah) ?></td>
                <td><?= html_escape($row->saldo_jumlah) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$kartu['mutasi']): ?><tr><td colspan="7">Belum ada mutasi.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php $base = site_url('gudang/stok/kartu/' . (int) $produk->id_produk); ?>
<?php if ($sebelum !== null): ?><a class="btn btn-default" href="<?= $base ?>">Mutasi terbaru</a><?php endif; ?>
<?php if ($kartu['berikutnya'] !== null): ?><a class="btn btn-default" href="<?= $base . '?sebelum=' . (int) $kartu['berikutnya'] ?>">Mutasi lebih lama</a><?php endif; ?>
