<h3>Produk</h3>
<form class="form-inline" method="get" action="<?= site_url('purchasing/produk') ?>" style="margin-bottom:12px">
    <input type="text" name="cari" class="form-control" maxlength="100" placeholder="Cari kode / nama" value="<?= html_escape($cari) ?>">
    <button type="submit" class="btn btn-default">Cari</button>
</form>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th>Satuan</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= html_escape($r->kode_produk) ?></td><td><?= html_escape($r->nama_produk) ?></td>
                <td><?= html_escape($r->tipe_produk) ?></td><td><?= html_escape($r->satuan) ?></td>
                <td><?= $r->aktif ? 'Aktif' : 'Tidak aktif' ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5">Tidak ada produk.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
