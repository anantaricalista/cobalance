<h3>Stok Barang</h3>
<p class="text-muted">Stok mengacu pada saldo persediaan saat ini. Status hampir habis mengikuti batas minimum pada Master Produk.</p>
<form class="form-inline" method="get" action="<?= site_url('gudang/stok') ?>" style="margin-bottom:12px">
    <input type="text" name="cari" class="form-control" maxlength="100" placeholder="Cari kode / nama" value="<?= html_escape($cari) ?>">
    <button type="submit" class="btn btn-default">Cari</button>
</form>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Kode</th><th>Produk</th><th>Satuan</th><th>Stok saat ini</th><th>Batas minimum</th><th>Status</th><th>Kartu stok</th></tr></thead>
        <tbody>
        <?php foreach ($stok as $row): ?>
            <tr>
                <td><?= html_escape($row->kode_produk) ?></td>
                <td><?= html_escape($row->nama_produk) ?></td>
                <td><?= html_escape($row->satuan) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($row->jumlah)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($row->stok_minimum)) ?></td>
                <td><?php
                    $statuses = array(
                        'tidak_aktif' => array('Tidak aktif', 'default'),
                        'habis' => array('Habis', 'danger'),
                        'hampir_habis' => array('Hampir habis', 'warning'),
                        'aman' => array('Aman', 'success')
                    );
                    $status = isset($statuses[$row->status_stok]) ? $statuses[$row->status_stok] : array('Tidak diketahui', 'default');
                ?><span class="label label-<?= $status[1] ?>"><?= html_escape($status[0]) ?></span></td>
                <td><a href="<?= site_url('gudang/stok/kartu/' . (int) $row->id_produk) ?>">Lihat</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$stok): ?>
            <tr><td colspan="7"><?= $cari !== '' ? 'Tidak ditemukan produk barang yang cocok.' : 'Belum ada produk barang.' ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
