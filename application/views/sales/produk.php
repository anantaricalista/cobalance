<h3>Katalog Produk dan Jasa</h3>
<p class="text-muted">Katalog hanya dapat dilihat. Stok barang menunjukkan saldo saat ini, bukan jumlah yang sudah dicadangkan untuk draft.</p>
<form class="form-inline" method="get" action="<?= site_url('produk') ?>" style="margin-bottom: 15px">
    <label for="product-search">Cari produk</label>
    <input id="product-search" name="cari" class="form-control" type="text" maxlength="100" placeholder="Kode atau nama" value="<?= html_escape($search) ?>">
    <button class="btn btn-default" type="submit">Cari</button>
</form>
<div class="table-responsive"><table class="table table-striped table-bordered">
    <thead><tr><th>Gambar</th><th>Kode</th><th>Produk</th><th>Jenis</th><th>Satuan</th><th>Harga jual</th><th>Stok saat ini</th><th>Status</th></tr></thead>
    <tbody><?php foreach ($products as $product): ?>
        <tr><td><?php if ($product->memiliki_gambar): ?><img src="<?= site_url('produk/gambar/' . (int) $product->id_produk) ?>" alt="" loading="lazy" style="max-width:60px;max-height:60px"><?php else: ?>—<?php endif; ?></td>
            <td><?= html_escape($product->kode_produk) ?></td><td><?= html_escape($product->nama_produk) ?></td>
            <td><?= $product->tipe_produk === 'barang' ? 'Barang' : 'Jasa' ?></td>
            <td><?= html_escape($product->satuan) ?></td><td>Rp <?= html_escape(format_nominal($product->harga_jual)) ?></td>
            <td><?= $product->tipe_produk === 'barang' ? html_escape(format_jumlah_indonesia($product->jumlah)) : '—' ?></td>
            <td><?= $product->aktif ? 'Aktif' : 'Tidak aktif' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="8">Tidak ada produk yang ditemukan.</td></tr><?php endif; ?></tbody>
</table></div>
<p>
    <?php if ($page > 1): ?><a class="btn btn-default" href="<?= html_escape(site_url('produk?' . http_build_query(array('cari' => $search, 'halaman' => $page - 1)))) ?>">Sebelumnya</a><?php endif; ?>
    <span>Halaman <?= (int) $page ?></span>
    <?php if ($more && $page < 999999): ?><a class="btn btn-default" href="<?= html_escape(site_url('produk?' . http_build_query(array('cari' => $search, 'halaman' => $page + 1)))) ?>">Berikutnya</a><?php endif; ?>
</p>
