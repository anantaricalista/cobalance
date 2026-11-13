<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Jurnal Transaksi</title>
    <style type="text/css">
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            margin: 20px;
            color: #333;
        }

        /* 
        table.header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        } */

        table.header-table td {
            border: none;
            padding: 3px 0;
        }

        h1 {
            font-size: 16pt;
            margin: 0;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .info-table td {
            border: none;
            padding: 2px 0;
            font-size: 11pt;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }

        .table th {
            background-color: #e0e0e0;
        }

        .table td.left {
            text-align: left;
        }

        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 30px;
            color: #666;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td style="text-align:center;" colspan="2">
                <h1>Jurnal Transaksi</h1>
            </td>
        </tr>
        <tr>
            <td style="width:28%; text-align: left;">
                <strong>Tanggal</strong>
            </td>
            <td>
                <?php echo ": " . html_escape($jurnal->tanggal); ?>
            </td>
        </tr>
        <tr>
            <td style="width:15%; text-align: left;">
                <strong>Referensi</strong>
            </td>
            <td>
                <?php echo ": " . html_escape($jurnal->referensi); ?>
            </td>
        </tr>
        <tr>
            <td style="width:15%; text-align: left;">
                <strong>Deskripsi</strong>
            </td>
            <td>
                <?php echo ": " . html_escape($jurnal->deskripsi); ?>
            </td>
        </tr>
        <?php foreach (array(
            'Nomor transaksi' => $info['nomor'],
            'Jenis transaksi' => $info['jenis_transaksi'],
            'Pihak' => $info['pihak'],
            'Jenis jurnal' => $info['jenis_jurnal'],
            'Status transaksi' => $info['status_transaksi'],
            'Keterangan transaksi' => $info['deskripsi_transaksi'],
            'Pembuat draft' => $info['pembuat'],
            'Diposting oleh' => $info['poster'],
            'Waktu posting' => $info['waktu_posting'],
            'Dibatalkan oleh' => $info['pembatal'],
            'Waktu pembatalan' => $info['waktu_pembatalan'],
            'Alasan pembatalan' => $info['alasan_pembatalan']
        ) as $label => $value): ?>
        <tr><td style="text-align: left;"><strong><?= html_escape($label) ?></strong></td>
            <td>: <?= html_escape($value) ?></td></tr>
        <?php endforeach; ?>
    </table>
    <br>
    <br>
    <table class="table">
        <tr>
            <td style="width:5%;"><b>No.</b></td>
            <td style="width:35%;"><b>Akun</b></td>
            <td style="width:30%;"><b>Debit</b></td>
            <td style="width:30%;"><b>Kredit</b></td>
        </tr>
        <?php
        $no = 1;
        $totalDebit = '0.00';
        $totalKredit = '0.00';
        foreach ($jurnal->detail as $detail):
            $totalDebit = jumlah_nominal($totalDebit, $detail->debit);
            $totalKredit = jumlah_nominal($totalKredit, $detail->kredit);
            ?>
            <tr>
                <td>
                    <?php echo $no++; ?>
                </td>
                <td class="left">
                    <?php echo html_escape($detail->nama_akun . ' (' . $detail->kode_akun . ')'); ?>
                </td>
                <td style="text-align: right;">
                    <?php echo format_nominal($detail->debit === null ? '0' : $detail->debit); ?>
                </td>
                <td style="text-align: right;">
                    <?php echo format_nominal($detail->kredit === null ? '0' : $detail->kredit); ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">Total</td>
                <td style="text-align: right;">
                    <?php echo format_nominal($totalDebit); ?>
                </td>
                <td style="text-align: right;">
                    <?php echo format_nominal($totalKredit); ?>
                </td>
            </tr>
        </tfoot>
    </table>
    <div class="footer">
        <p>Generated by Sistem Akuntansi &mdash;
            <?php echo date('Y'); ?>
        </p>
    </div>
</body>

</html>