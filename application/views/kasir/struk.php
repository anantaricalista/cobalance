<style>
.receipt { max-width:480px; margin:20px auto; padding:24px; background:#fff; border:1px solid #dfe7ef; color:#222; }
.receipt h2 { margin-top:0; text-align:center; } .receipt table { width:100%; } .receipt td { padding:5px 0; vertical-align:top; }
.receipt .right { text-align:right; } .receipt .rule { border-top:1px dashed #888; margin:12px 0; }
.receipt-actions { max-width:480px; margin:15px auto; display:flex; gap:8px; flex-wrap:wrap; }
@media print { body * { visibility:hidden; } .receipt, .receipt * { visibility:visible; } .receipt { position:absolute; left:0; top:0; width:100%; border:0; margin:0; padding:0; } }
</style>
<div class="receipt-actions">
    <a class="btn btn-success" href="<?= site_url('pos') ?>">Transaksi baru</a>
    <button class="btn btn-default" type="button" onclick="window.print()">Cetak struk</button>
    <a class="btn btn-default" href="<?= site_url('kasir/beranda') ?>">Beranda</a>
</div>
<article class="receipt">
    <h2>Struk Penjualan</h2>
    <p><strong><?= html_escape($transaction->nomor_transaksi) ?></strong><br>
        <?= html_escape(format_tanggal_indonesia($transaction->tanggal)) ?><br>
        Pelanggan: <?= html_escape($transaction->nama_pelanggan) ?><br>
        Status: <?= html_escape($transaction->status) ?></p>
    <?php if ($transaction->status !== 'diposting'): ?><p><strong>Transaksi ini tidak lagi berstatus diposting.</strong></p><?php endif; ?>
    <div class="rule"></div>
    <table><tbody>
        <?php foreach ($transaction->details as $line): ?>
            <tr><td><?= html_escape($line['deskripsi']) ?><br>
                <small><?= html_escape(format_jumlah_indonesia($line['jumlah'])) ?> <?= html_escape($line['satuan']) ?> × Rp <?= html_escape(format_nominal($line['harga'])) ?></small></td>
                <td class="right">Rp <?= html_escape(format_nominal($line['subtotal'])) ?></td></tr>
        <?php endforeach; ?>
    </tbody></table>
    <div class="rule"></div>
    <table><tbody>
        <tr><td>Nilai sebelum pajak</td><td class="right">Rp <?= html_escape(format_nominal($transaction->total_sebelum_pajak)) ?></td></tr>
        <tr><td>Pajak</td><td class="right">Rp <?= html_escape(format_nominal($transaction->total_pajak)) ?></td></tr>
        <tr><td><strong>Total</strong></td><td class="right"><strong>Rp <?= html_escape(format_nominal($transaction->total)) ?></strong></td></tr>
        <tr><td>Bayar (<?= html_escape(strtoupper($payment->metode)) ?>)</td><td class="right">Rp <?= html_escape(format_nominal($payment->dibayar)) ?></td></tr>
        <tr><td>Kembalian</td><td class="right">Rp <?= html_escape(format_nominal($payment->kembalian)) ?></td></tr>
    </tbody></table>
    <div class="rule"></div><p style="text-align:center">Terima kasih</p>
</article>
