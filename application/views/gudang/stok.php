<h3>Stok Barang</h3>
<form class="form-inline" method="get" action="<?= site_url('gudang/stok') ?>" style="margin-bottom:12px">
    <input type="text" name="cari" class="form-control" maxlength="100" placeholder="Cari kode / nama" value="<?= html_escape($cari) ?>">
    <button type="submit" class="btn btn-default">Cari</button>
</form>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Kode</th><th>Produk</th><th>Satuan</th><th>Stok</th><th>Status</th><th>Kartu stok</th></tr></thead>
        <tbody>
        <?php foreach ($stok as $row): ?>
            <tr>
                <td><?= html_escape($row->kode_produk) ?></td><td><?= html_escape($row->nama_produk) ?></td>
                <td><?= html_escape($row->satuan) ?></td><td><?= html_escape($row->jumlah) ?></td>
                <td><?= $row->aktif ? 'Aktif' : 'Tidak aktif' ?></td>
                <td><a href="<?= site_url('gudang/stok/kartu/' . (int) $row->id_produk) ?>">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$stok): ?><tr><td colspan="6">Tidak ada produk barang.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
