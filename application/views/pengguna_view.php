<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Data Pengguna</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button type="button" class="btn btn-success btn-sm" onclick="add_pengguna()">
            <i class="glyphicon glyphicon-plus"></i> Tambah Pengguna
        </button>
    </div>
</div>
<p class="text-muted">
    Role menentukan peran pengguna. Pembatasan akses modul dan tampilan khusus belum diterapkan.
</p>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lengkap</th>
                <th>Username</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="modal_form" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h3 class="modal-title" id="modal_title">Form Pengguna</h3>
                </div>
                <div class="modal-body">
                    <div id="form_error" class="alert alert-danger" role="alert" style="display:none;"></div>
                    <input type="hidden" name="id_pengguna">
                    <input type="hidden" name="user_form_token" value="<?= html_escape($form_token) ?>">
                    <?php if ($this->config->item('csrf_protection')): ?>
                        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>"
                            value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap</label>
                        <input id="nama_lengkap" name="nama_lengkap" class="form-control" maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" class="form-control" maxlength="100"
                            pattern="[A-Za-z0-9._-]+" autocomplete="username" required>
                        <p class="help-block">Gunakan huruf, angka, titik, garis bawah, atau tanda hubung.</p>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control" required>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= html_escape($value) ?>"><?= html_escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-block">Perusahaan memiliki satu admin. Memilih admin baru akan memindahkan role admin saat ini menjadi User.</p>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span id="password_required">*</span></label>
                        <input id="password" name="password" type="password" class="form-control"
                            minlength="8" maxlength="72" autocomplete="new-password">
                        <p class="help-block">Minimal 8 karakter. Saat mengedit, kosongkan agar password tetap sama.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="btnSave" class="btn btn-primary btn-sm">Simpan</button>
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var table;

    $(document).ready(function () {
        table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            order: [[1, 'asc']],
            ajax: {
                url: <?= json_encode(site_url('pengguna/ajax_list')) ?>,
                type: 'POST'
            },
            columnDefs: [{ targets: [0, -1], orderable: false }]
        });

        $('#form').on('submit', function (event) {
            event.preventDefault();
            $('#form_error').hide().text('');
            $('#btnSave').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: <?= json_encode(site_url('pengguna/ajax_save')) ?>,
                type: 'POST',
                data: $('#form').serialize(),
                dataType: 'json',
                success: function () {
                    $('#modal_form').modal('hide');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    $('#form_error').text(user_error_message(xhr, 'Gagal menyimpan pengguna.')).show();
                },
                complete: function () {
                    $('#btnSave').prop('disabled', false).text('Simpan');
                }
            });
        });
    });

    function user_error_message(xhr, fallback) {
        return xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : fallback;
    }

    function add_pengguna() {
        $('#form')[0].reset();
        $('#form [name="id_pengguna"]').val('');
        $('#form_error').hide().text('');
        $('#password').prop('required', true);
        $('#password_required').show();
        $('#modal_title').text('Tambah Pengguna');
        $('#modal_form').modal('show');
    }

    function edit_pengguna(id) {
        $.ajax({
            url: <?= json_encode(site_url('pengguna/ajax_edit')) ?> + '/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (user) {
                $('#form')[0].reset();
                $('#form_error').hide().text('');
                $('#form [name="id_pengguna"]').val(user.id_pengguna);
                $('#form [name="nama_lengkap"]').val(user.nama_lengkap);
                $('#form [name="username"]').val(user.username);
                $('#form [name="role"]').val(user.role);
                $('#password').prop('required', false);
                $('#password_required').hide();
                $('#modal_title').text('Edit Pengguna');
                $('#modal_form').modal('show');
            },
            error: function (xhr) {
                alert(user_error_message(xhr, 'Gagal mengambil data pengguna.'));
            }
        });
    }

    function delete_pengguna(id) {
        if (!confirm('Hapus pengguna ini dari perusahaan?')) {
            return;
        }
        var data = { user_form_token: <?= json_encode($form_token) ?> };
        <?php if ($this->config->item('csrf_protection')): ?>
            data[<?= json_encode($this->security->get_csrf_token_name()) ?>] =
                <?= json_encode($this->security->get_csrf_hash()) ?>;
        <?php endif; ?>
        $.ajax({
            url: <?= json_encode(site_url('pengguna/ajax_delete')) ?> + '/' + id,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function () {
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(user_error_message(xhr, 'Gagal menghapus pengguna.'));
            }
        });
    }
</script>
