<h2>Penjualan</h2>
<p class="text-muted">Penjualan dan retur berstatus diposting menurut tanggal transaksi. Draft dan transaksi yang saat ini dibatalkan tidak termasuk; jumlah menggunakan total transaksi termasuk pajak.</p>
<?php $this->load->view('manajer/periode', array('start' => $start, 'end' => $end)); ?>
<div class="row">
    <div class="col-sm-3"><strong>Penjualan</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->penjualan)) ?></p></div>
    <div class="col-sm-3"><strong>Retur</strong><p>Rp <?= html_escape(format_nominal($data['ringkasan']->retur)) ?></p></div>
    <div class="col-sm-3"><strong>Setelah retur</strong><p>Rp <?= html_escape(format_nominal(kurangi_nominal($data['ringkasan']->penjualan, $data['ringkasan']->retur))) ?></p></div>
    <div class="col-sm-3"><strong>Jumlah penjualan</strong><p><?= (int) $data['ringkasan']->jumlah ?></p></div>
</div>
<div class="table-responsive"><table class="table table-striped">
    <thead><tr><th>Tanggal</th><th>Nomor</th><th>Jenis</th><th>Pelanggan</th><th class="text-right">Total</th></tr></thead>
    <tbody><?php foreach ($data['rows'] as $row): ?>
        <tr><td><?= html_escape(format_tanggal_indonesia($row->tanggal)) ?></td><td><?= html_escape($row->nomor_transaksi) ?></td>
            <td><?= $row->jenis_transaksi === 'penjualan' ? 'Penjualan' : 'Retur Penjualan' ?></td>
            <td><?= html_escape($row->nama_pelanggan ? $row->nama_pelanggan : '—') ?></td>
            <td class="text-right">Rp <?= html_escape(format_nominal($row->total)) ?></td></tr>
    <?php endforeach; ?><?php if (!$data['rows']): ?><tr><td colspan="5">Tidak ada transaksi pada halaman ini.</td></tr><?php endif; ?></tbody>
</table></div>
<?php $this->load->view('manajer/halaman', array('route' => 'manajer/penjualan', 'page' => $page, 'more' => $data['more'], 'start' => $start, 'end' => $end)); ?>
