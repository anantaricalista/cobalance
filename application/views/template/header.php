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
    </style>
</head>

<body>
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
                    <li <?php if ($this->router->fetch_class() == 'akun')
                        echo 'class="active"'; ?>>
                        <a href="<?php echo site_url('akun'); ?>">Akun</a>
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
        <!-- Bootstrap JS -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <!-- DataTable JS -->
        <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>