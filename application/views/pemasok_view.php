<style>
    .supplier-page { color: #263b51; padding: 14px 0 30px; }
    .supplier-hero { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
    .supplier-eyebrow { color: #087c80; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .supplier-hero h1 { margin: 6px 0 8px; font-size: 30px; font-weight: 700; color: #183153; }
    .supplier-hero p, .supplier-hint { color: #5e7084; line-height: 1.5; margin: 0; }
    .supplier-add { border: 0; border-radius: 7px; padding: 11px 16px; background: #087d82; font-weight: 600; white-space: nowrap; }
    .supplier-add:hover, .supplier-add:focus { background: #06696d; }
    .supplier-panel { padding: 22px; background: #fff; border: 1px solid #e0e8ee; border-radius: 10px; box-shadow: 0 4px 18px rgba(27, 49, 79, .07); }
    .supplier-panel h2 { font-size: 18px; font-weight: 700; margin: 0 0 6px; color: #183153; }
    .supplier-panel .dataTables_wrapper { margin-top: 20px; }
    .supplier-panel .table-responsive { border: 0; margin: 0; }
    .supplier-panel .table { margin-top: 12px !important; }
    .supplier-panel .table > thead > tr > th { background: #f3f8f9; border-bottom: 1px solid #dce8ea; font-size: 12px; color: #4a6070; white-space: nowrap; }
    .supplier-panel .table > tbody > tr > td { vertical-align: middle; }
    .supplier-status { display: inline-block; padding: 4px 9px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
    .supplier-status.is-active { color: #126540; background: #e4f5ea; }
    .supplier-status.is-inactive { color: #5d6570; background: #eef1f4; }
    .supplier-edit { color: #08767a; border-color: #b6dadd; font-weight: 600; }
    .supplier-delete { color: #a63c3c; }
    .supplier-modal .modal-content { border: 0; border-radius: 10px; overflow: hidden; }
    .supplier-modal .modal-header { padding: 20px 24px; background: #f3f9f9; }
    .supplier-modal .modal-title { color: #183153; font-weight: 700; }
    .supplier-modal .modal-body { padding: 24px; }
    .supplier-modal .control-label { color: #42586c; }
    .supplier-modal .form-control { border-color: #cddbe0; border-radius: 6px; box-shadow: none; }
    .supplier-modal .form-control:focus { border-color: #168a8e; box-shadow: 0 0 0 3px rgba(22, 138, 142, .12); }
    .supplier-modal .modal-footer { padding: 15px 24px; }
    @media (max-width: 767px) {
        .supplier-hero { align-items: flex-start; flex-direction: column; }
        .supplier-add { width: 100%; }
        .supplier-panel { padding: 16px; }
        .supplier-modal .control-label { text-align: left; }
    }
</style>

<div class="supplier-page">
    <div class="supplier-hero">
        <div>
            <span class="supplier-eyebrow">Master data</span>
            <h1>Data Pemasok</h1>
            <p>Kelola informasi dan status pemasok untuk transaksi pembelian.</p>
        </div>
        <button type="button" class="btn btn-success supplier-add" onclick="add_pemasok()">
            <i class="glyphicon glyphicon-plus" aria-hidden="true"></i> Tambah Pemasok
        </button>
    </div>

    <div id="supplier_message" role="status" aria-live="polite"></div>
    <div class="supplier-panel">
        <h2>Daftar Pemasok</h2>
        <p class="supplier-hint">Cari pemasok berdasarkan kode, nama, atau kontak. Pemasok tidak aktif tidak dapat dipilih untuk pembelian baru.</p>
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
    </div>
</div>

<div class="modal fade supplier-modal" id="modal_form" role="dialog" aria-modal="true" aria-labelledby="modal_title">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form" class="form-horizontal">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h3 class="modal-title" id="modal_title">Form Pemasok</h3>
                    <p class="supplier-hint">Kode dan nama wajib diisi. Lengkapi kontak agar mudah dihubungi.</p>
                </div>
                <div class="modal-body">
                    <div id="form_error" class="alert alert-danger" role="alert" style="display:none;"></div>
                    <input type="hidden" name="id_pemasok">
                    <?php if ($this->session->userdata('role') === 'purchasing'): ?>
                        <input type="hidden" name="pemasok_form_token" value="<?= html_escape($this->session->userdata('pemasok_form_token')) ?>">
                    <?php endif; ?>
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
                            <span class="help-block">Pilih Tidak Aktif jika pemasok tidak lagi digunakan.</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSave" class="btn btn-primary">Simpan</button>
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
            language: {
                processing: 'Memuat data...',
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ pemasok',
                infoEmpty: 'Tidak ada pemasok',
                infoFiltered: '(disaring dari _MAX_ pemasok)',
                zeroRecords: 'Pemasok tidak ditemukan.',
                emptyTable: 'Belum ada pemasok.',
                paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' }
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
                    $('#supplier_message').empty().append($('<div class="alert alert-success">').text(
                        save_method === 'add' ? 'Pemasok berhasil ditambahkan.' : 'Data pemasok berhasil diperbarui.'));
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
                $('#supplier_message').empty().append($('<div class="alert alert-success">').text('Pemasok berhasil dihapus.'));
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                alert(error_message(xhr, 'Gagal menghapus pemasok.'));
            }
        });
    }
</script>
