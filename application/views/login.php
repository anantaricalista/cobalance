<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Masuk - Co-Balance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 15px; background: #f4f6f9; color: #29394c; }
        .login-shell { width: 100%; max-width: 430px; }
        .login-brand { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 23px; color: #243b5a; font-size: 24px; font-weight: 700; }
        .login-brand-mark { display: flex; align-items: center; justify-content: center; width: 45px; height: 45px; border-radius: 12px; background: #347e85; color: #fff; }
        .form-signin { padding: 32px; border: 1px solid #e1e8f0; border-radius: 12px; background: #fff; box-shadow: 0 12px 35px rgba(27,49,79,.08); }
        .form-signin h1 { margin: 0 0 8px; color: #243b5a; font-size: 23px; font-weight: 700; }
        .login-intro { margin-bottom: 24px; color: #718198; }
        .form-signin label { color: #42536b; font-size: 13px; }
        .form-signin .form-control { height: 44px; border-color: #d6dfe8; border-radius: 6px; box-shadow: none; }
        .form-signin .form-control:focus { border-color: #4a93a5; box-shadow: 0 0 0 3px rgba(74,147,165,.13); }
        .form-signin .btn { margin-top: 22px; border-color: #347e85; border-radius: 6px; background: #347e85; font-size: 15px; font-weight: 600; }
        .form-signin .btn:hover, .form-signin .btn:focus { border-color: #28666c; background: #28666c; }
        .login-footer { margin-top: 20px; color: #8492a5; font-size: 12px; text-align: center; }
        @media (max-width: 480px) { .form-signin { padding: 24px 20px; } }
    </style>
</head>

<body>
    <div class="login-shell">
        <div class="login-brand"><span class="login-brand-mark" aria-hidden="true">C</span>Co-Balance</div>
        <form class="form-signin" action="<?php echo site_url('auth/login'); ?>" method="post">
            <input type="hidden" name="login_token" value="<?php echo html_escape($login_token); ?>">
            <?php if ($this->config->item('csrf_protection')): ?>
                <input type="hidden" name="<?php echo html_escape($this->security->get_csrf_token_name()); ?>"
                    value="<?php echo html_escape($this->security->get_csrf_hash()); ?>">
            <?php endif; ?>
            <h1>Selamat datang</h1>
            <p class="login-intro">Masuk untuk mengakses ruang kerja Anda.</p>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger">
                    <?php echo $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="login-username">Username</label>
                <input id="login-username" type="text" name="username" class="form-control" placeholder="Masukkan username" autocomplete="username" maxlength="100" required autofocus>
            </div>
            <div class="form-group">
                <label for="login-password">Password</label>
                <input id="login-password" type="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">Masuk</button>
        </form>
        <p class="login-footer">Co-Balance &middot; Pengelolaan keuangan perusahaan</p>
    </div>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</body>

</html>