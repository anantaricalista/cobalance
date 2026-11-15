<?php
$types = array(
    'penjualan' => array('Penjualan', 'penjualan', 'id'),
    'pembelian' => array('Pembelian', 'pembelian', 'id'),
    'penerimaan_piutang' => array('Penerimaan Piutang', 'penerimaan_piutang', 'id'),
    'pembayaran_utang' => array('Pembayaran Utang', 'pembayaran_utang', 'id'),
    'kas_masuk' => array('Kas Masuk', 'kas_bank', 'id'),
    'kas_keluar' => array('Kas Keluar', 'kas_bank', 'id'),
    'transfer_kas' => array('Transfer Kas/Bank', 'kas_bank', 'id'),
    'retur_penjualan' => array('Retur Penjualan', 'retur_persediaan', 'id'),
    'retur_pembelian' => array('Retur Pembelian', 'retur_persediaan', 'id'),
    'saldo_awal' => array('Saldo Awal', 'saldo_awal', null),
    'saldo_awal_piutang' => array('Saldo Awal Piutang', 'saldo_awal', null),
    'saldo_awal_utang' => array('Saldo Awal Utang', 'saldo_awal', null),
    'saldo_awal_persediaan' => array('Saldo Awal Persediaan', 'saldo_awal', null),
    'beban_penjualan_jasa' => array('Beban Penjualan Jasa', 'beban_penjualan_jasa', null),
    'penyesuaian_akuntansi' => array('Penyesuaian Akuntansi', 'penyesuaian_akuntansi', 'id'),
    'penyesuaian_persediaan' => array('Penyesuaian Persediaan', 'penyesuaian_persediaan', 'id'),
    'setoran_modal' => array('Setoran Modal', 'modal_prive', 'id'),
    'prive' => array('Prive', 'modal_prive', 'id'),
    'perolehan_aset' => array('Perolehan Aset', 'aset_tetap', 'trx'),
    'penyusutan_aset' => array('Penyusutan Aset', 'aset_tetap', 'trx'),
    'pelepasan_aset' => array('Pelepasan Aset', 'aset_tetap', 'trx')
);
?>
<style>
    .accounting-home { color:#263248; }
    .accounting-home .hero { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:15px; padding:24px; margin-bottom:22px; background:linear-gradient(120deg,#183153,#356d91); color:#fff; border-radius:12px; }
    .accounting-home .hero h2 { margin:0 0 6px; color:#fff; font-weight:700; }
    .accounting-home .hero p { margin:0; }
    .accounting-home .summary { display:block; min-height:130px; padding:18px; margin-bottom:18px; border:1px solid #dfe7ef; border-left:4px solid #4a93a5; border-radius:10px; background:#fff; color:inherit; }
    .accounting-home a.summary:hover, .accounting-home a.summary:focus { color:inherit; text-decoration:none; box-shadow:0 5px 16px rgba(27,49,79,.12); }
    .accounting-home .summary.warning { border-left-color:#d58b39; }
    .accounting-home .summary.success { border-left-color:#45a178; }
    .accounting-home .value { display:block; margin:8px 0; color:#183153; font-size:26px; font-weight:700; }
    .accounting-home .panel { border-radius:10px; }
    .accounting-home .shortcuts { display:flex; flex-wrap:wrap; gap:8px; }
    .accounting-home .note { color:#687a91; }
    .accounting-home .table td, .accounting-home .table th { vertical-align:middle; }
    @media (max-width:767px) { .accounting-home .summary { min-height:0; } }
</style>
<div class="accounting-home">
    <div class="hero">
        <div><h2>Beranda Akuntansi</h2><p>Halo, <?= html_escape($nama) ?>. Tinjau transaksi dan usulan sebelum mem-posting.</p></div>
        <div><?= html_escape(format_tanggal_indonesia($hari_ini, true)) ?></div>
    </div>
    <div class="row">
        <div class="col-sm-4"><a class="summary" href="#draft-menunggu">
            Draft menunggu tinjauan <span class="value"><?= (int) $ringkasan['jumlah_draft'] ?></span>Seluruh perusahaan</a></div>
        <div class="col-sm-4"><a class="summary warning" href="<?= site_url('penyesuaian_persediaan#usulan-opname') ?>">
            Usulan opname menunggu <span class="value"><?= (int) $ringkasan['usulan'] ?></span>Tinjau dan tindak lanjuti</a></div>
        <div class="col-sm-4"><a class="summary success" href="<?= site_url('jurnal') ?>">
            Transaksi terposting bulan ini <span class="value"><?= (int) $ringkasan['posting_bulan_ini'] ?></span>Berdasarkan tanggal transaksi</a></div>
    </div>
    <section class="panel panel-default" id="draft-menunggu">
        <div class="panel-heading"><strong>Draft menunggu posting</strong> <span class="text-muted">(maksimal 15, tanggal paling awal)</span></div>
        <div class="table-responsive"><table class="table table-hover">
            <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis</th><th>Pembuat</th><th class="text-right">Total</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($ringkasan['draft'] as $item):
                $type = isset($types[$item->jenis_transaksi]) ? $types[$item->jenis_transaksi] : null;
                $url = $type ? site_url($type[1] . ($type[2] ? '?' . $type[2] . '=' . (int) $item->id_transaksi : '')) : null;
            ?>
                <tr><td><?= html_escape(format_tanggal_indonesia($item->tanggal)) ?></td>
                    <td><?= html_escape($item->nomor_transaksi) ?></td>
                    <td><?= html_escape($type ? $type[0] : $item->jenis_transaksi) ?></td>
                    <td><?= html_escape($item->pembuat ? $item->pembuat : '—') ?></td>
                    <td class="text-right"><?= $item->total === null ? '—' : 'Rp ' . html_escape(format_nominal($item->total)) ?></td>
                    <td><?php if ($url): ?><a href="<?= html_escape($url) ?>" class="btn btn-default btn-xs"><?= $type[2] ? 'Tinjau' : 'Buka modul' ?></a><?php else: ?>Modul tidak tersedia<?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$ringkasan['draft']): ?><tr><td colspan="6">Tidak ada draft yang menunggu posting.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
        <div class="panel-body note">Periksa rincian dan bukti pada modul terkait sebelum mem-posting. Jumlah dan daftar draft mencakup seluruh perusahaan, termasuk tanggal mendatang. Rincian piutang/utang saldo awal yang diposting bersama saldo awal induk tidak dihitung terpisah.</div>
    </section>
    <section class="panel panel-default">
        <div class="panel-heading"><strong>Jumlah draft menurut jenis</strong></div>
        <div class="panel-body">
            <?php if (!$ringkasan['jenis_draft']): ?><span class="note">Belum ada draft.</span><?php endif; ?>
            <?php foreach ($ringkasan['jenis_draft'] as $item):
                $type = isset($types[$item->jenis_transaksi]) ? $types[$item->jenis_transaksi] : null; ?>
                <p><?php if ($type): ?><a href="<?= site_url($type[1]) ?>"><?= html_escape($type[0]) ?></a>
                    <?php else: ?><?= html_escape($item->jenis_transaksi) ?><?php endif; ?>
                    <strong> · <?= (int) $item->jumlah ?></strong></p>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel panel-default">
        <div class="panel-heading"><strong>Akses cepat pembukuan</strong></div>
        <div class="panel-body shortcuts">
            <a class="btn btn-primary" href="<?= site_url('penyesuaian_akuntansi') ?>">Penyesuaian Akuntansi</a>
            <a class="btn btn-default" href="<?= site_url('jurnal') ?>">Jurnal Transaksi</a>
            <a class="btn btn-default" href="<?= site_url('buku_besar') ?>">Buku Besar</a>
            <a class="btn btn-default" href="<?= site_url('neraca') ?>">Neraca</a>
            <a class="btn btn-default" href="<?= site_url('laba_rugi') ?>">Laba Rugi</a>
            <a class="btn btn-default" href="<?= site_url('laporan') ?>">Laporan Akuntansi</a>
        </div>
    </section>
    <p class="note">Koreksi saldo awal yang sudah diposting tetap hanya tersedia untuk Admin Perusahaan.</p>
</div>
