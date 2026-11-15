<style>
.owner { color:#263248; } .owner-hero { padding:24px; margin-bottom:22px; background:linear-gradient(120deg,#183153,#2c7985); border-radius:12px; color:#fff; }
.owner-hero h2 { color:#fff; font-weight:700; margin:0 0 6px; } .owner-hero p { margin:0; }
.owner-card { display:block; min-height:130px; margin-bottom:18px; padding:18px; border:1px solid #e1e8f0; border-left:4px solid #4a93a5; border-radius:10px; background:#fff; color:inherit; }
.owner-card:hover,.owner-card:focus { color:inherit; text-decoration:none; box-shadow:0 5px 16px rgba(27,49,79,.12); }
.owner-card strong { display:block; font-size:22px; color:#183153; margin:8px 0; overflow-wrap:anywhere; }
.owner-card.orange { border-left-color:#d58b39; } .owner-card.green { border-left-color:#35a77a; } .owner-card.purple { border-left-color:#7d72c5; }
.owner .panel { border-radius:10px; } .owner .note { color:#687a91; } .owner .shortcuts { display:flex; flex-wrap:wrap; gap:8px; }
@media(max-width:767px) { .owner-card { min-height:0; } }
</style>
<div class="owner">
    <div class="owner-hero"><h2>Beranda Manajer / Pemilik</h2>
        <p>Halo, <?= html_escape($nama) ?>. Ringkasan perusahaan per <?= html_escape(format_tanggal_indonesia($today)) ?>.</p></div>
    <?php if (!$keuangan['kas']): ?><div class="alert alert-warning">Akun induk kas/bank 1100 belum tersedia; saldo kas belum dapat ditampilkan. Hubungi Admin.</div><?php endif; ?>
    <div class="row">
        <div class="col-sm-6 col-lg-3"><a class="owner-card" href="<?= site_url('manajer/kas') ?>">Saldo kas &amp; bank
            <strong><?= $keuangan['kas'] ? 'Rp ' . html_escape(format_nominal($kas)) : 'Tidak tersedia' ?></strong>Saldo jurnal sampai hari ini</a></div>
        <div class="col-sm-6 col-lg-3"><a class="owner-card green" href="<?= site_url('laba_rugi') ?>">Laba / rugi bulan ini
            <strong>Rp <?= html_escape(format_nominal($laba['laba_bersih'])) ?></strong>Menurut jurnal hingga hari ini</a></div>
        <div class="col-sm-6 col-lg-3"><a class="owner-card orange" href="<?= site_url('manajer/tagihan/utang') ?>">Utang terbuka
            <strong>Rp <?= html_escape(format_nominal($keuangan['utang']['ringkasan']->total)) ?></strong><?= (int) $keuangan['utang']['ringkasan']->jumlah_terlambat ?> tagihan lewat jatuh tempo</a></div>
        <div class="col-sm-6 col-lg-3"><a class="owner-card purple" href="<?= site_url('manajer/tagihan/piutang') ?>">Piutang terbuka
            <strong>Rp <?= html_escape(format_nominal($keuangan['piutang']['ringkasan']->total)) ?></strong><?= (int) $keuangan['piutang']['ringkasan']->jumlah_terlambat ?> tagihan lewat jatuh tempo</a></div>
    </div>
    <div class="row">
        <div class="col-md-6"><section class="panel panel-default">
            <div class="panel-heading"><strong>Penjualan bulan ini</strong></div>
            <div class="panel-body"><p>Penjualan terposting: <strong>Rp <?= html_escape(format_nominal($penjualan['ringkasan']->penjualan)) ?></strong></p>
                <p>Retur terposting: <strong>Rp <?= html_escape(format_nominal($penjualan['ringkasan']->retur)) ?></strong></p>
                <p>Bersih setelah retur: <strong>Rp <?= html_escape(format_nominal(kurangi_nominal($penjualan['ringkasan']->penjualan, $penjualan['ringkasan']->retur))) ?></strong></p>
                <a href="<?= site_url('manajer/penjualan') ?>">Lihat penjualan &rarr;</a></div>
        </section></div>
        <div class="col-md-6"><section class="panel panel-default">
            <div class="panel-heading"><strong>Stok pada atau di bawah batas minimum</strong></div>
            <div class="panel-body">
                <p><?= (int) $stok['jumlah'] ?> barang aktif dengan batas minimum lebih dari nol.</p>
                <?php foreach ($stok['barang'] as $item): ?><p><?= html_escape($item->nama_produk) ?>:
                    <strong><?= html_escape(format_jumlah_indonesia($item->jumlah)) ?> <?= html_escape($item->satuan) ?></strong>
                    <span class="note">(minimum <?= html_escape(format_jumlah_indonesia($item->stok_minimum)) ?>)</span></p><?php endforeach; ?>
                <?php if (!$stok['barang']): ?><p class="note">Tidak ada barang yang memenuhi kriteria tersebut.</p><?php endif; ?>
                <a href="<?= site_url('persediaan') ?>">Lihat persediaan &rarr;</a>
            </div>
        </section></div>
    </div>
    <section class="panel panel-default"><div class="panel-heading"><strong>Laporan dan jurnal</strong></div>
        <div class="panel-body shortcuts">
            <a class="btn btn-primary" href="<?= site_url('laba_rugi') ?>">Laba Rugi</a>
            <a class="btn btn-default" href="<?= site_url('neraca') ?>">Neraca</a>
            <a class="btn btn-default" href="<?= site_url('manajer/kas') ?>">Mutasi Kas &amp; Bank</a>
            <a class="btn btn-default" href="<?= site_url('jurnal') ?>">Jurnal</a>
            <a class="btn btn-default" href="<?= site_url('laporan') ?>">Laporan Akuntansi</a>
        </div></section>
    <p class="note">Akses baca-saja. Saldo kas berasal dari jurnal, bukan rekening bank langsung. Penjualan dan tagihan mengikuti status saat ini; tagihan terbuka dapat mencakup tanggal mendatang dan belum dikurangi draft. Stok menunjukkan saldo saat ini.</p>
</div>
