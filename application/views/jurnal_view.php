<!-- File: application/views/jurnal_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Jurnal Transaksi</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button class="btn btn-success btn-sm" onclick="add_jurnal()">
            <i class="glyphicon glyphicon-plus"></i>
        </button>
    </div>
</div>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th style="width:12%; align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modal_form" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- modal lebar untuk detail -->
        <div class="modal-content">
            <form action="#" id="form" class="form-horizontal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                    <h3 class="modal-title">Form Jurnal Transaksi</h3>
                </div>
                <div class="modal-body form">
                    <input type="hidden" name="id_jurnal" value="" />
                    <div class="form-body">
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-2">Tanggal</label>
                            <div class="col-xs-12 col-sm-4">
                                <input name="tanggal" placeholder="YYYY-MM-DD" class="form-control" type="date">
                                <span class="help-block"></span>
                            </div>
                            <label class="control-label col-xs-12 col-sm-2">Referensi</label>
                            <div class="col-xs-12 col-sm-4">
                                <input name="referensi" placeholder="Referensi" class="form-control" type="text">
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-2">Deskripsi</label>
                            <div class="col-xs-12 col-sm-10">
                                <input name="deskripsi" placeholder="Deskripsi" class="form-control" type="text">
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <hr>
                        <h4>Detail Jurnal</h4>
                        <table id="detail_table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Akun</th>
                                    <th>Debit</th>
                                    <th>Kredit</th>
                                    <th style="width:50px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <button type="button" class="btn btn-default btn-sm" onclick="add_detail_row()">
                            <i class="glyphicon glyphicon-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnSave" onclick="save()" class="btn btn-primary btn-sm">
                        <i class="glyphicon glyphicon-floppy-disk"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
                        <i class="glyphicon glyphicon-remove"></i>
                    </button>
                </div>
            </form>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script type="text/javascript">
    var save_method; // untuk menentukan aksi add atau update
    var table;

    $(document).ready(function () {
        table = $('#table').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [[1, "desc"]],
            "ajax": {
                "url": "<?php echo site_url('jurnal/ajax_list'); ?>",
                "type": "POST"
            },
            "columnDefs": [
                { "targets": [0, 4], "orderable": false }
            ]
        });

        $("input").change(function () {
            $(this).closest('.form-group').removeClass('has-error');
            $(this).next().empty();
        });
        $("select").change(function () {
            $(this).closest('.form-group').removeClass('has-error');
            $(this).next().empty();
        });
    });

    function add_jurnal() {
        save_method = 'add';
        $('#form')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.help-block').empty();
        $('#detail_table tbody').empty();
        $('#modal_form').modal('show');
        $('.modal-title').text('Tambah Jurnal Transaksi');
    }

    function edit_jurnal(id) {
        save_method = 'update';
        $('#form')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.help-block').empty();
        $('#detail_table tbody').empty();

        $.ajax({
            url: "<?php echo site_url('jurnal/ajax_edit/'); ?>" + id,
            type: "GET",
            dataType: "JSON",
            success: function (data) {
                $('[name="id_jurnal"]').val(data.id_jurnal);
                $('[name="tanggal"]').val(data.tanggal);
                $('[name="deskripsi"]').val(data.deskripsi);
                $('[name="referensi"]').val(data.referensi);
                // Load detail baris (jika ada)
                if (data.detail) {
                    for (var i = 0; i < data.detail.length; i++) {
                        add_detail_row(data.detail[i]);
                    }
                }
                $('#modal_form').modal('show');
                $('.modal-title').text('Edit Jurnal Transaksi');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error mendapatkan data via AJAX');
            }
        });
    }

    function save() {
        $('#btnSave').text('Menyimpan...');
        $('#btnSave').attr('disabled', true);
        var url = (save_method == 'add') ? "<?php echo site_url('jurnal/ajax_add'); ?>" : "<?php echo site_url('jurnal/ajax_update'); ?>";

        $.ajax({
            url: url,
            type: "POST",
            data: $('#form').serialize(),
            dataType: "JSON",
            success: function (data) {
                $('#modal_form').modal('hide');
                $('#btnSave').text('');
                $('#btnSave').attr('disabled', false);
                table.ajax.reload(null, false);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error menambahkan/memperbarui data');
                $('#btnSave').text('');
                $('#btnSave').attr('disabled', false);
            }
        });
    }

    function delete_jurnal(id) {
        if (confirm('Apakah anda yakin akan menghapus data ini?')) {
            $.ajax({
                url: "<?php echo site_url('jurnal/ajax_delete/'); ?>" + id,
                type: "POST",
                dataType: "JSON",
                success: function (data) {
                    table.ajax.reload(null, false);
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    alert('Error menghapus data');
                }
            });
        }
    }

    function add_detail_row(detail) {
        // Parameter detail opsional; jika ada, merupakan objek dengan properti: id_akun, debit, kredit.
        var akunOptions = '<option value="">-- Pilih Akun --</option>';
        <?php foreach ($akun_list as $akun): ?>
            akunOptions += '<option value="<?php echo $akun->id_akun; ?>"><?php echo $akun->nama_akun . ' (' . $akun->kode_akun . ')'; ?></option>';
        <?php endforeach; ?>

        var row = '<tr>';
        row += '<td><select name="detail_akun[]" class="form-control">' + akunOptions + '</select></td>';
        row += '<td><input name="detail_debit[]" class="form-control" type="number" step="0.01" value="0"></td>';
        row += '<td><input name="detail_kredit[]" class="form-control" type="number" step="0.01" value="0"></td>';
        row += '<td style="text-align:center;"><button type="button" class="btn btn-danger btn-sm" onclick="remove_detail_row(this)"><i class="glyphicon glyphicon-trash"></i></button></td>';
        row += '</tr>';

        var $row = $(row);
        if (detail) {
            $row.find('select[name="detail_akun[]"]').val(detail.id_akun);
            $row.find('input[name="detail_debit[]"]').val(detail.debit);
            $row.find('input[name="detail_kredit[]"]').val(detail.kredit);
        }
        $('#detail_table tbody').append($row);
    }

    function remove_detail_row(button) {
        $(button).closest('tr').remove();
    }
</script>