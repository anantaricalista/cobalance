<h3>Beranda Gudang</h3>
<p>Halo, <?= html_escape($nama) ?>.</p>
<div class="row">
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['total_produk'] ?></h4>Produk barang aktif
    </div></div></div>
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['stok_habis'] ?></h4>Produk stok kosong
    </div></div></div>
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['usulan_menunggu'] ?></h4>Usulan opname menunggu
    </div></div></div>
</div>
<p>
    <a class="btn btn-primary" href="<?= site_url('gudang/stok') ?>">Stok Barang</a>
    <a class="btn btn-default" href="<?= site_url('gudang/opname') ?>">Stock Opname</a>
</p>
