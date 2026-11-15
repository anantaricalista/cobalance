<h2>Riwayat Kasir</h2>
<p>Transaksi yang diselesaikan melalui kasir, termasuk yang telah dibatalkan.</p>
<p><a class="btn btn-success" href="<?= site_url('pos') ?>">Transaksi baru</a></p>
<div class="table-responsive"><table class="table table-striped">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Pelanggan</th><th>Pembayaran</th><th>Status</th><th class="text-right">Total</th><th>Struk</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr><td><?= html_escape(format_tanggal_indonesia($row->tanggal)) ?></td>
            <td><?= html_escape($row->nomor_transaksi) ?></td><td><?= html_escape($row->nama_pelanggan) ?></td>
            <td><?= html_escape(strtoupper($row->metode)) ?></td><td><?= html_escape($row->status) ?></td>
            <td class="text-right">Rp <?= html_escape(format_nominal($row->total)) ?></td>
            <td><a href="<?= site_url('pos/struk/' . (int) $row->id_transaksi) ?>">Lihat struk</a></td></tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7">Belum ada transaksi kasir.</td></tr><?php endif; ?>
    </tbody>
</table></div>
<p>
    <?php if ($page > 1): ?><a class="btn btn-default" href="<?= site_url('pos/riwayat?halaman=' . ($page - 1)) ?>">Sebelumnya</a><?php endif; ?>
    Halaman <?= (int) $page ?>
    <?php if ($more && $page < 999999): ?><a class="btn btn-default" href="<?= site_url('pos/riwayat?halaman=' . ($page + 1)) ?>">Berikutnya</a><?php endif; ?>
</p>
