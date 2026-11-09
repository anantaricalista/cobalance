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
<div class="opening-overview">
    <div class="opening-overview-copy">
        <span class="opening-eyebrow">PERSIAPAN PEMBUKUAN</span>
        <h4>Mulai dengan saldo yang tepat</h4>
        <p>Catat posisi akun, tagihan, stok, dan aset pada tanggal pembukaan. Rincian operasional akan diposting bersama sebagai satu jurnal saldo awal.</p>
    </div>
    <div class="opening-status-card">
        <span class="opening-status-icon glyphicon glyphicon-book" aria-hidden="true"></span>
        <div>
            <span class="opening-eyebrow"><?= $trx ? 'TRANSAKSI PEMBUKAAN' : 'STATUS PEMBUKAAN' ?></span>
            <?php if ($trx): ?>
                <strong><?= html_escape($trx->nomor_transaksi) ?></strong>
                <span class="transaction-status <?= $trx->status === 'draft' ? 'is-draft' : ($trx->status === 'diposting' ? 'is-posted' : 'is-cancelled') ?>"><?= html_escape($trx->status) ?></span>
            <?php else: ?>
                <strong>Belum dibuat</strong>
                <span class="text-muted">Isi rincian untuk menyiapkan saldo awal.</span>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="opening-guidance" role="note">
    <div class="opening-guidance-title"><span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Sebelum mulai</div>
    <div class="opening-guidance-grid">
        <div><strong>Gunakan satu tanggal</strong><span>Semua saldo awal mengikuti tanggal pembukaan yang sama.</span></div>
        <div><strong>Pilih rincian yang sesuai</strong><span>Piutang, utang, dan persediaan dicatat pada bagian masing-masing, bukan Saldo Akun.</span></div>
        <div><strong>Perhatikan jurnal</strong><span>Jurnal perusahaan harus kosong. Selisih akan dicatat pada akun ekuitas penyeimbang.</span></div>
        <div><strong>Hindari pencatatan ganda</strong><span>Nilai aset dan akumulasi penyusutan otomatis masuk jurnal; jangan catat ulang pada Saldo Akun.</span></div>
    </div>
</div>
<?php if ($trx && $trx->status === 'diposting'): ?>
    <div class="opening-legacy-note">
        <span class="glyphicon glyphicon-link" aria-hidden="true"></span>
        Untuk aset lama yang nilainya sudah tercatat dalam Saldo Akun, gunakan
        <a href="<?= site_url('aset_tetap') ?>">pendaftaran aset lama</a> tanpa jurnal tambahan.
    </div>
