<h3>Beranda Purchasing</h3>
<p>Halo, <?= html_escape($nama) ?>.</p>
<div class="row">
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['draft'] ?></h4>Pembelian draft
    </div></div></div>
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['bulan_ini'] ?></h4>Pembelian diposting bulan ini
    </div></div></div>
    <div class="col-sm-4"><div class="panel panel-default"><div class="panel-body">
        <h4><?= (int) $ringkasan['pemasok'] ?></h4>Pemasok aktif
    </div></div></div>
</div>
<p>
    <a class="btn btn-primary" href="<?= site_url('pembelian') ?>">Pembelian</a>
    <a class="btn btn-default" href="<?= site_url('pembayaran_utang') ?>">Pembayaran Utang</a>
</p>
