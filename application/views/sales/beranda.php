<style>
    .sales-home { color: #263248; }
    .sales-home h2 { color: #183153; font-weight: 700; margin-bottom: 6px; }
    .sales-home .intro, .sales-home .note { color: #687a91; }
    .sales-home .intro { margin-bottom: 22px; }
    .sales-home .card { display: block; min-height: 135px; margin-bottom: 18px; padding: 18px; border: 1px solid #e1e8f0; border-left: 4px solid #4a93a5; border-radius: 9px; background: #fff; color: inherit; }
    .sales-home .card:hover, .sales-home .card:focus { text-decoration: none; box-shadow: 0 5px 16px rgba(27,49,79,.12); }
    .sales-home .card.overdue { border-left-color: #d76666; }
    .sales-home .card.customer { border-left-color: #35a77a; }
    .sales-home .value { display: block; font-size: 22px; font-weight: 700; color: #183153; overflow-wrap: anywhere; margin: 8px 0; }
    .sales-home .panel { border-radius: 9px; }
    .sales-home .shortcuts { display: flex; flex-wrap: wrap; gap: 8px; }
    @media (max-width: 767px) { .sales-home .card { min-height: 0; } }
</style>
<div class="sales-home">
    <h2>Beranda Penjualan</h2>
    <p class="intro">Halo, <?= html_escape($nama) ?>. Pantau penjualan dan tagihan pelanggan perusahaan.</p>
    <div class="row">
        <div class="col-sm-6 col-lg-3"><a class="card" href="<?= site_url('penerimaan_piutang') ?>">
            Piutang penjualan belum lunas <span class="value">Rp <?= html_escape(format_nominal($ringkasan['piutang']->total)) ?></span>
            <?= (int) $ringkasan['piutang']->jumlah ?> tagihan terbuka</a></div>
        <div class="col-sm-6 col-lg-3"><a class="card overdue" href="<?= site_url('penerimaan_piutang') ?>">
            Piutang lewat jatuh tempo <span class="value">Rp <?= html_escape(format_nominal($ringkasan['piutang']->terlambat)) ?></span>
            <?= (int) $ringkasan['piutang']->jumlah_terlambat ?> tagihan lewat jatuh tempo</a></div>
        <div class="col-sm-6 col-lg-3"><a class="card" href="<?= site_url('penjualan') ?>">
            Draft penjualan <span class="value"><?= (int) $ringkasan['draft'] ?></span>Menunggu posting Akuntansi/Admin</a></div>
        <div class="col-sm-6 col-lg-3"><a class="card customer" href="<?= site_url('pelanggan') ?>">
            Pelanggan aktif <span class="value"><?= (int) $ringkasan['pelanggan'] ?></span>Di seluruh perusahaan</a></div>
    </div>
    <section class="panel panel-default">
        <div class="panel-heading"><strong>Tagihan penjualan terbuka</strong> <span class="text-muted">(maksimal 8, jatuh tempo terdekat)</span></div>
        <div class="table-responsive"><table class="table table-hover">
            <thead><tr><th>Penjualan</th><th>Pelanggan</th><th>Jatuh tempo</th><th class="text-right">Sisa piutang</th></tr></thead>
            <tbody><?php foreach ($ringkasan['tagihan'] as $tagihan): ?>
                <tr><td><a href="<?= site_url('penjualan?id=' . (int) $tagihan->id_transaksi) ?>"><?= html_escape($tagihan->nomor_transaksi) ?></a></td>
                    <td><?= html_escape($tagihan->nama_pelanggan) ?></td>
                    <td><?= html_escape(format_tanggal_indonesia($tagihan->jatuh_tempo)) ?></td>
                    <td class="text-right">Rp <?= html_escape(format_nominal($tagihan->sisa)) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$ringkasan['tagihan']): ?><tr><td colspan="4">Tidak ada tagihan penjualan yang belum lunas.</td></tr><?php endif; ?></tbody>
        </table></div>
    </section>
    <section class="panel panel-default"><div class="panel-body">
        <p><strong><?= (int) $ringkasan['bulan_ini'] ?></strong> penjualan perusahaan diposting bulan ini.</p>
        <div class="shortcuts">
            <a class="btn btn-primary" href="<?= site_url('penjualan') ?>">Penjualan</a>
            <a class="btn btn-default" href="<?= site_url('penerimaan_piutang') ?>">Penerimaan Piutang</a>
            <a class="btn btn-default" href="<?= site_url('pelanggan') ?>">Pelanggan</a>
            <a class="btn btn-default" href="<?= site_url('produk') ?>">Katalog Produk</a>
        </div>
    </div></section>
    <p class="note">Ringkasan mencakup transaksi seluruh perusahaan, tanpa membedakan pembuat. Piutang hanya berasal dari penjualan kredit yang diposting dan memiliki jurnal; sisa dikurangi penerimaan dan retur yang diposting. Saldo awal dan piutang dari transaksi lain tidak termasuk.</p>
</div>