<?php endif; ?>
<div id="opening-message" role="alert"></div>
<form id="opening-form" class="opening-form">
    <fieldset <?= $editable ? '' : 'disabled' ?>>
        <div class="opening-form-heading">
            <div>
                <span class="opening-eyebrow"><?= $editable ? 'FORMULIR PEMBUKAAN' : 'SALDO YANG TELAH DICATAT' ?></span>
                <h4><?= $editable ? 'Rincian saldo awal' : 'Rincian transaksi' ?></h4>
            </div>
            <?php if ($trx): ?>
                <span class="opening-date-label"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span> <?= html_escape($trx->tanggal) ?></span>
            <?php endif; ?>
        </div>
        <div class="opening-basics">
            <div class="form-group">
                <label for="opening-date">Tanggal pembukaan</label>
                <input class="form-control" id="opening-date" type="date" required value="<?= html_escape($trx ? $trx->tanggal : date('Y-m-d')) ?>">
            </div>
            <div class="form-group">
                <label for="balancing-account">Akun ekuitas penyeimbang</label>
                <select class="form-control" id="balancing-account" required></select>
            </div>
        </div>
        <div class="opening-journal-preview" aria-live="polite">
            <div class="opening-preview-heading">
                <div><span class="opening-eyebrow">PRATINJAU JURNAL</span><strong>Perkiraan posting</strong></div>
                <span class="opening-preview-account" id="opening-preview-account">Pilih akun penyeimbang</span>
            </div>
            <div class="opening-preview-grid">
                <div><span>Debit sebelum penyeimbang</span><strong id="opening-preview-debit">Rp 0,00</strong></div>
                <div><span>Kredit sebelum penyeimbang</span><strong id="opening-preview-credit">Rp 0,00</strong></div>
                <div><span>Nilai akun penyeimbang</span><strong id="opening-preview-balance">Rp 0,00</strong></div>
                <div class="opening-preview-total"><span>Total jurnal seimbang</span><strong id="opening-preview-total">Rp 0,00</strong></div>
            </div>
        </div>
        <?php foreach (array('akun' => 'Saldo akun', 'piutang' => 'Piutang per faktur', 'utang' => 'Utang per faktur', 'stok' => 'Persediaan per produk', 'aset' => 'Aset tetap') as $key => $title): ?>
            <section class="opening-section" data-opening-kind="<?= $key ?>">
                <div class="opening-section-heading">
                    <div>
                        <h4><?= html_escape($title) ?></h4>
                        <span class="opening-section-hint" data-section-hint="<?= $key ?>"></span>
                    </div>
                    <span class="opening-row-count" data-row-count="<?= $key ?>">0 rincian</span>
                </div>
            <div class="table-responsive">
                <table class="table table-bordered table-condensed table-hover" id="table-<?= $key ?>">
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
            <?php if ($editable): ?><button type="button" class="btn btn-default add-row" data-kind="<?= $key ?>"><span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Tambah <?= html_escape(strtolower($title)) ?></button><?php endif; ?>
            </section>
        <?php endforeach; ?>
        <?php if ($editable): ?>
            <div class="opening-save-bar">
                <span class="text-muted"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> Periksa kembali tanggal dan seluruh rincian sebelum menyimpan.</span>
                <button type="submit" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> Simpan draft saldo awal</button>
            </div>
        <?php endif; ?>
    </fieldset>
</form>
<?php if ($trx && $trx->status === 'draft'): ?>
    <div class="opening-action-panel">
        <div><strong>Draft siap ditinjau?</strong><span>Pastikan semua perubahan telah disimpan sebelum posting.</span></div>
        <button type="button" id="post-opening" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Posting saldo awal</button>
    </div>
