<h2><?= $type === 'utang' ? 'Utang' : 'Piutang' ?> Terbuka</h2>
<p class="text-muted">Tagihan seluruh perusahaan berdasarkan status saat ini, termasuk tanggal mendatang. Sisa dikurangi alokasi dan retur yang diposting; draft belum mengurangi tagihan.</p>
<p><strong><?= (int) $data['ringkasan']->jumlah ?> tagihan</strong> · Sisa Rp <?= html_escape(format_nominal($data['ringkasan']->total)) ?> ·
    Lewat jatuh tempo: <?= (int) $data['ringkasan']->jumlah_terlambat ?> tagihan, Rp <?= html_escape(format_nominal($data['ringkasan']->terlambat)) ?></p>
<div class="table-responsive"><table class="table table-striped">
    <thead><tr><th>Nomor</th><th><?= $type === 'utang' ? 'Pemasok' : 'Pelanggan' ?></th><th>Tanggal</th><th>Jatuh tempo</th><th class="text-right">Sisa</th></tr></thead>
    <tbody><?php foreach ($data['tagihan'] as $item): ?>
        <tr><td><?= html_escape($item->nomor_transaksi) ?></td><td><?= html_escape($item->pihak) ?><?= $item->aktif ? '' : ' (tidak aktif)' ?></td>
            <td><?= html_escape(format_tanggal_indonesia($item->tanggal)) ?></td>
            <td><?= html_escape(format_tanggal_indonesia($item->jatuh_tempo)) ?><?= $item->jatuh_tempo && $item->jatuh_tempo < $today ? ' (terlambat)' : '' ?></td>
            <td class="text-right">Rp <?= html_escape(format_nominal($item->sisa)) ?></td></tr>
    <?php endforeach; ?><?php if (!$data['tagihan']): ?><tr><td colspan="5">Tidak ada tagihan pada halaman ini.</td></tr><?php endif; ?></tbody>
</table></div>
<?php $this->load->view('manajer/halaman', array('route' => 'manajer/tagihan/' . $type, 'page' => $page, 'more' => $more)); ?>
<p class="text-muted">Utang: saldo awal serta pembelian, perolehan aset, dan beban penjualan jasa kredit yang diposting dan memiliki jurnal. Piutang: saldo awal serta penjualan dan pelepasan aset kredit yang diposting.</p>
