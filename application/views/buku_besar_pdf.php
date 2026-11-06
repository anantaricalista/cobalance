<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Buku Besar</title>
    <style type="text/css">
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            margin: 20px;
            color: #333;
        }

        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.header-table td {
            border: none;
            padding: 3px 0;
        }

        h1 {
            font-size: 16pt;
            text-align: center;
            margin-bottom: 0;
        }

        .info {
            text-align: center;
            margin-top: 5px;
            font-size: 11pt;
        }

        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.ledger-table th,
        table.ledger-table td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }

        table.ledger-table th {
            background-color: #e0e0e0;
        }

        table.ledger-table td.left {
            text-align: left;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="text-align:center;">
                <h1>Buku Besar</h1>
            </td>
        </tr>
    </table>
    <div class="info">
        <span><strong>Akun:</strong> <?php echo $akun->nama_akun . " (" . $akun->kode_akun . ")"; ?></span><br>
        <span><strong>Periode:</strong>
            <?php echo date("d F Y", strtotime($start_date)) . " - " . date("d F Y", strtotime($end_date)); ?></span>
    </div>
    <table class="ledger-table">
        <tr>
            <td style="width:5%;">No</td>
            <td style="width:15%;">Tanggal</td>
            <td style="width:25%;">Deskripsi</td>
            <td style="width:10%;">Referensi</td>
            <td style="width:15%;">Debit</td>
            <td style="width:15%;">Kredit</td>
            <td style="width:15%;">Saldo</td>
        </tr>
        <?php $running_balance = $opening; ?>
        <tr>
            <td>0</td>
            <td colspan="4" style="text-align:center;"><em>Saldo Awal</em></td>
            <td></td>
            <td style="text-align:right;">Rp.
                <?php echo number_format($opening, 2, ',', '.'); ?>
            </td>
        </tr>
        <?php $no = 1; ?>
        <?php foreach ($transactions as $trx):
            $running_balance += ($trx->debit - $trx->kredit);
            ?>
            <tr>
                <td>
                    <?php echo $no++; ?>
                </td>
                <td>
                    <?php echo date("d F Y", strtotime($trx->tanggal)); ?>
                </td>
                <td class="left">
                    <?php echo $trx->deskripsi; ?>
                </td>
                <td>
                    <?php echo $trx->referensi; ?>
                </td>
                <td style="text-align:right;">Rp.
                    <?php echo number_format($trx->debit, 2, ',', '.'); ?>
                </td>
                <td style="text-align:right;">Rp.
                    <?php echo number_format($trx->kredit, 2, ',', '.'); ?>
                </td>
                <td style="text-align:right;">Rp.
                    <?php echo number_format($running_balance, 2, ',', '.'); ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>