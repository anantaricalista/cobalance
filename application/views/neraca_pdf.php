<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Neraca</title>
    <style type="text/css">
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        h1,
        h3,
        h4 {
            text-align: center;
            margin: 0;
            padding: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #000;

        }

        td {
            border: 1px solid #000;
        }

        th {
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;

        }

        /* table,
        th,
        td {
            border: 1px solid #000;
            text-align: center;
            font-weight: bold;
        }

        th,
        td {
            padding: 4px;
            border: 1px solid #000;
        }

        th {
            background-color: #f2f2f2;
        } */

        tfoot tr {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Laporan Neraca (Balance Sheet)</h1>
    <h3>Periode:
        <?php echo date("d F Y", strtotime($end_date)); ?>
    </h3>

    <div style="width:33%; float:left;">
        <h4>Aset</h4>
        <table>
            <thead>
                <tr>
                    <th style="width:10%;">Kode</th>
                    <th style="width:45%;">Nama Akun</th>
                    <th style="width:45%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_aset = 0;
                if (!empty($aset)) {
                    foreach ($aset as $row) { ?>
                        <tr>
                            <td style="width:10%;">
                                <?php echo $row->kode_akun; ?>
                            </td>
                            <td style="width:45%;">
                                <?php echo $row->nama_akun; ?>
                            </td>
                            <td style="width:45%; text-align:right;">
                                <?php echo "Rp " . number_format($row->total_balance, 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php
                        $total_aset += floatval($row->total_balance);
                    }
                } else { ?>
                    <tr>
                        <td colspan="3" align="center">Data tidak ditemukan</td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" align="right">Total Aset</td>
                    <td align="right">
                        <?php echo "Rp " . number_format($total_aset, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="width:33%; float:left;">
        <h4>Liabilitas</h4>
        <table>
            <thead>
                <tr>
                    <th style="width:80px;">Kode Akun</th>
                    <th>Nama Akun</th>
                    <th style="width:150px; text-align:right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_liabilitas = 0;
                if (!empty($liabilitas)) {
                    foreach ($liabilitas as $row) { ?>
                        <tr>
                            <td>
                                <?php echo $row->kode_akun; ?>
                            </td>
                            <td>
                                <?php echo $row->nama_akun; ?>
                            </td>
                            <td align="right">
                                <?php echo "Rp " . number_format($row->total_balance, 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php
                        $total_liabilitas += floatval($row->total_balance);
                    }
                } else { ?>
                    <tr>
                        <td colspan="3" align="center">Data tidak ditemukan</td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" align="right">Total Liabilitas</td>
                    <td align="right">
                        <?php echo "Rp " . number_format($total_liabilitas, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <h4>Ekuitas</h4>
        <table>
            <thead>
                <tr>
                    <th style="width:80px;">Kode Akun</th>
                    <th>Nama Akun</th>
                    <th style="width:150px; text-align:right;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_ekuitas = 0;
                if (!empty($ekuitas)) {
                    foreach ($ekuitas as $row) { ?>
                        <tr>
                            <td>
                                <?php echo $row->kode_akun; ?>
                            </td>
                            <td>
                                <?php echo $row->nama_akun; ?>
                            </td>
                            <td align="right">
                                <?php echo "Rp " . number_format($row->total_balance, 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php
                        $total_ekuitas += floatval($row->total_balance);
                    }
                } else { ?>
                    <tr>
                        <td colspan="3" align="center">Data tidak ditemukan</td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" align="right">Total Ekuitas</td>
                    <td align="right">
                        <?php echo "Rp " . number_format($total_ekuitas, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="clear:both;"></div>
</body>

</html>