<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
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

        table.laporan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.laporan-table th,
        table.laporan-table td {
            border: 1px solid #333;
            padding: 5px;
            text-align: center;
        }

        table.laporan-table th {
            background-color: #e0e0e0;
        }

        table.laporan-table td.left {
            text-align: left;
        }

        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="text-align:center;">
                <h1>Laporan Laba Rugi</h1>
            </td>
        </tr>
    </table>
    <div class="info">
        <?php
        function format_date_indo($date)
        {
            $months = array(
                '01' => 'Januari',
                '02' => 'Februari',
                '03' => 'Maret',
                '04' => 'April',
                '05' => 'Mei',
                '06' => 'Juni',
                '07' => 'Juli',
                '08' => 'Agustus',
                '09' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Desember'
            );
            $parts = explode('-', $date);
            return intval($parts[2]) . ' ' . $months[$parts[1]] . ' ' . $parts[0];
        }

        $periode = format_date_indo($start_date) . " s/d " . format_date_indo($end_date);
        ?>
        <p><strong>Periode:</strong> <?php echo $periode; ?>
        </p>
    </div>

    <!-- Bagian Pendapatan -->
    <h3>Pendapatan</h3>
    <table class="laporan-table">
        <thead>
            <tr>
                <th style="width:80px;">Kode Akun</th>
                <th>Nama Akun</th>
                <th style="width:150px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($pendapatan)): ?>
                <?php foreach ($pendapatan as $row): ?>
                    <tr>
                        <td>
                            <?php echo $row->kode_akun; ?>
                        </td>
                        <td class="left">
                            <?php echo $row->nama_akun; ?>
                        </td>
                        <td style="text-align:right;">Rp
                            <?php echo number_format($row->amount, 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center;">Data tidak ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="2" style="text-align:right;">Total Pendapatan</td>
                <td style="text-align:right;">Rp
                    <?php echo number_format($total_pendapatan, 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Bagian Beban -->
    <h3>Beban</h3>
    <table class="laporan-table">
        <thead>
            <tr>
                <th style="width:80px;">Kode Akun</th>
                <th>Nama Akun</th>
                <th style="width:150px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($beban)): ?>
                <?php foreach ($beban as $row): ?>
                    <tr>
                        <td>
                            <?php echo $row->kode_akun; ?>
                        </td>
                        <td class="left">
                            <?php echo $row->nama_akun; ?>
                        </td>
                        <td style="text-align:right;">Rp
                            <?php echo number_format($row->amount, 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center;">Data tidak ditemukan</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="2" style="text-align:right;">Total Beban</td>
                <td style="text-align:right;">Rp
                    <?php echo number_format($total_beban, 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Laba Bersih -->
    <h3 style="text-align:right;">Laba Bersih: Rp
        <?php echo number_format($laba_bersih, 2, ',', '.'); ?>
    </h3>

    <div class="footer">
        <p>Generated by Sistem Akuntansi &mdash;
            <?php echo date('Y'); ?>
        </p>
    </div>
</body>

</html>