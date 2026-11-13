<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6"><h1>Data <?php echo html_escape($master['title']); ?></h1></div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button type="button" class="btn btn-success btn-sm" onclick="add_master()">
            <i class="glyphicon glyphicon-plus"></i> Tambah
        </button>
    </div>
</div>
<?php if (isset($master['help'])): ?>
    <p class="alert alert-info"><?php echo html_escape($master['help']); ?></p>
<?php endif; ?>
<?php if ($master['route'] === 'produk'): ?>
    <style>
        .product-list-image { width: 48px; height: 48px; object-fit: cover; border: 1px solid #e1e8f0; border-radius: 6px; }
        #modal_form .modal-content { border: 0; border-radius: 12px; }
        #modal_form .modal-header { padding: 20px 24px; border-bottom: 1px solid #e4ebf2; }
        #modal_form .modal-title { margin: 0; color: #243b5a; font-size: 20px; font-weight: 700; }
        #modal_form .modal-body { padding: 20px 24px; background: #f7f9fc; }
        #modal_form .modal-footer { padding: 15px 24px; }
        .product-form-section { margin-bottom: 16px; padding: 18px; border: 1px solid #e1e8f0; border-radius: 9px; background: #fff; }
        .product-form-section:last-child { margin-bottom: 0; }
        .product-form-section h4 { margin: 0 0 5px; color: #243b5a; font-size: 15px; font-weight: 700; }
        .product-form-section > p { margin: 0 0 15px; color: #718198; font-size: 12px; line-height: 1.5; }
        .product-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 18px; }
        #modal_form .product-form-grid .form-group { margin: 0; min-width: 0; }
        #modal_form .product-form-grid .control-label { display: block; float: none; width: auto; padding: 0; margin-bottom: 6px; text-align: left; }
        #modal_form .product-form-grid .product-field-input { float: none; width: auto; padding: 0; }
        #modal_form .product-form-grid .select2-container { max-width: 100%; }
        .product-image-controls { display: flex; align-items: flex-start; gap: 16px; }
        .product-image-placeholder, .product-image-preview { width: 112px; height: 112px; border: 1px solid #e1e8f0; border-radius: 8px; background: #f7f9fc; }
        .product-image-preview { display: block; object-fit: contain; padding: 4px; }
        .product-image-placeholder { display: flex; align-items: center; justify-content: center; padding: 10px; color: #718198; font-size: 12px; text-align: center; }
        .product-image-input { min-width: 0; flex: 1; }
        .product-image-input input[type="file"] { max-width: 100%; }
        .product-image-input .help-block { margin: 7px 0; }
        .product-image-input label { font-weight: 400; }
        @media (max-width: 600px) {
            #modal_form .modal-header, #modal_form .modal-body, #modal_form .modal-footer { padding-left: 16px; padding-right: 16px; }
            .product-form-grid { grid-template-columns: minmax(0, 1fr); }
            .product-image-controls { flex-wrap: wrap; }
        }
    </style>
    <p class="text-muted">Harga: pemisah ribuan ditambahkan otomatis; gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>.</p>
<?php endif; ?>
<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead><tr>
            <th>No</th>
            <?php foreach ($master['list'] as $name): ?>
                <th><?php echo html_escape($master['fields'][$name]['label']); ?></th>
            <?php endforeach; ?>
            <?php if ($master['route'] === 'produk'): ?><th>Gambar</th><?php endif; ?>
            <th>Aksi</th>
        </tr></thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="modal_form" role="dialog" aria-labelledby="modal_title">
    <div class="modal-dialog<?php echo $master['route'] === 'produk' ? ' modal-lg' : ''; ?>"><div class="modal-content">
        <form id="form" class="form-horizontal">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                <h3 class="modal-title" id="modal_title"></h3>
            </div>
            <div class="modal-body">
                <div id="form_error" class="alert alert-danger" role="alert" style="display:none;"></div>
                <input type="hidden" name="<?php echo html_escape($master['id']); ?>">
                <?php if ($master['route'] === 'produk'): ?>
                    <section class="product-form-section" aria-labelledby="product-basic-title">
                        <h4 id="product-basic-title">Informasi Produk</h4>
                        <p>Isi identitas, harga, dan status produk atau jasa.</p>
                        <div class="product-form-grid">
                <?php endif; ?>
                <?php foreach ($master['fields'] as $name => $field): ?>
                    <?php if ($master['route'] === 'produk' && $name === 'id_akun_pendapatan'): ?>
                        </div></section>
                        <section class="product-form-section" aria-labelledby="product-accounts-title">
                            <h4 id="product-accounts-title">Pengaturan Akun</h4>
                            <p>Pilih akun yang sesuai untuk transaksi produk atau jasa.</p>
                            <div class="product-form-grid">
                    <?php elseif ($master['route'] === 'produk' && $name === 'aktif'): ?>
                        </div></section>
                        <section class="product-form-section" aria-labelledby="product-status-title">
                            <h4 id="product-status-title">Status Produk</h4>
                            <p>Produk tidak aktif tidak dapat dipilih untuk transaksi baru.</p>
                            <div class="product-form-grid">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="<?php echo html_escape($name); ?>" class="control-label col-xs-12 col-sm-4"><?php echo html_escape($field['label']); ?></label>
                        <div class="col-xs-12 col-sm-8<?php echo $master['route'] === 'produk' ? ' product-field-input' : ''; ?>">
                            <?php if ($field['type'] === 'select'): ?>
                                <select id="<?php echo html_escape($name); ?>" name="<?php echo html_escape($name); ?>" class="form-control" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <?php if (isset($field['placeholder'])): ?>
                                        <option value=""><?php echo html_escape($field['placeholder']); ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($field['options'] as $value => $label): ?>
                                        <option value="<?php echo html_escape((string) $value); ?>"><?php echo html_escape($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($field['type'] === 'account'): ?>
                                <select id="<?php echo html_escape($name); ?>" name="<?php echo html_escape($name); ?>" class="form-control account-search" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($accounts[$name] as $account): ?>
                                        <option value="<?php echo $account->selectable ? html_escape($account->id_akun) : ''; ?>" <?php echo $account->selectable ? '' : 'disabled'; ?>><?php echo html_escape(str_repeat('- ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun); ?></option>
                                    <?php endforeach; ?>
                                    <?php if ($master['route'] === 'produk'): ?>
                                            </div>
                                        </section>
                                        <section class="product-form-section" aria-labelledby="product-image-title">
                                            <h4 id="product-image-title">Gambar Produk</h4>
                                            <p>Tambahkan gambar untuk memudahkan pengenalan produk.</p>
                                            <div class="product-image-controls">
                                                <img id="product-image-preview" class="product-image-preview" alt="Pratinjau gambar produk" style="display:none">
                                                <div id="product-image-empty" class="product-image-placeholder">Belum ada gambar</div>
                                                <div class="product-image-input">
                                                    <label for="gambar_produk">Pilih gambar</label>
                                                    <input id="gambar_produk" name="gambar_produk" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="product-image-help">
                                                    <p id="product-image-help" class="help-block">Opsional. JPG, PNG, atau WebP, maksimal 2 MB dan 4096 x 4096 piksel.</p>
                                                    <label id="product-image-remove-label" style="display:none"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar saat ini</label>
                                                </div>
                                            </div>
                                        </section>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <input id="<?php echo html_escape($name); ?>" name="<?php echo html_escape($name); ?>" class="form-control"
                                    type="<?php echo $field['type'] === 'decimal' ? 'number' : 'text'; ?>"
                                    <?php if ($field['type'] === 'decimal'): ?>min="0" step="<?php echo html_escape($field['step']); ?>" <?php if (isset($field['max'])): ?>max="<?php echo html_escape((string) $field['max']); ?>"<?php endif; ?><?php endif; ?>
                                    <?php if (isset($field['maxlength'])): ?>maxlength="<?php echo (int) $field['maxlength']; ?>"<?php endif; ?>
                                    <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="submit" id="btnSave" class="btn btn-primary btn-sm">Simpan</button>
                <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Batal</button>
            </div>
        </form>
    </div></div>
</div>

<script type="text/javascript">
    var table;
    var save_method;
    var masterUrl = <?php echo json_encode(site_url($master['route']), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var masterId = <?php echo json_encode($master['id']); ?>;
    var masterFields = <?php echo json_encode(array_keys($master['fields'])); ?>;
    var masterTitle = <?php echo json_encode($master['title']); ?>;
    var allAccounts = <?php echo json_encode($all_accounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var accountOptions = <?php echo json_encode($accounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var settingOptions = <?php echo json_encode($setting_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var isProduct = <?php echo $master['route'] === 'produk' ? 'true' : 'false'; ?>;
    var productImageUrl = null;
    var existingImageUrl = '';

    function showProductImage(url) {
        $('#product-image-preview').attr('src', url || '').toggle(!!url);
        $('#product-image-empty').toggle(!url);
    }

    function resetProductImage(url) {
        if (productImageUrl) URL.revokeObjectURL(productImageUrl);
        productImageUrl = null;
        existingImageUrl = url || '';
        $('#product-image-remove-label').toggle(!!existingImageUrl);
        showProductImage(existingImageUrl);
    }

    $(document).ready(function () {
        table = $('#table').DataTable({
            processing: true, serverSide: true, order: [[1, 'asc']],
            ajax: { url: masterUrl + '/ajax_list', type: 'POST' },
            columnDefs: [{ targets: isProduct ? [0, -2, -1] : [0, -1], orderable: false }]
        });
        $('#jenis_pengaturan').on('change', function () {
            updateSettingOptions();
        });
        <?php if ($master['route'] === 'produk'): ?>
        MoneyInput.init($('#harga_jual')[0], $('#harga_jual').val());
        $('#gambar_produk').on('change', function () {
            if (productImageUrl) URL.revokeObjectURL(productImageUrl);
            productImageUrl = this.files.length ? URL.createObjectURL(this.files[0]) : null;
            if (productImageUrl) $('[name="hapus_gambar"]').prop('checked', false);
            showProductImage(productImageUrl || existingImageUrl);
        });
        $('[name="hapus_gambar"]').on('change', function () {
            if (this.checked) {
                $('#gambar_produk').val('');
                if (productImageUrl) URL.revokeObjectURL(productImageUrl);
                productImageUrl = null;
            }
            showProductImage(this.checked ? '' : existingImageUrl);
        });
        <?php endif; ?>
        $('#form').on('submit', function (event) {
            event.preventDefault();
            $('#form_error').hide().text('');
            $('#btnSave').prop('disabled', true).text('Menyimpan...');
            var payload = MoneyInput.serialize($('#form'));
            if (isProduct) {
                payload = new FormData($('#form')[0]);
                $('#form [data-money]').each(function () { payload.set(this.name, MoneyInput.value(this)); });
            }
            $.ajax({
                url: masterUrl + (save_method === 'add' ? '/ajax_add' : '/ajax_update'),
                type: 'POST', data: payload, dataType: 'json',
                processData: !isProduct, contentType: isProduct ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success: function () { $('#modal_form').modal('hide'); table.ajax.reload(null, false); },
                error: function (xhr) { $('#form_error').text(error_message(xhr, 'Gagal menyimpan data.')).show(); },
                complete: function () { $('#btnSave').prop('disabled', false).text('Simpan'); }
            });
        });
    });

    function error_message(xhr, fallback) {
        return xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : fallback;
    }

    function add_master() {
        save_method = 'add';
        $('#form')[0].reset();
        if (isProduct) resetProductImage('');
        $('#form [data-money]').each(function () { MoneyInput.set(this, ''); });
        $('#form option[data-legacy]').remove();
        updateSettingOptions();
        $('#form').find('[name="' + masterId + '"]').val('');
        $('#form_error').hide().text('');
        $('#modal_title').text('Tambah ' + masterTitle);
        $('#modal_form').modal('show');
    }

    function edit_master(id) {
        $.ajax({
            url: masterUrl + '/ajax_edit/' + id, type: 'GET', dataType: 'json',
            success: function (data) {
                save_method = 'update';
                $('#form')[0].reset();
                if (isProduct) resetProductImage(data.memiliki_gambar ? masterUrl + '/gambar/' + encodeURIComponent(id) : '');
                $('#form option[data-legacy]').remove();
                $('#form_error').hide().text('');
                if (masterId === 'id_pengaturan_akun') {
                    if (!Object.prototype.hasOwnProperty.call(settingOptions, data.jenis_pengaturan)) {
                        $('#jenis_pengaturan').append($('<option>').val(data.jenis_pengaturan).text(data.jenis_pengaturan + ' (pilihan lama)').attr('data-legacy', 'true'));
                    }
                    $('#jenis_pengaturan').val(data.jenis_pengaturan);
                    updateSettingOptions();
                }
                [masterId].concat(masterFields).forEach(function (field) {
                    var input = $('#form').find('[name="' + field + '"]');
                    var value = data[field] === null ? '' : String(data[field]);
                    if (Object.prototype.hasOwnProperty.call(accountOptions, field) && value && input.find('option').filter(function () { return this.value === value && !this.disabled; }).length === 0) {
                        allAccounts.forEach(function (account) {
                            if (String(account.id_akun) === value) {
                                input.append($('<option>').val(value).text(account.kode_akun + ' - ' + account.nama_akun + ' (pilihan lama)').attr('data-legacy', 'true'));
                            }
                        });
                    }
                    if (input.is('[data-money]')) MoneyInput.set(input[0], value);
                    else input.val(value);
                });
                $('#modal_title').text('Edit ' + masterTitle);
                $('#modal_form').modal('show');
            },
            error: function (xhr) { alert(error_message(xhr, 'Gagal mengambil data.')); }
        });
    }

    function updateSettingOptions() {
        if (masterId !== 'id_pengaturan_akun') return;
        var select = $('#id_akun');
        var selected = select.val();
        var key = $('#jenis_pengaturan').val() || '';
        var choices = Object.prototype.hasOwnProperty.call(settingOptions, key) ? settingOptions[key] : (key ? accountOptions.id_akun : []);
        select.empty().append($('<option>').val('').text('-- Pilih Akun --'));
        choices.forEach(function (account) {
            select.append($('<option>').val(account.selectable ? account.id_akun : '').prop('disabled', !account.selectable)
                .text(new Array(account.depth + 1).join('- ') + account.kode_akun + ' - ' + account.nama_akun));
        });
        if (selected && select.find('option').filter(function () { return this.value === selected && !this.disabled; }).length) {
            select.val(selected);
        }
    }

    function delete_master(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) return;
        $.ajax({
            url: masterUrl + '/ajax_delete/' + id, type: 'POST', dataType: 'json',
            success: function () { table.ajax.reload(null, false); },
            error: function (xhr) { alert(error_message(xhr, 'Gagal menghapus data.')); }
        });
    }
</script>
