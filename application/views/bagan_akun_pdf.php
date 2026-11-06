<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Bagan Akun (Chart of Accounts)</title>
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
    table.report-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table.report-table th, table.report-table td {
      border: 1px solid #333;
      padding: 5px;
      text-align: center;
    }
    table.report-table th {
      background-color: #e0e0e0;
    }
    table.report-table td.left {
      text-align: left;
    }
  </style>
</head>
<body>
  <table class="header-table">
    <tr>
      <td style="text-align:center;"><h1>Bagan Akun (Chart of Accounts)</h1></td>
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
    $periode = format_date_indo($end_date);
    ?>
    <p><strong>Periode:</strong> <?php echo $periode; ?></p>
  </div>
  
  <table class="report-table">
    <thead>
      <tr>
        <th style="width:60px;">No</th>
        <th style="width:100px;">Kode Akun</th>
        <th>Nama Akun</th>
        <th style="width:120px;">Tipe Akun</th>
        <th style="width:150px;">Saldo</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1; ?>
      <?php foreach ($accounts as $row): ?>
          <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row->kode_akun; ?></td>
            <td style="text-align:left;"><?php echo ($row->id_akun_induk ? '&nbsp;&nbsp;&nbsp;' : '') . $row->nama_akun; ?></td>
            <td><?php echo $row->tipe_akun; ?></td>
            <td style="text-align:right;">Rp <?php echo number_format($row->saldo, 2, ',', '.'); ?></td>
          </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  
</body>
</html>
