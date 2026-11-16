<?php
$hari = $ringkasan['hari_ini'];
$maks = 0;
foreach ($ringkasan['minggu'] as $day) {
    $maks = max($maks, $day['total']);
}
$selisih = $ringkasan['kemarin'] > 0 ? (($hari['total'] - $ringkasan['kemarin']) / $ringkasan['kemarin']) * 100 : null;
?>
<style>
    .kasir-home { color: #263248; }
    .kasir-home .hero { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 22px; padding: 22px 24px; border-radius: 12px; color: #fff; background: linear-gradient(120deg, #183153 0%, #2f7f93 100%); }
    .kasir-home .hero h2 { margin: 0 0 4px; font-weight: 700; color: #fff; }
    .kasir-home .hero p { margin: 0; opacity: .85; }
    .kasir-home .clock { font-size: 30px; font-weight: 700; letter-spacing: 1px; line-height: 1.1; }
    .kasir-home .clock small { display: block; font-size: 13px; font-weight: 400; opacity: .85; letter-spacing: 0; }
    .kasir-home .cta { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 22px; }
    .kasir-home .cta .btn-lg { align-self: center; }
    .kasir-home .cta form { flex: 1 1 260px; display: flex; gap: 6px; min-width: 240px; }
    .kasir-home .cta form input { height: 100%; min-height: 50px; border-radius: 9px; }
    .kasir-home .stat { margin-bottom: 18px; padding: 18px; border: 1px solid #e1e8f0; border-left: 4px solid #4a93a5; border-radius: 10px; background: #fff; transition: transform .15s, box-shadow .15s; }
    .kasir-home .stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(27,49,79,.12); }
    .kasir-home .stat.cash { border-left-color: #35a77a; }
    .kasir-home .stat.count { border-left-color: #e0a030; }
    .kasir-home .stat.draft { border-left-color: #8a6fc9; }
    .kasir-home .stat .label { color: #687a91; font-size: 13px; }
    .kasir-home .stat .value { display: block; margin: 6px 0; font-size: 24px; font-weight: 700; color: #183153; overflow-wrap: anywhere; }
    .kasir-home .up { color: #1e8a5e; } .kasir-home .down { color: #c24b4b; }
    .kasir-home .panel { border-radius: 10px; }
    .kasir-home .chart { display: flex; align-items: flex-end; gap: 10px; height: 160px; padding-top: 10px; }
    .kasir-home .chart .bar { flex: 1; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; text-align: center; font-size: 12px; color: #687a91; }
    .kasir-home .chart .fill { width: 100%; min-height: 4px; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, #4a93a5, #2f7f93); transform-origin: bottom; animation: kasir-grow .7s ease-out both; }
    .kasir-home .chart .bar.today .fill { background: linear-gradient(180deg, #35a77a, #1e8a5e); }
    .kasir-home .chart .bar b { color: #183153; font-size: 11px; }
    .kasir-home .rank { display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eef2f6; }
    .kasir-home .rank:last-child { border-bottom: 0; }
    .kasir-home .badge-tunai { background: #35a77a; } .kasir-home .badge-kredit { background: #e0a030; }
    .kasir-home .note { color: #687a91; }
    @keyframes kasir-grow { from { transform: scaleY(0); } to { transform: scaleY(1); } }
    @media (prefers-reduced-motion: reduce) { .kasir-home .chart .fill { animation: none; } .kasir-home .stat { transition: none; } }
</style>
<div class="kasir-home">
    <div class="hero">
        <div>
            <h2><span id="kasir-greeting">Halo</span>, <?= html_escape($nama) ?></h2>
            <p>Siap melayani pelanggan hari ini.</p>
        </div>
        <div class="clock" aria-live="off"><span id="kasir-time">--:--:--</span><small id="kasir-date"></small></div>
    </div>

    <div class="cta">
        <a class="btn btn-success btn-lg" href="<?= site_url('pos') ?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Transaksi baru</a>
        <form method="get" action="<?= site_url('produk') ?>" role="search">
            <label class="sr-only" for="kasir-cari">Cari produk</label>
            <input id="kasir-cari" name="cari" class="form-control input-lg" type="text" maxlength="100" placeholder="Cari harga atau stok produk…">
            <button class="btn btn-default btn-lg" type="submit"><span class="glyphicon glyphicon-search" aria-hidden="true"></span><span class="sr-only">Cari</span></button>
        </form>
    </div>

    <div class="row">
        <div class="col-sm-6 col-lg-3"><div class="stat">
            <span class="label">Penjualan hari ini</span>
            <span class="value">Rp <?= html_escape(format_nominal($hari['total'])) ?></span>
            <?php if ($selisih === null): ?><span class="text-muted">Belum ada pembanding kemarin</span>
            <?php else: ?><span class="<?= $selisih >= 0 ? 'up' : 'down' ?>"><?= $selisih >= 0 ? '▲' : '▼' ?> <?= html_escape(number_format(abs($selisih), 1, ',', '.')) ?>% dari kemarin</span><?php endif; ?>
        </div></div>
        <div class="col-sm-6 col-lg-3"><div class="stat cash">
            <span class="label">Penerimaan tunai</span>
            <span class="value">Rp <?= html_escape(format_nominal($hari['tunai'])) ?></span>
            Penjualan terposting; tidak termasuk pembayaran non-tunai
        </div></div>
        <div class="col-sm-6 col-lg-3"><div class="stat count">
            <span class="label">Jumlah transaksi</span>
            <span class="value"><span data-count="<?= (int) $hari['jumlah'] ?>"><?= (int) $hari['jumlah'] ?></span></span>
            Rata-rata Rp <?= html_escape(format_nominal($hari['rata_rata'])) ?>
        </div></div>
        <div class="col-sm-6 col-lg-3"><div class="stat draft">
            <span class="label">Menunggu posting</span>
            <span class="value"><span data-count="<?= (int) $hari['draft'] ?>"><?= (int) $hari['draft'] ?></span></span>
            Draft hari ini, diposting Akuntansi/Admin
        </div></div>
    </div>

    <div class="row">
        <div class="col-md-7">
            <section class="panel panel-default"><div class="panel-heading"><strong>Penjualan 7 hari terakhir</strong></div>
                <div class="panel-body"><div class="chart" role="img" aria-label="Grafik penjualan 7 hari terakhir">
                    <?php foreach ($ringkasan['minggu'] as $i => $day): $h = $maks > 0 ? max(3, round($day['total'] / $maks * 100)) : 3; ?>
                        <div class="bar <?= $i === 6 ? 'today' : '' ?>" title="Rp <?= html_escape(format_nominal($day['total'])) ?>">
                            <b><?= $day['total'] > 0 ? html_escape(number_format($day['total'] / 1000, 0, ',', '.')) . 'rb' : '' ?></b>
                            <div class="fill" style="height: <?= (int) $h ?>%; animation-delay: <?= $i * 60 ?>ms"></div>
                            <span><?= html_escape(preg_replace('/ \d{4}$/', '', format_tanggal_indonesia($day['tanggal']))) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div></div>
            </section>
            <section class="panel panel-default"><div class="panel-heading"><strong>Transaksi terbaru</strong></div>
                <div class="table-responsive"><table class="table table-hover">
                    <thead><tr><th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th>Bayar</th><th class="text-right">Total</th></tr></thead>
                    <tbody><?php foreach ($ringkasan['terbaru'] as $trx): ?>
                        <tr><td><a href="<?= $trx->metode_kasir ? site_url('pos/struk/' . (int) $trx->id_transaksi) : site_url('penjualan?id=' . (int) $trx->id_transaksi) ?>"><?= html_escape($trx->nomor_transaksi) ?></a>
                            <?= $trx->status === 'draft' ? '<span class="label label-default">draft</span>' : '' ?></td>
                            <td><?= html_escape(format_tanggal_indonesia($trx->tanggal)) ?></td>
                            <td><?= html_escape($trx->nama_pelanggan) ?></td>
                            <td><span class="badge badge-<?= $trx->cara_pembayaran === 'kredit' ? 'kredit' : 'tunai' ?>"><?= html_escape($trx->metode_kasir ? strtoupper($trx->metode_kasir) : ($trx->cara_pembayaran === 'kredit' ? 'Kredit' : 'Tunai')) ?></span></td>
                            <td class="text-right">Rp <?= html_escape(format_nominal($trx->total)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$ringkasan['terbaru']): ?><tr><td colspan="5">Belum ada transaksi penjualan.</td></tr><?php endif; ?></tbody>
                </table></div>
            </section>
        </div>
        <div class="col-md-5">
            <section class="panel panel-default"><div class="panel-heading"><strong>Terlaris 7 hari terakhir</strong></div>
                <div class="panel-body">
                    <?php foreach ($ringkasan['terlaris'] as $i => $item): ?>
                        <div class="rank"><span><strong><?= $i + 1 ?>.</strong> <?= html_escape($item->nama_produk) ?>
                            <span class="text-muted">· <?= html_escape(format_jumlah_indonesia($item->jumlah)) ?> <?= html_escape($item->satuan) ?></span></span>
                            <span>Rp <?= html_escape(format_nominal($item->nilai)) ?></span></div>
                    <?php endforeach; ?>
                    <?php if (!$ringkasan['terlaris']): ?><span class="text-muted">Belum ada produk terjual.</span><?php endif; ?>
                </div>
            </section>
            <section class="panel panel-warning"><div class="panel-heading"><strong>Stok hampir habis</strong></div>
                <div class="panel-body">
                    <?php foreach ($ringkasan['stok_menipis'] as $item): ?>
                        <div class="rank"><span><?= html_escape($item->nama_produk) ?></span>
                            <span><strong><?= html_escape(format_jumlah_indonesia($item->jumlah)) ?></strong> <?= html_escape($item->satuan) ?>
                            <span class="text-muted">/ min <?= html_escape(format_jumlah_indonesia($item->stok_minimum)) ?></span></span></div>
                    <?php endforeach; ?>
                    <?php if (!$ringkasan['stok_menipis']): ?><span class="text-muted">Semua stok aman.</span><?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <p class="note">Total, grafik dan produk terlaris mencakup penjualan terposting seluruh perusahaan; draft dihitung terpisah. Transaksi kasir selesai langsung diposting. Pembatalan dilakukan Akuntansi/Admin.</p>
</div>
<script>
(function () {
    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function tick() {
        var d = new Date(), h = d.getHours();
        document.getElementById('kasir-time').textContent = pad(h) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        document.getElementById('kasir-date').textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        document.getElementById('kasir-greeting').textContent = h < 11 ? 'Selamat pagi' : (h < 15 ? 'Selamat siang' : (h < 18 ? 'Selamat sore' : 'Selamat malam'));
    }
    tick();
    setInterval(tick, 1000);
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    var els = document.querySelectorAll('[data-count]');
    Array.prototype.forEach.call(els, function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10), start = null;
        if (!isFinite(target) || target <= 0) { return; }
        function step(ts) {
            if (start === null) { start = ts; }
            var p = Math.min(1, (ts - start) / 800);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
            if (p < 1) { requestAnimationFrame(step); }
        }
        requestAnimationFrame(step);
    });
})();
</script>
