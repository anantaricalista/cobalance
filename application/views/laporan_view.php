<!-- File: application/views/laporan_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Laporan Akuntansi (Trial Balance)</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <a class="btn btn-warning btn-sm" href="<?php echo site_url('laporan/export_pdf'); ?>" target="_blank"
            title="Export PDF">
            <i class="glyphicon glyphicon-file"></i>
        </a>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width:80px;">Kode Akun</th>
                <th>Nama Akun</th>
                <th style="width:100px; text-align:right;">Total Debit</th>
                <th style="width:100px; text-align:right;">Total Kredit</th>
                <th style="width:100px; text-align:right;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($laporan)): ?>
                <?php foreach ($laporan as $row): ?>
                    <tr>
                        <td>
                            <?php echo $row->kode_akun; ?>
                        </td>
                        <td>
                            <?php echo $row->nama_akun; ?>
                        </td>
                        <td style="text-align:right;">
                            <?php echo number_format($row->total_debit, 2, ',', '.'); ?>
                        </td>
                        <td style="text-align:right;">
                            <?php echo number_format($row->total_kredit, 2, ',', '.'); ?>
                        </td>
                        <td style="text-align:right;">
                            <?php echo number_format($row->saldo, 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align:center;">Data tidak ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <?php
            $totalDebit = 0;
            $totalKredit = 0;
            $totalSaldo = 0;
            foreach ($laporan as $row) {
                $totalDebit += $row->total_debit;
                $totalKredit += $row->total_kredit;
                $totalSaldo += $row->saldo;
            }
            ?>
            <tr style="font-weight:bold;">
                <td colspan="2" style="text-align:right;">Total</td>
                <td style="text-align:right;">
                    <?php echo number_format($totalDebit, 2, ',', '.'); ?>
                </td>
                <td style="text-align:right;">
                    <?php echo number_format($totalKredit, 2, ',', '.'); ?>
                </td>
                <td style="text-align:right;">
                    <?php echo number_format($totalSaldo, 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>