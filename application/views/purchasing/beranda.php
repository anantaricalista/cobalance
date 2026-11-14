<style>
    .purchasing-dashboard { color: #263248; }
    .purchasing-dashboard .purchase-heading { margin: 4px 0 24px; }
    .purchasing-dashboard .purchase-heading h2 { margin: 0 0 6px; color: #183153; font-size: 28px; font-weight: 700; }
    .purchasing-dashboard .purchase-heading p, .purchasing-dashboard .purchase-note { color: #687a91; }
    .purchasing-dashboard .purchase-card {
        display: block; min-height: 158px; margin-bottom: 20px; padding: 20px;
        border: 1px solid #e1e8f0; border-left: 4px solid #4a93a5; border-radius: 10px;
        background: #fff; box-shadow: 0 4px 14px rgba(27,49,79,.05); color: inherit;
    }
    .purchasing-dashboard a.purchase-card:hover, .purchasing-dashboard a.purchase-card:focus {
        color: inherit; text-decoration: none; box-shadow: 0 8px 20px rgba(27,49,79,.11);
    }
    .purchasing-dashboard .purchase-card.debt { border-left-color: #e7a43b; }
    .purchasing-dashboard .purchase-card.overdue { border-left-color: #d76666; }
    .purchasing-dashboard .purchase-card.supplier { border-left-color: #35a77a; }
    .purchasing-dashboard .card-label { display: block; margin-bottom: 12px; color: #687a91; font-size: 13px; font-weight: 600; }
    .purchasing-dashboard .card-value { display: block; color: #183153; font-size: 23px; font-weight: 700; overflow-wrap: anywhere; }
    .purchasing-dashboard .card-note { display: block; margin-top: 8px; color: #718198; font-size: 12px; }
    .purchasing-dashboard .purchase-panel {
        margin-bottom: 22px; border: 1px solid #e1e8f0; border-radius: 10px;
        background: #fff; box-shadow: 0 4px 14px rgba(27,49,79,.04);
    }
    .purchasing-dashboard .panel-heading-custom { padding: 17px 20px; border-bottom: 1px solid #edf1f6; }
    .purchasing-dashboard .panel-heading-custom h3 { margin: 0 0 5px; font-size: 17px; }
    .purchasing-dashboard .panel-heading-custom p { margin: 0; color: #718198; }
    .purchasing-dashboard .panel-content { padding: 18px 20px; }
    .purchasing-dashboard .table { margin-bottom: 0; }
    .purchasing-dashboard .table th { border-top: 0; color: #718198; font-size: 12px; }
    .purchasing-dashboard .empty-state { padding: 20px; color: #718198; text-align: center; }
    .purchasing-dashboard .shortcuts { display: flex; flex-wrap: wrap; gap: 9px; }
    .purchasing-dashboard .shortcuts .btn { font-weight: 600; }
    @media (max-width: 767px) {
        .purchasing-dashboard .purchase-card { min-height: 0; }
        .purchasing-dashboard .panel-heading-custom, .purchasing-dashboard .panel-content { padding-right: 14px; padding-left: 14px; }
    }
</style>

<div class="purchasing-dashboard">
    <div class="purchase-heading">
        <h2>Beranda Purchasing</h2>
        <p>Halo, <?= html_escape($nama) ?>. Berikut ringkasan pembelian barang dan tagihan perusahaan.</p>
    </div>

    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <a class="purchase-card debt" href="<?= site_url('pembayaran_utang') ?>">
                <span class="card-label">Utang pembelian belum lunas</span>
                <span class="card-value">Rp <?= html_escape(format_nominal($ringkasan['utang']->total)) ?></span>
                <span class="card-note"><?= (int) $ringkasan['utang']->jumlah ?> tagihan terbuka</span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="purchase-card overdue" href="<?= site_url('pembayaran_utang') ?>">
                <span class="card-label">Utang lewat jatuh tempo</span>
                <span class="card-value">Rp <?= html_escape(format_nominal($ringkasan['utang']->terlambat)) ?></span>
                <span class="card-note"><?= (int) $ringkasan['utang']->jumlah_terlambat ?> tagihan lewat jatuh tempo</span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="purchase-card" href="<?= site_url('pembelian') ?>">
                <span class="card-label">Draft pembelian</span>
                <span class="card-value"><?= (int) $ringkasan['draft'] ?></span>
                <span class="card-note">Menunggu posting oleh Akuntansi atau Admin</span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="purchase-card supplier" href="<?= site_url('pemasok') ?>">
                <span class="card-label">Pemasok aktif</span>
                <span class="card-value"><?= (int) $ringkasan['pemasok'] ?></span>
                <span class="card-note">Di seluruh perusahaan</span>
            </a>
        </div>
    </div>

    <div class="purchase-panel">
        <div class="panel-heading-custom">
            <h3>Tagihan pembelian terbuka</h3>
            <p>Maksimal 8 tagihan, diurutkan berdasarkan jatuh tempo terdekat.</p>
        </div>
        <div class="panel-content">
            <?php if ($ringkasan['tagihan']): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Pembelian</th><th>Pemasok</th><th>Jatuh tempo</th><th class="text-right">Sisa utang</th></tr></thead>
                        <tbody>
                        <?php foreach ($ringkasan['tagihan'] as $tagihan): ?>
                            <tr>
                                <td><a href="<?= site_url('pembelian?id=' . (int) $tagihan->id_transaksi) ?>"><?= html_escape($tagihan->nomor_transaksi) ?></a></td>
                                <td><?= html_escape($tagihan->nama_pemasok) ?></td>
                                <td><?= html_escape(format_tanggal_indonesia($tagihan->jatuh_tempo)) ?></td>
                                <td class="text-right">Rp <?= html_escape(format_nominal($tagihan->sisa)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">Tidak ada tagihan pembelian yang belum lunas.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="purchase-panel">
        <div class="panel-heading-custom"><h3>Aktivitas dan akses cepat</h3></div>
        <div class="panel-content">
            <p><strong><?= (int) $ringkasan['bulan_ini'] ?></strong> pembelian barang diposting bulan ini.</p>
            <div class="shortcuts">
                <a class="btn btn-primary" href="<?= site_url('pembelian') ?>">Pembelian</a>
                <a class="btn btn-default" href="<?= site_url('pembayaran_utang') ?>">Pembayaran Utang</a>
                <a class="btn btn-default" href="<?= site_url('produk') ?>">Produk</a>
                <a class="btn btn-default" href="<?= site_url('pemasok') ?>">Pemasok</a>
            </div>
        </div>
    </div>
    <p class="purchase-note">Utang mencakup pembelian kredit perusahaan yang hanya berisi barang, sudah diposting, dan memiliki jurnal posting, tanpa membedakan pembuatnya. Sisa utang dikurangi pembayaran dan retur yang sudah diposting. Jumlah draft dan pembelian bulan ini hanya mencakup pembelian barang yang dibuat oleh pengguna yang saat ini ber-role Purchasing; perubahan role atau penghapusan akun pembuat dapat mengubah jumlahnya.</p>
</div>
