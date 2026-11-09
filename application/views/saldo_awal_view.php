<?php
$trx = $opening['transaksi'];
$editable = !$trx || $trx->status === 'draft';
$control_ids = array();
foreach ($control_accounts as $account) $control_ids[(string) $account->id_akun] = true;
$account_options = array();
foreach ($accounts as $account) {
    $account_options[] = array('id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable || isset($control_ids[(string) $account->id_akun]));
}
$equity_options = array();
foreach ($equity_accounts as $account) {
    $equity_options[] = array('id' => $account->id_akun,
        'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
        'disabled' => !$account->selectable);
}
$asset_account_options = array();
foreach (array('id_akun_aset' => $asset_accounts, 'id_akun_akumulasi_penyusutan' => $accumulated_accounts,
    'id_akun_beban_penyusutan' => $expense_accounts) as $key => $list) {
    $asset_account_options[$key] = array();
    foreach ($list as $account) {
        $asset_account_options[$key][] = array('id' => $account->id_akun,
            'label' => str_repeat('— ', $account->depth) . $account->kode_akun . ' - ' . $account->nama_akun,
            'disabled' => !$account->selectable);
    }
}
$customer_options = array();
foreach ($customers as $customer) $customer_options[] = array('id' => $customer->id_pelanggan, 'label' => $customer->kode_pelanggan . ' - ' . $customer->nama_pelanggan, 'disabled' => !$customer->aktif);
$supplier_options = array();
foreach ($suppliers as $supplier) $supplier_options[] = array('id' => $supplier->id_pemasok, 'label' => $supplier->kode_pemasok . ' - ' . $supplier->nama_pemasok, 'disabled' => !$supplier->aktif);
$product_options = array();
foreach ($products as $product) $product_options[] = array('id' => $product->id_produk, 'label' => $product->kode_produk . ' - ' . $product->nama_produk, 'disabled' => !$product->aktif);
?>
<h3>Saldo Awal</h3>
<p>Gunakan satu tanggal pembukaan. Piutang, utang, dan persediaan dimasukkan melalui rincian masing-masing, bukan saldo akun.</p>
<p>Jurnal perusahaan harus kosong sebelum posting. Selisih debit dan kredit dicatat pada akun ekuitas penyeimbang.</p>
<p>Masukkan aset tetap pada rincian aset: nilai perolehan dan akumulasi penyusutan akan otomatis masuk jurnal. Jangan masukkan akun yang sama lagi pada Saldo Akun.</p>
<?php if ($trx && $trx->status === 'diposting'): ?>
    <p>Untuk aset lama yang nilainya sudah tercatat dalam Saldo Akun, gunakan <a href="<?= site_url('aset_tetap') ?>">pendaftaran aset lama</a> tanpa jurnal tambahan.</p>
<?php endif; ?>
<?php if ($trx): ?>
    <p><strong>Nomor:</strong> <?= html_escape($trx->nomor_transaksi) ?> &nbsp; <strong>Status:</strong> <?= html_escape($trx->status) ?></p>
<?php endif; ?>
<div id="opening-message" role="alert"></div>
<form id="opening-form">
    <fieldset <?= $editable ? '' : 'disabled' ?>>
        <div class="row">
            <div class="form-group col-sm-4">
                <label for="opening-date">Tanggal pembukaan</label>
                <input class="form-control" id="opening-date" type="date" required value="<?= html_escape($trx ? $trx->tanggal : date('Y-m-d')) ?>">
            </div>
            <div class="form-group col-sm-8">
                <label for="balancing-account">Akun ekuitas penyeimbang</label>
                <select class="form-control" id="balancing-account" required></select>
            </div>
        </div>
        <?php foreach (array('akun' => 'Saldo akun', 'piutang' => 'Piutang per faktur', 'utang' => 'Utang per faktur', 'stok' => 'Persediaan per produk', 'aset' => 'Aset tetap') as $key => $title): ?>
            <h4><?= html_escape($title) ?></h4>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed" id="table-<?= $key ?>">
                    <thead><tr>
                        <?php
                        $headers = array('akun' => array('Akun', 'Debit', 'Kredit'), 'piutang' => array('Pelanggan', 'Nomor faktur', 'Jatuh tempo', 'Jumlah'), 'utang' => array('Pemasok', 'Nomor faktur', 'Jatuh tempo', 'Jumlah'), 'stok' => array('Produk', 'Jumlah', 'Total nilai'),
                            'aset' => array('Kode', 'Nama', 'Tanggal perolehan', 'Mulai penyusutan', 'Nilai perolehan', 'Nilai residu', 'Umur (bulan)', 'Akumulasi per Saldo Awal', 'Akun aset', 'Akun akumulasi', 'Akun beban penyusutan'));
                        foreach ($headers[$key] as $header) echo '<th>' . html_escape($header) . '</th>';
                        ?>
                        <?php if ($editable): ?><th>Aksi</th><?php endif; ?>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <?php if ($editable): ?><button type="button" class="btn btn-default add-row" data-kind="<?= $key ?>">Tambah <?= html_escape(strtolower($title)) ?></button><?php endif; ?>
        <?php endforeach; ?>
        <?php if ($editable): ?><p style="margin-top:20px"><button type="submit" class="btn btn-primary">Simpan draft</button></p><?php endif; ?>
    </fieldset>
</form>
<?php if ($trx && $trx->status === 'draft'): ?>
    <button type="button" id="post-opening" class="btn btn-success">Posting saldo awal yang tersimpan</button>
    <p>Pastikan perubahan sudah disimpan sebelum posting.</p>
<?php elseif ($trx && $trx->status === 'diposting'): ?>
    <h4>Pembatalan</h4>
    <p>Pembatalan ditolak jika faktur telah dialokasikan, stok telah digunakan, atau aset sudah digunakan dalam penyusutan atau pelepasan. Setelah dibatalkan, pembukaan baru tidak dapat dibuat melalui modul ini.</p>
    <form id="cancel-form" class="form-inline">
        <label for="cancel-date">Tanggal</label>
        <input id="cancel-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>">
        <label for="cancel-reason">Alasan</label>
        <input id="cancel-reason" class="form-control" required maxlength="255">
        <button type="submit" class="btn btn-danger">Batalkan saldo awal</button>
    </form>
<?php endif; ?>
<script>
$(function () {
    var opening = <?= json_encode($opening, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var options = <?= json_encode(array('akun' => $account_options, 'penyeimbang' => $equity_options, 'piutang' => $customer_options, 'utang' => $supplier_options, 'stok' => $product_options), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var assetAccounts = <?= json_encode($asset_account_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var editable = <?= $editable ? 'true' : 'false' ?>;
    var fields = {
        akun: [['id_akun', 'select'], ['debit', 'money'], ['kredit', 'money']],
        piutang: [['id_pihak', 'select'], ['nomor', 'text'], ['jatuh_tempo', 'date'], ['jumlah', 'money']],
        utang: [['id_pihak', 'select'], ['nomor', 'text'], ['jatuh_tempo', 'date'], ['jumlah', 'money']],
        stok: [['id_produk', 'select'], ['jumlah', 'quantity'], ['nilai', 'money']],
        aset: [['kode_aset', 'text'], ['nama_aset', 'text'], ['tanggal_perolehan', 'date'],
            ['tanggal_mulai_penyusutan', 'date'], ['nilai_perolehan', 'money'], ['nilai_residu', 'money'],
            ['umur_manfaat_bulan', 'integer'], ['akumulasi_penyusutan_awal', 'money'],
            ['id_akun_aset', 'select'], ['id_akun_akumulasi_penyusutan', 'select'], ['id_akun_beban_penyusutan', 'select']]
    };
    function fillSelect(select, list, selected) {
        select.append($('<option>').val('').text('-- Pilih --'));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }
    fillSelect($('#balancing-account'), options.penyeimbang, opening.penyeimbang);
    function addRow(kind, values) {
        values = values || {};
        var row = $('<tr>');
        fields[kind].forEach(function (field) {
            var input;
            if (field[1] === 'select') {
                input = $('<select class="form-control">');
                fillSelect(input, kind === 'aset' ? assetAccounts[field[0]] : options[kind], values[field[0]]);
            } else {
                input = $('<input class="form-control">').attr('type', field[1] === 'date' ? 'date' : 'text');
                if (field[1] === 'money' || field[1] === 'quantity') input.attr('inputmode', 'decimal').attr('placeholder', field[1] === 'money' ? '0.00' : '0.0000');
                if (field[0] === 'nomor') input.attr('maxlength', '100');
                if (field[0] === 'kode_aset') input.attr('maxlength', '50');
                if (field[0] === 'nama_aset') input.attr('maxlength', '255');
                if (field[1] === 'integer') input.attr('inputmode', 'numeric');
                input.val(values[field[0]] == null ? ((field[0] === 'debit' || field[0] === 'kredit' || field[0] === 'nilai_residu' || field[0] === 'akumulasi_penyusutan_awal') ? '0.00' : '') : values[field[0]]);
            }
            input.attr('data-field', field[0]).prop('required', true);
            row.append($('<td>').append(input));
        });
        if (editable) row.append($('<td>').append($('<button type="button" class="btn btn-danger btn-sm">').text('Hapus').on('click', function () { row.remove(); })));
        $('#table-' + kind + ' tbody').append(row);
    }
    Object.keys(fields).forEach(function (kind) { (opening[kind] || []).forEach(function (row) { addRow(kind, row); }); });
    $('.add-row').on('click', function () { addRow($(this).data('kind')); });
    function request(url, data) {
        <?php if ($this->config->item('csrf_protection')): ?>
        data[<?= json_encode($this->security->get_csrf_token_name()) ?>] = <?= json_encode($this->security->get_csrf_hash()) ?>;
        <?php endif; ?>
        $('button').prop('disabled', true);
        $.post(url, data).done(function () { location.reload(); }).fail(function (xhr) {
            $('#opening-message').empty().append($('<div class="alert alert-danger">').text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'));
            $('button').prop('disabled', false);
        });
    }
    $('#opening-form').on('submit', function (event) {
        event.preventDefault();
        var data = {penyeimbang: $('#balancing-account').val()};
        Object.keys(fields).forEach(function (kind) {
            data[kind] = [];
            $('#table-' + kind + ' tbody tr').each(function () {
                var row = {};
                $(this).find('[data-field]').each(function () { row[$(this).data('field')] = $(this).val(); });
                data[kind].push(row);
            });
        });
        request(<?= json_encode(site_url('saldo_awal/simpan')) ?>, {tanggal: $('#opening-date').val(), data: JSON.stringify(data)});
    });
    $('#post-opening').on('click', function () {
        if (confirm('Posting saldo awal yang telah disimpan?')) request(<?= json_encode(site_url('saldo_awal/posting')) ?>, {id: opening.transaksi.id_transaksi});
    });
    $('#cancel-form').on('submit', function (event) {
        event.preventDefault();
        if (confirm('Batalkan saldo awal dan buat jurnal pembalik? Pembukaan baru tidak dapat dibuat melalui modul ini.')) request(<?= json_encode(site_url('saldo_awal/batalkan')) ?>, {id: opening.transaksi.id_transaksi, tanggal: $('#cancel-date').val(), alasan: $('#cancel-reason').val()});
    });
});
</script>
