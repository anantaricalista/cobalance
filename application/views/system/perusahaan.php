<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Admin - Co-Balance</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/img/favicon-32.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('assets/img/apple-touch-icon.png') ?>">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; color: #263248; }
        .system-bar { background: #243444; color: #fff; padding: 18px 0; box-shadow: 0 3px 14px rgba(23,38,55,.14); }
        .system-bar h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .system-bar p { margin: 4px 0 0; color: #d7eff0; }
        .system-brand { display: flex; align-items: center; gap: 12px; }
        .system-brand-logo { display: flex; flex: 0 0 44px; align-items: center; justify-content: center; width: 44px; height: 44px; padding: 6px; border-radius: 11px; background: #fff; }
        .system-brand-logo img { width: 100%; height: 100%; object-fit: contain; }
        .system-brand-text { min-width: 0; }
        @media (max-width: 480px) { .system-bar h1 { font-size: 17px; } .system-bar p { font-size: 12px; } }
        .system-content { padding-top: 25px; padding-bottom: 30px; }
        .system-panel { background: white; border: 1px solid #e1e8f0; border-radius: 10px; margin-bottom: 22px; padding: 24px; box-shadow: 0 4px 14px rgba(27,49,79,.05); }
        .system-panel h2 { margin: 0 0 8px; color: #183153; font-size: 19px; font-weight: 700; }
        .system-panel .help { margin-bottom: 20px; color: #687a91; }
        .system-panel label { color: #42536b; }
        .system-panel .form-control { border-color: #d6dfe8; border-radius: 6px; box-shadow: none; }
        .system-panel .form-control:focus { border-color: #4a93a5; box-shadow: 0 0 0 3px rgba(74,147,165,.13); }
        .system-panel .btn-primary { border-color: #347e85; border-radius: 6px; background: #347e85; }
        .system-panel .btn-primary:hover, .system-panel .btn-primary:focus { border-color: #28666c; background: #28666c; }
        .system-panel .table { margin-bottom: 0; }
        .system-panel .table th { white-space: nowrap; }
    </style>
</head>
<body>
    <header class="system-bar">
        <div class="container">
            <form class="pull-right" action="<?= site_url('system/perusahaan/logout') ?>" method="post">
                <input type="hidden" name="system_form_token" value="<?= html_escape($form_token) ?>">
                <?php if ($this->config->item('csrf_protection')): ?>
                    <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                <?php endif; ?>
                <button class="btn btn-default btn-sm" type="submit">Keluar</button>
            </form>
            <div class="system-brand">
                <span class="system-brand-logo"><img src="<?= base_url('assets/img/cobalance-logo.png') ?>" alt=""></span>
                <div class="system-brand-text"><h1>Co-Balance · System Admin</h1>
                    <p>Pengelolaan perusahaan dan admin perusahaan</p></div>
            </div>
        </div>
    </header>
    <main class="container system-content">
        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success" role="status"><?= html_escape($this->session->flashdata('success')) ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger" role="alert"><?= html_escape($this->session->flashdata('error')) ?></div>
        <?php endif; ?>
        <section class="system-panel">
            <h2>Tambah Perusahaan</h2>
            <p class="help">Setiap perusahaan dibuat bersama satu admin dan salinan master akun dari perusahaan ID 1.</p>
            <form action="<?= site_url('system/perusahaan/simpan') ?>" method="post">
                <input type="hidden" name="system_form_token" value="<?= html_escape($form_token) ?>">
                <?php if ($this->config->item('csrf_protection')): ?>
                    <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                <?php endif; ?>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group"><label for="nama_perusahaan">Nama perusahaan *</label><input id="nama_perusahaan" name="nama_perusahaan" class="form-control" maxlength="255" required></div>
                        <div class="form-group"><label for="alamat">Alamat</label><textarea id="alamat" name="alamat" class="form-control" rows="2"></textarea></div>
                        <div class="row">
                            <div class="col-sm-7"><div class="form-group"><label for="email">Email</label><input id="email" name="email" type="email" class="form-control" maxlength="100"></div></div>
                            <div class="col-sm-5"><div class="form-group"><label for="telepon">Telepon</label><input id="telepon" name="telepon" class="form-control" maxlength="20"></div></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group"><label for="nama_admin">Nama admin *</label><input id="nama_admin" name="nama_admin" class="form-control" maxlength="255" required></div>
                        <div class="form-group"><label for="username">Username admin *</label><input id="username" name="username" class="form-control" maxlength="100" pattern="[A-Za-z0-9._-]+" autocomplete="off" required></div>
                        <div class="form-group"><label for="password">Password admin *</label><input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" required><p class="help-block">Gunakan 8–72 byte dan bagikan melalui saluran yang aman.</p></div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Buat Perusahaan</button>
            </form>
        </section>
        <section class="system-panel">
            <h2>Daftar Perusahaan</h2>
            <p class="help">Admin perusahaan mengelola data dan penyesuaian akun perusahaannya sendiri.</p>
            <?php if (!$companies): ?>
                <p>Belum ada perusahaan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Perusahaan</th><th>Admin</th><th>Username</th><th>Email</th><th>Telepon</th></tr></thead>
                        <tbody>
                            <?php foreach ($companies as $company): ?>
                                <tr>
                                    <td><?= (int) $company->id_perusahaan ?></td>
                                    <td><?= html_escape($company->nama_perusahaan) ?></td>
                                    <td><?= html_escape($company->nama_lengkap ?: 'Belum ada admin') ?></td>
                                    <td><?= html_escape($company->username ?: '-') ?></td>
                                    <td><?= html_escape($company->email ?: '-') ?></td>
                                    <td><?= html_escape($company->telepon ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
