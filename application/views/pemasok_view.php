<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Data Pemasok</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button type="button" class="btn btn-success btn-sm" onclick="add_pemasok()">
            <i class="glyphicon glyphicon-plus"></i> Tambah Pemasok
        </button>
    </div>
</div>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Pemasok</th>
                <th>Nama Pemasok</th>
                <th>Telepon</th>
                <th>Email</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="modal_form" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form" class="form-horizontal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h3 class="modal-title" id="modal_title">Form Pemasok</h3>
                </div>
                <div class="modal-body">
                    <div id="form_error" class="alert alert-danger" role="alert" style="display:none;"></div>
                    <input type="hidden" name="id_pemasok">
                    <div class="form-group">
                        <label for="kode_pemasok" class="control-label col-xs-12 col-sm-3">Kode Pemasok</label>
                        <div class="col-xs-12 col-sm-9">
                            <input id="kode_pemasok" name="kode_pemasok" class="form-control" type="text" maxlength="50" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nama_pemasok" class="control-label col-xs-12 col-sm-3">Nama Pemasok</label>
                        <div class="col-xs-12 col-sm-9">
                            <input id="nama_pemasok" name="nama_pemasok" class="form-control" type="text" maxlength="255" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="alamat" class="control-label col-xs-12 col-sm-3">Alamat</label>
                        <div class="col-xs-12 col-sm-9">
                            <textarea id="alamat" name="alamat" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="telepon" class="control-label col-xs-12 col-sm-3">Telepon</label>
                        <div class="col-xs-12 col-sm-9">
                            <input id="telepon" name="telepon" class="form-control" type="tel" maxlength="20">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="control-label col-xs-12 col-sm-3">Email</label>
                        <div class="col-xs-12 col-sm-9">
                            <input id="email" name="email" class="form-control" type="email" maxlength="100">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="aktif" class="control-label col-xs-12 col-sm-3">Status</label>
                        <div class="col-xs-12 col-sm-9">
                            <select id="aktif" name="aktif" class="form-control">
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="btnSave" class="btn btn-primary btn-sm">Simpan</button>
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    var save_method;
    var table;

    $(document).ready(function () {
        table = $('#table').DataTable({
            processing: true,
            serverSide: true,
            order: [[1, 'asc']],
            ajax: {
                url: <?php echo json_encode(site_url('pemasok/ajax_list')); ?>,
                type: 'POST'
            },
            columnDefs: [{ targets: [0, -1], orderable: false }]
        });

        $('#form').on('submit', function (event) {
            event.preventDefault();
            $('#form_error').hide().text('');
            $('#btnSave').prop('disabled', true).text('Menyimpan...');
            $.ajax({
                url: save_method === 'add'
                    ? <?php echo json_encode(site_url('pemasok/ajax_add')); ?>
                    : <?php echo json_encode(site_url('pemasok/ajax_update')); ?>,
                type: 'POST',
                data: $('#form').serialize(),
                dataType: 'json',
                success: function () {
                    $('#modal_form').modal('hide');
                    table.ajax.reload(null, false);
                },
                error: function (xhr) {
                    $('#form_error').text(error_message(xhr, 'Gagal menyimpan pemasok.')).show();
                },
                complete: function () {
                    $('#btnSave').prop('disabled', false).text('Simpan');
                }
            });
        });
    });

    function error_message(xhr, fallback) {
        return xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : fallback;
    }

    function add_pemasok() {
        save_method = 'add';
        $('#form')[0].reset();
        $('[name="id_pemasok"]').val('');
        $('#form_error').hide().text('');
        $('#modal_title').text('Tambah Pemasok');
        $('#modal_form').modal('show');
    }

    function edit_pemasok(id) {
        $.ajax({
            url: <?php echo json_encode(site_url('pemasok/ajax_edit')); ?> + '/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                save_method = 'update';
                $('#form')[0].reset();
                $('#form_error').hide().text('');
                ['id_pemasok', 'kode_pemasok', 'nama_pemasok', 'alamat', 'telepon', 'email', 'aktif'].forEach(function (field) {
                    $('#form').find('[name="' + field + '"]').val(data[field]);
                });
                $('#modal_title').text('Edit Pemasok');
                $('#modal_form').modal('show');
            },
            error: function (xhr) {
                alert(error_message(xhr, 'Gagal mengambil data pemasok.'));
            }
        });
    }

    function delete_pemasok(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus pemasok ini?')) {
            return;
        }
        $.ajax({
            url: <?php echo json_encode(site_url('pemasok/ajax_delete')); ?> + '/' + id,
            type: 'POST',
            dataType: 'json',
            success: function () {
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(error_message(xhr, 'Gagal menghapus pemasok.'));
            }
        });
    }
</script>
