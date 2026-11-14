<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Co-Balance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/img/apple-touch-icon.png') ?>">
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS (jika digunakan) -->
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/select2/css/select2.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/account-search.css') ?>" rel="stylesheet">
    <script defer src="<?= base_url('assets/js/account-search.js') ?>"></script>
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
            margin-bottom: 70px;
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

        .transaction-ui .content-container > form:not(.form-inline) {
            margin: 18px 0 22px;
            padding: 22px;
            border: 1px solid #e0e7f0;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 4px 14px rgba(27, 49, 79, .05);
        }

        .transaction-ui .content-container > form:not(.form-inline) fieldset {
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

        .transaction-ui .journal-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }

        .transaction-ui #table th:last-child,
        .transaction-ui #table td:last-child {
            min-width: 190px;
            text-align: center;
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

        .transaction-ui .history-search-group {
            display: flex;
            align-items: center;
            gap: 7px;
            width: min(100%, 520px);
        }

        .transaction-ui .history-search-group .form-control {
            flex: 1 1 260px;
            max-width: none;
        }

        .transaction-ui .history-clear {
            flex-shrink: 0;
        }

        .transaction-ui .history-status-filter {
            flex: 0 0 165px;
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

        .transaction-ui .module-title {
            margin-top: 8px;
            padding: 18px 22px 14px;
            border: 1px solid #e0e7f0;
            border-bottom: 0;
            border-radius: 10px 10px 0 0;
            background: #fff;
        }

        .transaction-ui .module-intro {
            display: flex;
            flex-wrap: wrap;
            gap: 5px 22px;
            margin: 0 0 22px;
            padding: 0 22px 16px;
            border: 1px solid #e0e7f0;
            border-top: 0;
            border-radius: 0 0 10px 10px;
            background: #fff;
            color: #63738a;
        }

        .transaction-ui .module-intro p {
            flex: 1 1 280px;
            margin: 0;
            line-height: 1.6;
        }

        .transaction-ui .module-intro p:only-child {
            flex-basis: 100%;
        }

        .transaction-ui .module-section-title {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 25px;
            padding: 10px 13px;
            border-left: 3px solid #54a2ae;
            border-radius: 0 6px 6px 0;
            background: #edf4f7;
        }

        .transaction-ui .module-section-title + p {
            color: #6d7d91;
        }

        .transaction-ui .module-section-title + .table-responsive,
        .transaction-ui .module-section-title + form {
            margin-top: 10px;
        }

        .transaction-ui form[id*="cancel"] {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 10px;
            margin: 14px 0 22px;
            padding: 16px;
            border: 1px solid #efd9d7;
            border-radius: 8px;
            background: #fff9f8;
        }

        .transaction-ui form[id*="cancel"] .form-group {
            margin: 0;
        }

        .transaction-ui form[id*="cancel"] label {
            display: block;
            margin-bottom: 5px;
        }

        .transaction-ui .opening-cancel-panel form[id*="cancel"] {
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .transaction-ui table.table-hover > tbody > tr:hover {
            background-color: #edf6f8;
        }

        .transaction-ui input[type="file"] {
            max-width: 100%;
            padding: 8px;
            border: 1px dashed #c8d5e3;
            border-radius: 6px;
            background: #fafcff;
        }

        .transaction-ui button[type="submit"] .glyphicon,
        .transaction-ui .btn .glyphicon {
            margin-right: 4px;
        }

        .transaction-ui .opening-overview {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 18px;
            margin: 18px 0;
            padding: 24px;
            border: 1px solid #dfe8ef;
            border-radius: 12px;
            background: linear-gradient(120deg, #fff 30%, #f0f8f8);
            box-shadow: 0 5px 18px rgba(27, 49, 79, .05);
        }

        .transaction-ui .opening-overview-copy {
            max-width: 690px;
        }

        .transaction-ui .opening-eyebrow {
            display: block;
            margin-bottom: 7px;
            color: #52818d;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .1em;
        }

        .transaction-ui .opening-overview h4,
        .transaction-ui .opening-form-heading h4 {
            margin: 0 0 8px;
            color: #183153;
            font-size: 21px;
            font-weight: 700;
        }

        .transaction-ui .opening-overview-copy p {
            margin: 0;
            color: #63738a;
            line-height: 1.6;
        }

        .transaction-ui .opening-status-card {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 240px;
            padding: 16px;
            border: 1px solid #e0e9ef;
            border-radius: 9px;
            background: rgba(255, 255, 255, .85);
        }

        .transaction-ui .opening-status-card > div {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        .transaction-ui .opening-status-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 50%;
            background: #e8f4f4;
            color: #387c89;
            font-size: 18px;
        }

        .transaction-ui .opening-status-card strong {
            color: #273b55;
            font-size: 15px;
        }

        .transaction-ui .opening-guidance {
            margin: 18px 0 22px;
            padding: 18px 20px;
            border: 1px solid #d8e7ed;
            border-radius: 10px;
            background: #f0f7fa;
        }

        .transaction-ui .opening-guidance-title {
            margin-bottom: 14px;
            color: #27576a;
            font-weight: 700;
        }

        .transaction-ui .opening-guidance-title .glyphicon {
            margin-right: 5px;
        }

        .transaction-ui .opening-guidance-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px 22px;
        }

        .transaction-ui .opening-guidance-grid > div {
            display: flex;
            flex-direction: column;
            gap: 3px;
            color: #63738a;
            font-size: 13px;
            line-height: 1.5;
        }

        .transaction-ui .opening-guidance-grid strong {
            color: #344b62;
        }

        .transaction-ui .opening-legacy-note {
            margin: 16px 0;
            padding: 12px 15px;
            border-left: 3px solid #80a9d0;
            border-radius: 0 6px 6px 0;
            background: #f1f6fc;
            color: #506b89;
        }

        .transaction-ui .opening-legacy-note .glyphicon {
            margin-right: 6px;
        }

        .transaction-ui .opening-form {
            padding: 0 !important;
            overflow: hidden;
        }

        .transaction-ui .opening-form fieldset {
            padding: 0 22px 22px;
        }

        .transaction-ui .opening-form-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px 22px 16px;
            border-bottom: 1px solid #e6ecf2;
            background: #fbfcfe;
        }

        .transaction-ui .opening-form-heading h4 {
            margin-bottom: 0;
        }

        .transaction-ui .opening-date-label {
            padding: 8px 11px;
            border: 1px solid #e0e8ef;
            border-radius: 6px;
            background: #fff;
            color: #52647a;
            font-size: 13px;
        }

        .transaction-ui .opening-date-label .glyphicon {
            margin-right: 5px;
            color: #538693;
        }

        .transaction-ui .opening-basics {
            display: grid;
            grid-template-columns: minmax(190px, 1fr) minmax(280px, 2fr);
            gap: 15px;
            padding: 0 22px;
        }

        .transaction-ui .opening-journal-preview {
            margin: 0 22px 18px;
            padding: 15px;
            border: 1px solid #dfe8ef;
            border-radius: 8px;
            background: #f7fafc;
        }

        .transaction-ui .opening-preview-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .transaction-ui .opening-preview-heading strong {
            color: #30455f;
        }

        .transaction-ui .opening-preview-account {
            overflow: hidden;
            max-width: 55%;
            padding: 5px 9px;
            border-radius: 15px;
            background: #e8f1f5;
            color: #426374;
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .transaction-ui .opening-preview-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .transaction-ui .opening-preview-grid > div {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
            padding: 10px;
            border: 1px solid #e8edf2;
            border-radius: 6px;
            background: #fff;
        }

        .transaction-ui .opening-preview-grid span {
            color: #718198;
            font-size: 11px;
            line-height: 1.4;
        }

        .transaction-ui .opening-preview-grid strong {
            overflow: hidden;
            color: #344b62;
            font-size: 15px;
            text-overflow: ellipsis;
        }

        .transaction-ui .opening-preview-grid .opening-preview-total {
            border-color: #cee3d4;
            background: #f4faf5;
        }

        .transaction-ui .opening-preview-total strong {
            color: #23643a;
        }

        .transaction-ui .opening-section {
            margin-top: 19px;
            padding: 2px 16px 16px;
            border: 1px solid #e4eaf1;
            border-radius: 9px;
            background: #fff;
        }

        .transaction-ui .opening-section-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin: 0 -1px 5px;
            padding: 12px 2px 11px;
            border-bottom: 1px solid #edf0f4;
        }

        .transaction-ui .opening-section-heading h4 {
            margin: 0 0 4px;
        }

        .transaction-ui .opening-section-hint {
            display: block;
            color: #77869a;
            font-size: 12px;
            line-height: 1.5;
        }

        .transaction-ui .opening-row-count {
            flex-shrink: 0;
            padding: 5px 9px;
            border-radius: 16px;
            background: #eef3f7;
            color: #52647a;
            font-size: 12px;
            font-weight: 600;
        }

        .transaction-ui .opening-section .table-responsive {
            margin: 12px 0;
        }

        .transaction-ui .opening-section .table {
            min-width: 650px;
        }

        .transaction-ui .opening-section[data-opening-kind="aset"] .table {
            min-width: 1500px;
        }

        .transaction-ui .opening-section .form-control {
            min-width: 105px;
        }

        .transaction-ui .opening-empty-row td {
            height: 58px;
            background: #fbfcfe;
        }

        .transaction-ui .opening-save-bar,
        .transaction-ui .opening-action-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
            padding: 16px 18px;
            border: 1px solid #e0e7f0;
            border-radius: 9px;
            background: #f8fafc;
        }

        .transaction-ui .opening-save-bar .text-muted,
        .transaction-ui .opening-action-panel span {
            color: #718198;
            font-size: 13px;
        }

        .transaction-ui .opening-action-panel {
            margin: 18px 0 26px;
            border-color: #d7e8dc;
            background: #f2f8f4;
        }

        .transaction-ui .opening-action-panel > div {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .transaction-ui .opening-cancel-panel {
            margin: 20px 0;
            padding: 18px;
            border: 1px solid #f0d7d5;
            border-radius: 9px;
            background: #fff9f8;
        }

        .transaction-ui .opening-cancel-panel h4 {
            margin: 0 0 8px;
            color: #8d4944;
        }

        .transaction-ui .opening-cancel-panel p {
            color: #765d5a;
            line-height: 1.55;
        }

        .transaction-ui .opening-cancel-panel .form-inline .form-group {
            margin: 8px 10px 8px 0;
        }

        .transaction-ui .opening-cancel-panel label {
            display: block;
            margin-bottom: 4px;
        }

        @media (max-width: 767px) {
            .transaction-ui h3 {
                font-size: 23px;
            }

            .transaction-ui .content-container > form:not(.form-inline) {
                padding: 15px;
            }

            .transaction-ui .history-tools {
                align-items: stretch;
                flex-direction: column;
            }

            .transaction-ui .history-tools .form-control {
                max-width: none;
            }

            .transaction-ui .history-search-group {
                width: 100%;
            }

            .transaction-ui .history-status-filter {
                flex: initial;
                width: 100%;
            }

            .transaction-ui .opening-overview,
            .transaction-ui .opening-save-bar,
            .transaction-ui .opening-action-panel {
                align-items: stretch;
                flex-direction: column;
            }

            .transaction-ui .opening-status-card {
                min-width: 0;
            }

            .transaction-ui .opening-guidance-grid,
            .transaction-ui .opening-basics {
                grid-template-columns: minmax(0, 1fr);
            }

            .transaction-ui .opening-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transaction-ui .opening-form fieldset {
                padding: 0 14px 16px;
            }

            .transaction-ui .opening-form-heading {
                align-items: flex-start;
                flex-direction: column;
                padding: 16px;
            }

            .transaction-ui .opening-basics {
                padding: 0 14px;
            }

            .transaction-ui .opening-journal-preview {
                margin-right: 14px;
                margin-left: 14px;
            }

            .transaction-ui .opening-section {
                padding-right: 10px;
                padding-left: 10px;
            }

            .transaction-ui .opening-section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .transaction-ui .opening-save-bar .btn {
                width: 100%;
            }
        }
    </style>
    <link href="<?= base_url('assets/css/cobalance-layout.css') ?>" rel="stylesheet">
</head>

<body class="cb-layout <?php echo in_array($this->router->fetch_class(), array(
    'penjualan', 'penerimaan_piutang', 'pembelian', 'pembayaran_utang', 'kas_bank', 'aset_tetap',
    'persediaan', 'penyesuaian_persediaan', 'usulan_opname', 'retur_persediaan', 'modal_prive', 'penyesuaian_akuntansi',
    'saldo_awal', 'jurnal', 'beban_penjualan_jasa'
), true) ? 'transaction-ui' : ''; ?>">
    <header class="cb-topbar">
        <button type="button" class="cb-menu-toggle" aria-label="Buka menu navigasi" aria-controls="cb-sidebar" aria-expanded="false">
            <span class="glyphicon glyphicon-menu-hamburger" aria-hidden="true"></span>
        </button>
        <div class="cb-topbar-title">Ruang Kerja <strong>Co-Balance</strong></div>
        <div class="cb-topbar-company"><?= html_escape($this->session->userdata('nama_perusahaan')) ?></div>
        <a class="cb-topbar-logout" href="<?= site_url('auth/logout') ?>" title="Keluar">
            <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span><span class="cb-logout-label"> Keluar</span>
        </a>
    </header>
    <div class="cb-sidebar-backdrop" hidden></div>
    <!-- Navigation Bar -->
    <nav id="cb-sidebar" class="navbar navbar-default sticky-header cb-sidebar" aria-label="Navigasi utama">
        <div class="container-fluid">
            <!-- Brand and toggle -->
            <div class="navbar-header">
                <a class="navbar-brand" href="<?php echo site_url(isset(MY_Controller::$role_scope[$this->session->userdata('role')]) ? MY_Controller::$role_scope[$this->session->userdata('role')]['home'] : ''); ?>"><span class="cb-brand-icon"><img src="<?= base_url('assets/img/cobalance-logo.png') ?>" alt=""></span><span>Co-Balance<small>Ruang kerja keuangan</small></span></a>
            </div>
            <!-- Menu Items -->
            <div class="navbar-collapse" id="navbar-menu">
                <?php if ($this->session->userdata('role') === 'gudang'): ?>
                <ul class="nav navbar-nav">
                    <li <?php if ($this->router->fetch_class() == 'beranda') echo 'class="active"'; ?>><a href="<?php echo site_url('gudang/beranda'); ?>"><span class="glyphicon glyphicon-home cb-nav-icon" aria-hidden="true"></span>Beranda</a></li>
                    <li <?php if ($this->router->fetch_class() == 'stok') echo 'class="active"'; ?>><a href="<?php echo site_url('gudang/stok'); ?>"><span class="glyphicon glyphicon-inbox cb-nav-icon" aria-hidden="true"></span>Stok Barang</a></li>
                    <li <?php if ($this->router->fetch_class() == 'opname') echo 'class="active"'; ?>><a href="<?php echo site_url('gudang/opname'); ?>"><span class="glyphicon glyphicon-check cb-nav-icon" aria-hidden="true"></span>Stock Opname</a></li>
                </ul>
                <?php elseif ($this->session->userdata('role') === 'purchasing'): ?>
                <ul class="nav navbar-nav">
                    <li <?php if ($this->router->fetch_directory() == 'purchasing/' && $this->router->fetch_class() == 'beranda') echo 'class="active"'; ?>><a href="<?php echo site_url('purchasing/beranda'); ?>"><span class="glyphicon glyphicon-home cb-nav-icon" aria-hidden="true"></span>Beranda</a></li>
                    <li <?php if ($this->router->fetch_class() == 'pembelian') echo 'class="active"'; ?>><a href="<?php echo site_url('pembelian'); ?>"><span class="glyphicon glyphicon-shopping-cart cb-nav-icon" aria-hidden="true"></span>Pembelian</a></li>
                    <li <?php if ($this->router->fetch_class() == 'pembayaran_utang') echo 'class="active"'; ?>><a href="<?php echo site_url('pembayaran_utang'); ?>"><span class="glyphicon glyphicon-credit-card cb-nav-icon" aria-hidden="true"></span>Pembayaran Utang</a></li>
                    <li <?php if ($this->router->fetch_class() == 'pemasok') echo 'class="active"'; ?>><a href="<?php echo site_url('pemasok'); ?>"><span class="glyphicon glyphicon-briefcase cb-nav-icon" aria-hidden="true"></span>Pemasok</a></li>
                    <li <?php if ($this->router->fetch_class() == 'produk') echo 'class="active"'; ?>><a href="<?php echo site_url('produk'); ?>"><span class="glyphicon glyphicon-tags cb-nav-icon" aria-hidden="true"></span>Produk</a></li>
                </ul>
                <?php else: ?>
                <ul class="nav navbar-nav">
                    <li <?php if ($this->router->fetch_class() == 'dashboard') echo 'class="active"'; ?>><a href="<?= site_url('dashboard') ?>"><span class="glyphicon glyphicon-home cb-nav-icon" aria-hidden="true"></span>Beranda</a></li>
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('akun', 'pengguna', 'pelanggan', 'pemasok', 'produk', 'pajak', 'pengaturan_akun')))
                        echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            <span class="glyphicon glyphicon-th-large cb-nav-icon" aria-hidden="true"></span>Master <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'akun')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('akun'); ?>"><span class="glyphicon glyphicon-list-alt cb-nav-icon" aria-hidden="true"></span>Akun</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pengguna')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pengguna'); ?>"><span class="glyphicon glyphicon-user cb-nav-icon" aria-hidden="true"></span>Pengguna</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pelanggan')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pelanggan'); ?>"><span class="glyphicon glyphicon-user cb-nav-icon" aria-hidden="true"></span>Pelanggan</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pemasok')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pemasok'); ?>"><span class="glyphicon glyphicon-briefcase cb-nav-icon" aria-hidden="true"></span>Pemasok</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'produk') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('produk'); ?>"><span class="glyphicon glyphicon-tags cb-nav-icon" aria-hidden="true"></span>Produk dan Jasa</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pajak') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pajak'); ?>"><span class="glyphicon glyphicon-usd cb-nav-icon" aria-hidden="true"></span>Pajak</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'pengaturan_akun') echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('pengaturan_akun'); ?>"><span class="glyphicon glyphicon-cog cb-nav-icon" aria-hidden="true"></span>Pengaturan Akun</a>
                            </li>
                        </ul>
                    </li>
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('saldo_awal', 'penjualan', 'beban_penjualan_jasa', 'penerimaan_piutang', 'pembelian', 'pembayaran_utang', 'kas_bank', 'aset_tetap', 'persediaan', 'penyesuaian_persediaan', 'usulan_opname', 'retur_persediaan', 'modal_prive', 'penyesuaian_akuntansi'))) echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            <span class="glyphicon glyphicon-transfer cb-nav-icon" aria-hidden="true"></span>Transaksi <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'penjualan') echo 'class="active"'; ?>><a href="<?php echo site_url('penjualan'); ?>"><span class="glyphicon glyphicon-shopping-cart cb-nav-icon" aria-hidden="true"></span>Penjualan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'beban_penjualan_jasa') echo 'class="active"'; ?>><a href="<?php echo site_url('beban_penjualan_jasa'); ?>"><span class="glyphicon glyphicon-wrench cb-nav-icon" aria-hidden="true"></span>Beban Penjualan Jasa</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penerimaan_piutang') echo 'class="active"'; ?>><a href="<?php echo site_url('penerimaan_piutang'); ?>"><span class="glyphicon glyphicon-download-alt cb-nav-icon" aria-hidden="true"></span>Penerimaan Piutang</a></li>
                            <li <?php if ($this->router->fetch_class() == 'pembelian') echo 'class="active"'; ?>><a href="<?php echo site_url('pembelian'); ?>"><span class="glyphicon glyphicon-shopping-cart cb-nav-icon" aria-hidden="true"></span>Pembelian</a></li>
                            <li <?php if ($this->router->fetch_class() == 'pembayaran_utang') echo 'class="active"'; ?>><a href="<?php echo site_url('pembayaran_utang'); ?>"><span class="glyphicon glyphicon-credit-card cb-nav-icon" aria-hidden="true"></span>Pembayaran Utang</a></li>
                            <li <?php if ($this->router->fetch_class() == 'kas_bank') echo 'class="active"'; ?>><a href="<?php echo site_url('kas_bank'); ?>"><span class="glyphicon glyphicon-credit-card cb-nav-icon" aria-hidden="true"></span>Kas dan Bank</a></li>
                            <li <?php if ($this->router->fetch_class() == 'aset_tetap') echo 'class="active"'; ?>><a href="<?php echo site_url('aset_tetap'); ?>"><span class="glyphicon glyphicon-briefcase cb-nav-icon" aria-hidden="true"></span>Aset Tetap</a></li>
                            <li <?php if ($this->router->fetch_class() == 'persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('persediaan'); ?>"><span class="glyphicon glyphicon-inbox cb-nav-icon" aria-hidden="true"></span>Persediaan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penyesuaian_persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('penyesuaian_persediaan'); ?>"><span class="glyphicon glyphicon-edit cb-nav-icon" aria-hidden="true"></span>Penyesuaian Persediaan</a></li>
                            <li <?php if ($this->router->fetch_class() == 'usulan_opname') echo 'class="active"'; ?>><a href="<?php echo site_url('usulan_opname'); ?>"><span class="glyphicon glyphicon-check cb-nav-icon" aria-hidden="true"></span>Usulan Opname Gudang</a></li>
                            <li <?php if ($this->router->fetch_class() == 'retur_persediaan') echo 'class="active"'; ?>><a href="<?php echo site_url('retur_persediaan'); ?>"><span class="glyphicon glyphicon-retweet cb-nav-icon" aria-hidden="true"></span>Retur Transaksi</a></li>
                            <li <?php if ($this->router->fetch_class() == 'modal_prive') echo 'class="active"'; ?>><a href="<?php echo site_url('modal_prive'); ?>"><span class="glyphicon glyphicon-usd cb-nav-icon" aria-hidden="true"></span>Modal dan Prive</a></li>
                            <li <?php if ($this->router->fetch_class() == 'penyesuaian_akuntansi') echo 'class="active"'; ?>><a href="<?php echo site_url('penyesuaian_akuntansi'); ?>"><span class="glyphicon glyphicon-edit cb-nav-icon" aria-hidden="true"></span>Penyesuaian Akuntansi</a></li>
                            <li <?php if ($this->router->fetch_class() == 'saldo_awal') echo 'class="active"'; ?>><a href="<?php echo site_url('saldo_awal'); ?>"><span class="glyphicon glyphicon-flag cb-nav-icon" aria-hidden="true"></span>Saldo Awal</a></li>
                        </ul>
                    </li>
                    <li <?php if ($this->router->fetch_class() == 'jurnal')
                        echo 'class="active"'; ?>>
                        <a href="<?php echo site_url('jurnal'); ?>"><span class="glyphicon glyphicon-book cb-nav-icon" aria-hidden="true"></span>Jurnal Transaksi</a>
                    </li>
                    <li class="dropdown <?php if (in_array($this->router->fetch_class(), array('buku_besar', 'jurnal_umum', 'laporan', 'laba_rugi', 'neraca', 'bagan_akun')))
                        echo 'active'; ?>">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
                            aria-expanded="false">
                            <span class="glyphicon glyphicon-stats cb-nav-icon" aria-hidden="true"></span>Laporan <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li <?php if ($this->router->fetch_class() == 'buku_besar')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('buku_besar'); ?>"><span class="glyphicon glyphicon-book cb-nav-icon" aria-hidden="true"></span>Buku Besar</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'jurnal_umum')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('jurnal_umum'); ?>"><span class="glyphicon glyphicon-list-alt cb-nav-icon" aria-hidden="true"></span>Jurnal Umum</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'laporan')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('laporan'); ?>"><span class="glyphicon glyphicon-file cb-nav-icon" aria-hidden="true"></span>Laporan Akuntansi</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'laba_rugi')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('laba_rugi'); ?>"><span class="glyphicon glyphicon-signal cb-nav-icon" aria-hidden="true"></span>Laba Rugi</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'neraca')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('neraca'); ?>"><span class="glyphicon glyphicon-stats cb-nav-icon" aria-hidden="true"></span>Neraca</a>
                            </li>
                            <li <?php if ($this->router->fetch_class() == 'bagan_akun')
                                echo 'class="active"'; ?>>
                                <a href="<?php echo site_url('bagan_akun'); ?>"><span class="glyphicon glyphicon-th-list cb-nav-icon" aria-hidden="true"></span>Bagan Akun</a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
    <main class="container content-container">


        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script src="<?= base_url('assets/vendor/select2/js/select2.min.js') ?>"></script>
        <script src="<?= base_url('assets/vendor/autonumeric/autoNumeric.min.js') ?>"></script>
        <?php $this->load->view('template/money_input'); ?>
        <!-- Bootstrap JS -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <!-- DataTable JS -->
        <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
        <script src="<?= base_url('assets/js/cobalance-layout.js') ?>"></script>
        <?php $this->load->view('template/transaction_ui'); ?>