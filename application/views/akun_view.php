<!-- File: application/views/akun_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Data Akun</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button class="btn btn-success btn-sm" onclick="add_akun()">
            <i class="glyphicon glyphicon-plus"></i> Tambah Akun
        </button>
    </div>
</div>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th>Tipe Akun</th>
                <th>Akun Induk</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modal_form" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="#" id="form" class="form-horizontal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span>&times;</span>
                    </button>
                    <h3 class="modal-title">Form Akun</h3>
                </div>
                <div class="modal-body form">
                    <input type="hidden" value="" name="id_akun" />
                    <div class="form-body">
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3">Kode Akun</label>
                            <div class="col-xs-12 col-sm-9">
                                <input name="kode_akun" placeholder="Kode Akun" class="form-control" type="text">
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3">Nama Akun</label>
                            <div class="col-xs-12 col-sm-9">
                                <input name="nama_akun" placeholder="Nama Akun" class="form-control" type="text">
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3">Tipe Akun</label>
                            <div class="col-xs-12 col-sm-9">
                                <select name="tipe_akun" class="form-control">
                                    <option value="">-- Pilih Tipe Akun --</option>
                                    <option value="Aset">Aset</option>
                                    <option value="Kewajiban">Kewajiban</option>
                                    <option value="Ekuitas">Ekuitas</option>
                                    <option value="Pendapatan">Pendapatan</option>
                                    <option value="Beban">Beban</option>
                                </select>
                                <span class="help-block"></span>
                            </div>
                        </div>
                        <!-- Dropdown untuk Akun Induk -->
                        <div class="form-group">
                            <label class="control-label col-xs-12 col-sm-3">Akun Induk</label>
                            <div class="col-xs-12 col-sm-9">
                                <select name="id_akun_induk" class="form-control">
                                    <option value="">-- Tidak Ada (Akun Induk) --</option>
                                    <?php foreach ($parent_list as $parent): ?>
                                        <option value="<?php echo $parent->id_akun; ?>">
                                            <?php echo $parent->nama_akun . " (" . $parent->kode_akun . ")"; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="help-block"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnSave" onclick="save()" class="btn btn-primary btn-sm">
                        Simpan
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
                        Batal
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

        // Inisialisasi DataTable dengan default order berdasarkan kolom kode akun (index 1)
        table = $('#table').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [[1, "asc"]],
            "ajax": {
                "url": "<?php echo site_url('akun/ajax_list') ?>",
                "type": "POST"
            },
            "columnDefs": [
                {
                    "targets": [0, -1],
                    "orderable": false,
                },
            ],
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

    function add_akun() {
        save_method = 'add';
        $('#form')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.help-block').empty();
        $('#modal_form').modal('show');
        $('.modal-title').text('Tambah Akun');
    }

    function edit_akun(id) {
        save_method = 'update';
        $('#form')[0].reset();
        $('.form-group').removeClass('has-error');
        $('.help-block').empty();

        // Load data via AJAX
        $.ajax({
            url: "<?php echo site_url('akun/ajax_edit/') ?>/" + id,
            type: "GET",
            dataType: "JSON",
            success: function (data) {
                $('[name="id_akun"]').val(data.id_akun);
                $('[name="kode_akun"]').val(data.kode_akun);
                $('[name="nama_akun"]').val(data.nama_akun);
                $('[name="tipe_akun"]').val(data.tipe_akun);
                $('[name="id_akun_induk"]').val(data.id_akun_induk);
                $('#modal_form').modal('show');
                $('.modal-title').text('Edit Akun');
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error mendapatkan data melalui ajax');
            }
        });
    }

    function save() {
        $('#btnSave').text('Menyimpan...');
        $('#btnSave').attr('disabled', true);
        var url = (save_method == 'add') ? "<?php echo site_url('akun/ajax_add') ?>"
            : "<?php echo site_url('akun/ajax_update') ?>";

        // Proses simpan data via AJAX
        $.ajax({
            url: url,
            type: "POST",
            data: $('#form').serialize(),
            dataType: "JSON",
            success: function (data) {
                $('#modal_form').modal('hide');
                $('#btnSave').text('Simpan');
                $('#btnSave').attr('disabled', false);
                table.ajax.reload(null, false);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error menambahkan/memperbarui data');
                $('#btnSave').text('Simpan');
                $('#btnSave').attr('disabled', false);
            }
        });
    }

    function delete_akun(id) {
        if (confirm('Apakah anda yakin ingin menghapus data ini?')) {
            $.ajax({
                url: "<?php echo site_url('akun/ajax_delete') ?>/" + id,
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
</script>