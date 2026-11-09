<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Co-Balance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS (jika digunakan) -->
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <style>
        /* Sticky header */
        .sticky-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #66CCCC;
            /* Hijau tosca soft */
            border-color: #66CCCC;
        }

        .sticky-header .navbar-brand,
        .sticky-header .navbar-nav>li>a {
            color: #ffffff;
        }

        .sticky-header .navbar-nav>li>a:hover,
        .sticky-header .navbar-nav>li.active>a {
            background-color: rgba(0, 0, 0, 0.1);
            color: #ffffff;
        }

        /* Sticky footer */
        .sticky-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: #66CCCC;
            padding: 15px 0;
        }

        .sticky-footer p {
            margin: 0;
            color: #ffffff;
            text-align: center;
        }

        /* Ruang untuk konten utama agar tidak tertutup header & footer */
        .content-container {
            margin-top: 70px;
            /* Sesuaikan dengan tinggi header */
            margin-bottom: 70px;
            /* Sesuaikan dengan tinggi footer */
        }

        .transaction-ui {
            background: #f4f7fb;
            color: #263248;
        }

        .transaction-ui .content-container {
            padding-top: 18px;
            padding-bottom: 28px;
            margin-bottom: 22px;
        }

        .transaction-ui h3 {
            margin: 8px 0 10px;
            padding-bottom: 12px;
            border-bottom: 1px solid #dfe7f1;
            color: #183153;
            font-size: 27px;
            font-weight: 700;
            letter-spacing: -.3px;
        }

        .transaction-ui h3 + p {
            max-width: 900px;
            margin-bottom: 20px;
            color: #63738a;
            line-height: 1.6;
        }

        .transaction-ui h4 {
            margin: 24px 0 12px;
            color: #243b5a;
            font-size: 18px;
            font-weight: 600;
        }

        .transaction-ui form[id$="-form"] {
            margin: 18px 0 22px;
            padding: 22px;
            border: 1px solid #e0e7f0;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 4px 14px rgba(27, 49, 79, .05);
        }

        .transaction-ui form[id$="-form"] fieldset {
            min-width: 0;
            border: 0;
        }

        .transaction-ui .form-group label {
            color: #42536b;
            font-weight: 600;
        }

        .transaction-ui .form-control {
            min-height: 40px;
            border-color: #d6dfeb;
            border-radius: 6px;
            box-shadow: none;
        }

        .transaction-ui .form-control:focus {
            border-color: #4a93a5;
            box-shadow: 0 0 0 3px rgba(74, 147, 165, .14);
        }

        .transaction-ui .table-responsive {
            margin: 12px 0 22px;
            border: 1px solid #e0e7f0;
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 3px 12px rgba(27, 49, 79, .04);
        }

        .transaction-ui .table {
            margin-bottom: 0;
            background: #fff;
        }

        .transaction-ui .table > thead > tr > th {
            border-bottom: 1px solid #dce5ef;
            background: #f5f8fc;
            color: #42536b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .035em;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .transaction-ui .table > tbody > tr > td {
            vertical-align: middle;
        }

        .transaction-ui .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: #fbfcfe;
        }

        .transaction-ui .table-hover > tbody > tr:hover,
        .transaction-ui .table-striped > tbody > tr:hover {
            background-color: #f0f7fa;
        }

        .transaction-ui .btn {
            border-radius: 6px;
            font-weight: 600;
            transition: box-shadow .15s ease, transform .15s ease;
        }

        .transaction-ui .btn:hover {
            box-shadow: 0 3px 8px rgba(27, 49, 79, .12);
            transform: translateY(-1px);
        }

        .transaction-ui .btn:disabled {
            transform: none;
        }

        .transaction-ui .history-tools {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 0 0 10px;
        }

        .transaction-ui .history-tools .form-control {
            max-width: 340px;
        }

        .transaction-ui .history-count {
            color: #718198;
            font-size: 13px;
            white-space: nowrap;
        }

        .transaction-ui .transaction-status {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 20px;
            background: #eef2f7;
            color: #516176;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .transaction-ui .transaction-status.is-draft {
            background: #fff4d6;
            color: #805c00;
        }

        .transaction-ui .transaction-status.is-posted {
            background: #e4f5ea;
            color: #23643a;
        }

        .transaction-ui .transaction-status.is-cancelled {
            background: #fde9e8;
            color: #a23c37;
        }

        .transaction-ui .alert {
            border-radius: 7px;
        }

        .transaction-ui .sticky-footer {
            position: static;
            margin-top: 28px;
        }

        @media (max-width: 767px) {
            .transaction-ui h3 {
                font-size: 23px;
            }

            .transaction-ui form[id$="-form"] {
                padding: 15px;
            }

            .transaction-ui .history-tools {
                align-items: stretch;
                flex-direction: column;
            }

            .transaction-ui .history-tools .form-control {
                max-width: none;
            }
        }
    </style>
</head>