<?php elseif ($trx && $trx->status === 'diposting'): ?>
    <section class="opening-cancel-panel">
        <h4><span class="glyphicon glyphicon-warning-sign" aria-hidden="true"></span> Pembatalan saldo awal</h4>
        <p>Pembatalan membuat jurnal pembalik. Pembatalan ditolak jika faktur telah dialokasikan, stok telah digunakan, atau aset sudah dipakai untuk penyusutan atau pelepasan. Saldo awal baru tidak dapat dibuat melalui modul ini setelah transaksi dibatalkan.</p>
        <form id="cancel-form" class="form-inline">
            <div class="form-group"><label for="cancel-date">Tanggal pembatalan</label>
                <input id="cancel-date" type="date" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
            <div class="form-group"><label for="cancel-reason">Alasan</label>
                <input id="cancel-reason" class="form-control" required maxlength="255" placeholder="Jelaskan alasan pembatalan"></div>
            <button type="submit" class="btn btn-danger">Batalkan saldo awal</button>
        </form>
    </section>
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
    var sectionHints = {
        akun: 'Saldo akun rincian, kecuali akun kontrol yang memiliki bagian khusus.',
        piutang: 'Tambahkan satu baris untuk setiap faktur pelanggan yang masih harus dibayar.',
        utang: 'Tambahkan satu baris untuk setiap faktur pemasok yang belum dilunasi.',
        stok: 'Masukkan jumlah dan nilai total persediaan awal untuk setiap produk.',
        aset: 'Nilai aset dan akumulasi penyusutan akan menghasilkan jurnal secara otomatis.'
    };
    Object.keys(sectionHints).forEach(function (kind) {
        $('[data-section-hint="' + kind + '"]').text(sectionHints[kind]);
    });
    function fillSelect(select, list, selected) {
        select.append($('<option>').val('').text('-- Pilih --'));
        list.forEach(function (item) {
            select.append($('<option>').val(item.id).text(item.label).prop('disabled', item.disabled));
        });
        select.val(selected == null ? '' : String(selected));
    }
    fillSelect($('#balancing-account'), options.penyeimbang, opening.penyeimbang);
    function addRow(kind, values, focusNew) {
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
                if (field[1] === 'money') MoneyInput.init(input[0], input.val());
            }
            input.attr('data-field', field[0]).prop('required', true);
            row.append($('<td>').append(input));
        });
        if (editable) row.append($('<td>').append($('<button type="button" class="btn btn-danger btn-sm">')
            .attr('aria-label', 'Hapus rincian ' + kind).append($('<span class="glyphicon glyphicon-trash" aria-hidden="true">'))
            .on('click', function () { row.remove(); updateCounts(); })));
        $('#table-' + kind + ' tbody').append(row);
        updateCounts();
        updatePreview();
        if (focusNew) row.find('input, select').first().trigger('focus');
    }
    function updateCounts() {
        Object.keys(fields).forEach(function (kind) {
            $('#table-' + kind + ' tbody .opening-empty-row').remove();
            var count = $('#table-' + kind + ' tbody tr').length;
            $('[data-row-count="' + kind + '"]').text(count + ' rincian');
            $('#table-' + kind).toggleClass('opening-table-empty', count === 0);
            if (count === 0) {
                $('#table-' + kind + ' tbody').append($('<tr class="opening-empty-row">')
                    .append($('<td>').attr('colspan', fields[kind].length + (editable ? 1 : 0))
                        .addClass('text-center text-muted').text('Belum ada rincian. Gunakan tombol tambah untuk mengisi bagian ini.')));
            }
        });
    }
    function cents(value) {
        var amount = String(value == null ? '' : value);
        if (!/^\d+(?:\.\d{1,2})?$/.test(amount)) return 0;
        var parts = amount.split('.');
        return Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0'));
    }
    function sumMoney(kind, field) {
        var total = 0;
        $('#table-' + kind + ' [data-field="' + field + '"]').each(function () {
            total += cents($(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val());
        });
        return total;
    }
    function displayMoney(value) {
        var whole = String(Math.floor(value / 100)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return 'Rp ' + whole + ',' + String(value % 100).padStart(2, '0');
    }
    function updatePreview() {
        var debit = sumMoney('akun', 'debit') + sumMoney('piutang', 'jumlah')
            + sumMoney('stok', 'nilai') + sumMoney('aset', 'nilai_perolehan');
        var credit = sumMoney('akun', 'kredit') + sumMoney('utang', 'jumlah')
            + sumMoney('aset', 'akumulasi_penyusutan_awal');
        var difference = Math.abs(debit - credit);
        var total = Math.max(debit, credit);
        $('#opening-preview-debit').text(displayMoney(debit));
        $('#opening-preview-credit').text(displayMoney(credit));
        $('#opening-preview-balance').text(displayMoney(difference));
        $('#opening-preview-total').text(displayMoney(total));
        var account = $('#balancing-account option:selected').text();
        $('#opening-preview-account').text(account && $('#balancing-account').val()
            ? account : 'Pilih akun penyeimbang');
    }
    Object.keys(fields).forEach(function (kind) { (opening[kind] || []).forEach(function (row) { addRow(kind, row); }); });
    $('.add-row').on('click', function () { addRow($(this).data('kind'), null, true); });
    $('#opening-form').on('input change', '[data-field], #balancing-account', updatePreview);
    updateCounts();
    updatePreview();
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
                $(this).find('[data-field]').each(function () { row[$(this).data('field')] = $(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val(); });
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
