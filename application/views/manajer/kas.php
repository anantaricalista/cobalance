<h2>Mutasi Kas &amp; Bank</h2>
<p class="text-muted">Ringkasan perubahan bersih akun kas/bank dari jurnal, termasuk jurnal pembalik. Kas masuk dan keluar dalam satu jurnal saling mengurangi, sehingga jumlahnya bukan mutasi bruto. Transfer antar akun kas/bank bersih nol dan tidak ditampilkan. Ini bukan laporan arus kas yang dikelompokkan menurut aktivitas operasi, investasi, dan pendanaan.</p>
<?php $this->load->view('manajer/periode', array('start' => $start, 'end' => $end)); ?>
<?php if (!$data['akun_tersedia']): ?><div class="alert alert-warning">Akun induk kas/bank 1100 belum tersedia. Hubungi Admin.</div>
<?php else: ?>
<div class="row">
    <div class="col-sm-3"><strong>Saldo awal</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->awal)) ?></p></div>
    <div class="col-sm-3"><strong>Masuk bersih</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->masuk)) ?></p></div>
    <div class="col-sm-3"><strong>Keluar bersih</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->keluar)) ?></p></div>
    <div class="col-sm-3"><strong>Saldo akhir</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->akhir)) ?></p></div>
</div>
<div class="table-responsive"><table class="table table-striped">
    <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Referensi</th><th class="text-right">Mutasi bersih</th><th>Jurnal</th></tr></thead>
    <tbody><?php foreach ($data['rows'] as $row): ?>
        <tr><td><?= html_escape(format_tanggal_indonesia($row->tanggal)) ?></td><td><?= html_escape($row->deskripsi) ?></td>
            <td><?= html_escape($row->referensi) ?></td><td class="text-right">Rp <?= html_escape(format_nominal($row->neto)) ?></td>
            <td><a href="<?= site_url('jurnal') ?>">Lihat jurnal</a></td></tr>
    <?php endforeach; ?><?php if (!$data['rows']): ?><tr><td colspan="5">Tidak ada mutasi bersih pada halaman ini.</td></tr><?php endif; ?></tbody>
</table></div>
<?php $this->load->view('manajer/halaman', array('route' => 'manajer/kas', 'page' => $page, 'more' => $data['more'], 'start' => $start, 'end' => $end)); ?>
<?php endif; ?>
