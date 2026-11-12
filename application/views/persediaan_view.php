<h3>Persediaan</h3>
<p><a href="<?= site_url('penyesuaian_persediaan') ?>">Penyesuaian dan stock opname</a> |
    <a href="<?= site_url('retur_persediaan') ?>">Retur pembelian dan penjualan</a></p>
<h4>Saldo stok</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Kode</th><th>Produk</th><th>Satuan</th><th>Jumlah</th><th>Nilai</th><th>Harga pokok rata-rata</th><th>Status</th><th>Riwayat</th></tr></thead>
        <tbody>
        <?php foreach ($saldo as $row): ?>
            <tr>
                <td><?= html_escape($row->kode_produk) ?></td><td><?= html_escape($row->nama_produk) ?></td>
                <td><?= html_escape($row->satuan) ?></td><td><?= html_escape($row->jumlah) ?></td>
                <td><?= html_escape(format_nominal($row->nilai)) ?></td><td><?= html_escape(format_nominal($row->harga_pokok_rata_rata, 6)) ?></td>
                <td><?= $row->aktif ? 'Aktif' : 'Tidak aktif' ?></td>
                <td><a href="<?= site_url('persediaan?produk=' . (int) $row->id_produk) ?>">Lihat mutasi</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$saldo): ?><tr><td colspan="8">Belum ada produk barang.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($riwayat): ?>
    <h4>Mutasi <?= html_escape($riwayat['produk']->kode_produk . ' - ' . $riwayat['produk']->nama_produk) ?></h4>
    <p>Mutasi diurutkan berdasarkan urutan pencatatan terbaru, bukan tanggal transaksi.</p>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead><tr><th>Tanggal transaksi</th><th>Nomor</th><th>Jenis transaksi</th><th>Status</th><th>Mutasi</th><th>Jumlah</th><th>Nilai</th><th>Saldo jumlah</th><th>Saldo nilai</th><th>Harga pokok rata-rata</th></tr></thead>
            <tbody>
            <?php foreach ($riwayat['mutasi'] as $row): ?>
                <tr>
                    <td><?= html_escape($row->tanggal) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
                    <td><?= html_escape($row->jenis_transaksi) ?></td><td><?= html_escape($row->status) ?></td>
                    <td><?= html_escape($row->jenis_mutasi) ?></td><td><?= html_escape($row->jumlah) ?></td>
                    <td><?= html_escape(format_nominal($row->nilai)) ?></td><td><?= html_escape($row->saldo_jumlah) ?></td>
                    <td><?= html_escape(format_nominal($row->saldo_nilai)) ?></td><td><?= html_escape(format_nominal($row->harga_pokok_rata_rata, 6)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$riwayat['mutasi']): ?><tr><td colspan="10">Belum ada mutasi.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($riwayat['sebelum'] !== null): ?>
        <a class="btn btn-default" href="<?= site_url('persediaan?produk=' . (int) $riwayat['produk']->id_produk) ?>">Mutasi terbaru</a>
    <?php endif; ?>
    <?php if ($riwayat['berikutnya'] !== null): ?>
        <a class="btn btn-default" href="<?= site_url('persediaan?produk=' . (int) $riwayat['produk']->id_produk . '&amp;sebelum=' . (int) $riwayat['berikutnya']) ?>">Mutasi lebih lama</a>
    <?php endif; ?>
<?php endif; ?>
