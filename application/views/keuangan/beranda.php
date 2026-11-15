<?php
$saldo_kas = '0.00';
foreach ($ringkasan['kas'] as $akun) {
    $saldo_kas = jumlah_nominal($saldo_kas, $akun->saldo);
}
$sections = array(
    'utang' => array('judul' => 'Utang terbuka', 'rute' => 'pembayaran_utang', 'aksi' => 'Buka Pembayaran Utang'),
    'piutang' => array('judul' => 'Piutang terbuka', 'rute' => 'penerimaan_piutang', 'aksi' => 'Buka Penerimaan Piutang')
);
?>
<style>
    .finance-home { color:#263248; }
    .finance-home .hero { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:15px; padding:24px; margin-bottom:22px; background:linear-gradient(120deg,#183153,#287d87); color:#fff; border-radius:12px; }
    .finance-home .hero h2 { margin:0 0 6px; color:#fff; font-weight:700; }
    .finance-home .hero p { margin:0; }
    .finance-home .shortcuts { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:20px; }
    .finance-home .summary { border:1px solid #dfe7ef; border-left:4px solid #348a9a; border-radius:10px; background:#fff; padding:18px; margin-bottom:18px; min-height:145px; }
    .finance-home .summary.debt { border-left-color:#d58b39; }
    .finance-home .summary.receivable { border-left-color:#45a178; }
    .finance-home .value { display:block; font-size:23px; font-weight:700; color:#183153; margin:8px 0; overflow-wrap:anywhere; }
    .finance-home .panel { border-radius:10px; }
    .finance-home .note { color:#687a91; }
    .finance-home .alert-due { color:#a34636; font-weight:600; }
    .finance-home .table td, .finance-home .table th { vertical-align:middle; }
    @media (max-width:767px) { .finance-home .summary { min-height:0; } }
</style>
<div class="finance-home">
    <div class="hero">
        <div><h2>Beranda Keuangan</h2><p>Halo, <?= html_escape($nama) ?>. Pantau kas dan tagihan seluruh perusahaan.</p></div>
        <div><?= html_escape(format_tanggal_indonesia($hari_ini, true)) ?></div>
    </div>
    <div class="shortcuts">
        <a class="btn btn-primary" href="<?= site_url('kas_bank') ?>">Kas &amp; Bank</a>
        <a class="btn btn-default" href="<?= site_url('pembayaran_utang') ?>">Pembayaran Utang</a>
        <a class="btn btn-default" href="<?= site_url('penerimaan_piutang') ?>">Penerimaan Piutang</a>
    </div>
    <div class="row">
        <div class="col-sm-4"><div class="summary">
            Saldo kas &amp; bank <span class="value">Rp <?= html_escape(format_nominal($saldo_kas)) ?></span>
            Saldo jurnal sampai <?= html_escape(format_tanggal_indonesia($hari_ini)) ?>
        </div></div>
        <div class="col-sm-4"><div class="summary debt">
            Utang terbuka <span class="value">Rp <?= html_escape(format_nominal($ringkasan['utang']['ringkasan']->total)) ?></span>
            <?= (int) $ringkasan['utang']['ringkasan']->jumlah ?> tagihan · <?= (int) $ringkasan['utang']['ringkasan']->jumlah_terlambat ?> lewat jatuh tempo
        </div></div>
        <div class="col-sm-4"><div class="summary receivable">
            Piutang terbuka <span class="value">Rp <?= html_escape(format_nominal($ringkasan['piutang']['ringkasan']->total)) ?></span>
            <?= (int) $ringkasan['piutang']['ringkasan']->jumlah ?> tagihan · <?= (int) $ringkasan['piutang']['ringkasan']->jumlah_terlambat ?> lewat jatuh tempo
        </div></div>
    </div>
    <section class="panel panel-default">
        <div class="panel-heading"><strong>Draft menunggu posting</strong></div>
        <div class="panel-body">
            <a href="<?= site_url('kas_bank') ?>">Kas &amp; Bank: <?= (int) $ringkasan['draft']['kas_bank'] ?></a> &nbsp; · &nbsp;
            <a href="<?= site_url('pembayaran_utang') ?>">Pembayaran Utang: <?= (int) $ringkasan['draft']['utang'] ?></a> &nbsp; · &nbsp;
            <a href="<?= site_url('penerimaan_piutang') ?>">Penerimaan Piutang: <?= (int) $ringkasan['draft']['piutang'] ?></a>
            <p class="note">Draft seluruh perusahaan belum memengaruhi saldo atau sisa tagihan. Akuntansi/Admin melakukan posting dan pembatalan.</p>
        </div>
    </section>
    <section class="panel panel-default">
        <div class="panel-heading"><strong>Saldo per akun kas &amp; bank</strong></div>
        <div class="table-responsive"><table class="table table-hover">
            <thead><tr><th>Kode</th><th>Akun</th><th class="text-right">Saldo</th></tr></thead>
            <tbody><?php foreach ($ringkasan['kas'] as $akun): ?>
                <tr><td><?= html_escape($akun->kode_akun) ?></td><td><?= html_escape($akun->nama_akun) ?></td>
                    <td class="text-right">Rp <?= html_escape(format_nominal($akun->saldo)) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$ringkasan['kas']): ?><tr><td colspan="3">Akun induk kas/bank 1100 belum tersedia. Hubungi Admin.</td></tr><?php endif; ?></tbody>
        </table></div>
        <div class="panel-body note">Saldo setiap akun adalah debit dikurangi kredit jurnal, termasuk jurnal pembalik; bukan saldo rekening bank yang terhubung secara langsung.</div>
    </section>
    <?php foreach ($sections as $key => $section): $data = $ringkasan[$key]; ?>
        <section class="panel panel-default">
            <div class="panel-heading"><strong><?= html_escape($section['judul']) ?></strong> <span class="text-muted">(maksimal 8, jatuh tempo terdekat)</span></div>
            <div class="panel-body">
                Lewat jatuh tempo: <strong class="alert-due">Rp <?= html_escape(format_nominal($data['ringkasan']->terlambat)) ?></strong>
                <a class="btn btn-default btn-sm pull-right" href="<?= site_url($section['rute']) ?>"><?= html_escape($section['aksi']) ?></a>
            </div>
            <div class="table-responsive"><table class="table table-hover">
                <thead><tr><th>Nomor</th><th><?= $key === 'utang' ? 'Pemasok' : 'Pelanggan' ?></th><th>Jatuh tempo</th><th class="text-right">Sisa</th></tr></thead>
                <tbody><?php foreach ($data['tagihan'] as $item): ?>
                    <tr><td><?= html_escape($item->nomor_transaksi) ?></td>
                        <td><?= html_escape($item->pihak) ?><?= $item->aktif ? '' : ' (tidak aktif)' ?></td>
                        <td class="<?= $item->jatuh_tempo && $item->jatuh_tempo < $hari_ini ? 'alert-due' : '' ?>"><?= html_escape(format_tanggal_indonesia($item->jatuh_tempo)) ?></td>
                        <td class="text-right">Rp <?= html_escape(format_nominal($item->sisa)) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$data['tagihan']): ?><tr><td colspan="4">Tidak ada tagihan terbuka.</td></tr><?php endif; ?></tbody>
            </table></div>
        </section>
    <?php endforeach; ?>
    <p class="note">Utang mencakup saldo awal serta pembelian, perolehan aset, dan beban penjualan jasa kredit yang diposting dan memiliki jurnal. Piutang mencakup saldo awal serta penjualan dan pelepasan aset kredit yang diposting. Sisa dikurangi pembayaran atau penerimaan serta retur yang diposting. Tagihan dan draft ditampilkan berdasarkan status saat ini, termasuk transaksi bertanggal mendatang. Pihak tidak aktif harus diaktifkan kembali oleh pengguna berwenang sebelum dipilih dalam transaksi.</p>
</div>
