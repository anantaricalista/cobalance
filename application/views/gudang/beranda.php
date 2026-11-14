<style>
    .warehouse-dashboard { color: #263248; }
    .warehouse-dashboard .warehouse-heading { margin: 4px 0 22px; }
    .warehouse-dashboard .warehouse-heading h2 { margin: 0 0 6px; color: #183153; font-size: 28px; font-weight: 700; }
    .warehouse-dashboard .warehouse-heading p, .warehouse-dashboard .warehouse-note { color: #687a91; }
    .warehouse-dashboard .warehouse-card { min-height: 135px; margin-bottom: 18px; border-left: 4px solid #4a93a5; }
    .warehouse-dashboard .warehouse-card.empty { border-left-color: #d76666; }
    .warehouse-dashboard .warehouse-card.low { border-left-color: #e7a43b; }
    .warehouse-dashboard .warehouse-card.waiting { border-left-color: #7d72c5; }
    .warehouse-dashboard .warehouse-card h3 { margin: 0 0 9px; color: #183153; font-size: 25px; font-weight: 700; }
    .warehouse-dashboard .warehouse-panel { margin: 20px 0; border-color: #e1e8f0; border-radius: 9px; }
    .warehouse-dashboard .warehouse-panel .panel-heading { color: #243b5a; font-weight: 700; }
    .warehouse-dashboard .warehouse-panel .table { margin-bottom: 0; }
    .warehouse-dashboard .warehouse-panel .table th { border-top: 0; color: #718198; }
    .warehouse-dashboard .warehouse-empty { padding: 18px; color: #718198; text-align: center; }
    .warehouse-dashboard .warehouse-actions { display: flex; flex-wrap: wrap; gap: 9px; }
</style>

<div class="warehouse-dashboard">
    <div class="warehouse-heading">
        <h2>Beranda Gudang</h2>
        <p>Halo, <?= html_escape($nama) ?>. Pantau ketersediaan barang dan tindak lanjut opname.</p>
    </div>
    <div class="row">
        <div class="col-sm-6 col-lg-3"><div class="panel panel-default warehouse-card"><div class="panel-body">
            <h3><?= (int) $ringkasan['total_produk'] ?></h3>Produk barang aktif
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="panel panel-default warehouse-card empty"><div class="panel-body">
            <h3><?= (int) $ringkasan['stok_habis'] ?></h3>Produk stok habis
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="panel panel-default warehouse-card low"><div class="panel-body">
            <h3><?= (int) $ringkasan['stok_hampir_habis'] ?></h3>Produk hampir habis
        </div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="panel panel-default warehouse-card waiting"><div class="panel-body">
            <h3><?= (int) $ringkasan['usulan_menunggu'] ?></h3>Usulan opname menunggu
        </div></div></div>
    </div>
    <section class="panel panel-default warehouse-panel">
        <div class="panel-heading">Peringatan ketersediaan stok <span class="text-muted">(maksimal 8 barang)</span></div>
        <?php if ($ringkasan['peringatan_stok']): ?>
            <div class="table-responsive"><table class="table table-hover">
                <thead><tr><th>Status</th><th>Produk</th><th>Stok</th><th>Batas minimum</th><th></th></tr></thead>
                <tbody><?php foreach ($ringkasan['peringatan_stok'] as $produk): ?>
                    <tr>
                        <td><?= $produk->status_stok === 'habis' ? '<span class="label label-danger">Habis</span>' : '<span class="label label-warning">Hampir habis</span>' ?></td>
                        <td><?= html_escape($produk->kode_produk . ' - ' . $produk->nama_produk) ?></td>
                        <td><?= html_escape(format_jumlah_indonesia($produk->jumlah)) ?> <?= html_escape($produk->satuan) ?></td>
                        <td><?= html_escape(format_jumlah_indonesia($produk->stok_minimum)) ?> <?= html_escape($produk->satuan) ?></td>
                        <td><a class="btn btn-xs btn-default" href="<?= site_url('gudang/stok/kartu/' . (int) $produk->id_produk) ?>">Lihat kartu stok</a></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php else: ?>
            <div class="warehouse-empty">Tidak ada produk aktif yang habis atau di bawah batas minimum.</div>
        <?php endif; ?>
    </section>
    <p class="warehouse-note">Notifikasi memakai batas minimum per produk di Master Produk. Produk dengan batas 0 tidak ditandai hampir habis; stok 0 tetap dihitung sebagai habis.</p>
    <div class="warehouse-actions">
        <a class="btn btn-primary" href="<?= site_url('gudang/stok') ?>">Lihat semua stok</a>
        <a class="btn btn-default" href="<?= site_url('gudang/opname') ?>">Catat stock opname</a>
    </div>
</div>