<body class="<?php echo in_array($this->router->fetch_class(), array(
    'penjualan', 'penerimaan_piutang', 'pembelian', 'pembayaran_utang', 'kas_bank', 'aset_tetap',
    'persediaan', 'penyesuaian_persediaan', 'retur_persediaan', 'modal_prive', 'penyesuaian_akuntansi',
    'saldo_awal', 'jurnal'
), true) ? 'transaction-ui' : ''; ?>">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-default sticky-header">
        <div class="container-fluid">
            <!-- Brand and toggle -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-menu"
                    aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo site_url(); ?>">Co-Balance</a>
            </div>
            <!-- Menu Items -->
            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="nav navbar-nav">
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('akun', 'pelanggan', 'pemasok', 'produk', 'pajak', 'pengaturan_akun')))
                        echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            Master <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'akun')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('akun'); ?>">Akun</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pelanggan')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pelanggan'); ?>">Pelanggan</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pemasok')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pemasok'); ?>">Pemasok</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'produk') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('produk'); ?>">Produk dan Jasa</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pajak') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pajak'); ?>">Pajak</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pengaturan_akun') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pengaturan_akun'); ?>">Pengaturan Akun</a>
                            </li>
                        </ul>
                    </li>
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('saldo_awal', 'penjualan', 'penerimaan_piutang', 'pembelian', 'pembayaran_utang', 'kas_bank', 'aset_tetap', 'persediaan', 'penyesuaian_persediaan', 'retur_persediaan', 'modal_prive', 'penyesuaian_akuntansi'))) echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            Transaksi <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'penjualan') echo 'class="active"'; ?>><a href="<?php echo site_url('penjualan'); ?>">Penjualan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penerimaan_piutang') echo 'class="active"'; ?>><a href="<?php echo site_url('penerimaan_piutang'); ?>">Penerimaan Piutang</a></li>
                            <li <?php if ($this->router->fetch_class() == 'pembelian') echo 'class="active"'; ?>><a href="<?php echo site_url('pembelian'); ?>">Pembelian</a></li>
                            <li <?php if ($this->router->fetch_class() == 'pembayaran_utang') echo 'class="active"'; ?>><a href="<?php echo site_url('pembayaran_utang'); ?>">Pembayaran Utang</a></li>
                            <li <?php if ($this->router->fetch_class() == 'kas_bank') echo 'class="active"'; ?>><a href="<?php echo site_url('kas_bank'); ?>">Kas dan Bank</a></li>
                            <li <?php if ($this->router->fetch_class() == 'aset_tetap') echo 'class="active"'; ?>><a href="<?php echo site_url('aset_tetap'); ?>">Aset Tetap</a></li>
                            <li <?php if ($this->router->fetch_class() == 'persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('persediaan'); ?>">Persediaan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penyesuaian_persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('penyesuaian_persediaan'); ?>">Penyesuaian Persediaan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'retur_persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('retur_persediaan'); ?>">Retur Pembelian/Penjualan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'modal_prive') echo 'class="active"'; ?>><a href="<?php echo site_url('modal_prive'); ?>">Modal dan Prive</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penyesuaian_akuntansi') echo 'class="active"'; ?>><a href="<?php echo site_url('penyesuaian_akuntansi'); ?>">Penyesuaian Akuntansi</a></li>
                            <li <?php if ($this->router->fetch_class() == 'saldo_awal') echo 'class="active"'; ?>><a href="<?php echo site_url('saldo_awal'); ?>">Saldo Awal</a></li>
                        </ul>
                    </li>
                    <li <?php if ($this->router->fetch_class() == 'jurnal')
                        echo 'class="active"'; ?>>
                        <a href="<?php echo site_url('jurnal'); ?>">Jurnal Transaksi</a>
                    </li>
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('buku_besar', 'jurnal_umum', 'laporan', 'laba_rugi', 'neraca', 'bagan_akun')))
                        echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            Laporan <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'buku_besar')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('buku_besar'); ?>">Buku Besar</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'jurnal_umum')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('jurnal_umum'); ?>">Jurnal Umum</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'laporan')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('laporan'); ?>">Laporan Akuntansi</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'laba_rugi')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('laba_rugi'); ?>">Laba Rugi</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'neraca')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('neraca'); ?>">Neraca</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'bagan_akun')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('bagan_akun'); ?>">Bagan Akun</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <!-- Right-side: tampilkan nama perusahaan dan logout (hanya ikon) -->
                <ul class="nav navbar-nav navbar-right">
                    <li>
                        <a href="javascript:void(0)" style="cursor: default;">
                            <?php echo $this->session->userdata('nama_perusahaan'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo site_url('auth/logout'); ?>" title="Logout">
                            <i class="glyphicon glyphicon-log-out"></i>
                        </a>
                    </li>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
    <div class="container content-container">


        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <?php $this->load->view('template/money_input'); ?>
        <!-- Bootstrap JS -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <!-- DataTable JS -->
        <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
        <?php $this->load->view('template/transaction_ui'); ?>