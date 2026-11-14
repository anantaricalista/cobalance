<?php
$trx = $opening['transaksi'];
$correcting = $trx && $trx->status === 'diposting' && !empty($can_correct_opening);
$editable = !$trx || $trx->status === 'draft' || $correcting;
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
<p class="text-muted" role="note">Nominal: pemisah ribuan ditambahkan otomatis; gunakan koma untuk desimal, misalnya <strong>1.000,50</strong>.</p>
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
                <span class="opening-eyebrow"><?= $correcting ? 'KOREKSI SALDO AWAL' : ($editable ? 'FORMULIR PEMBUKAAN' : 'SALDO YANG TELAH DICATAT') ?></span>
                <h4><?= $correcting ? 'Perbarui rincian saldo awal' : ($editable ? 'Rincian saldo awal' : 'Rincian transaksi') ?></h4>
            </div>
            <?php if ($trx): ?>
                <span class="opening-date-label"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span> <?= html_escape(format_tanggal_indonesia($trx->tanggal)) ?></span>
            <?php endif; ?>
        </div>
        <div class="opening-basics">
            <div class="form-group">
                <label for="opening-date">Tanggal pembukaan</label>
                <input class="form-control" id="opening-date" type="date" required value="<?= html_escape($trx ? $trx->tanggal : date('Y-m-d')) ?>" <?= $correcting ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label for="balancing-account">Akun ekuitas penyeimbang</label>
                <select class="form-control account-search" id="balancing-account" required></select>
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
        <?php if ($correcting): ?>
            <section class="opening-cancel-panel">
                <h4><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> Koreksi mengubah jurnal asli</h4>
                <p>Koreksi akan memperbarui jurnal saldo awal pada tanggal pembukaan, sehingga laporan historis dapat berubah. Jurnal HPP stok tidak dihitung ulang; jadwal dan jurnal penyusutan akan dihitung ulang. Tinjau dampaknya dan pastikan periode terkait terbuka sebelum melanjutkan.</p>
                <div class="form-group">
                    <label for="opening-correction-reason">Alasan koreksi</label>
                    <textarea id="opening-correction-reason" class="form-control" maxlength="1000" required></textarea>
                </div>
                <label><input id="opening-period-confirm" type="checkbox" value="1" required>
                    Saya sudah memastikan secara manual bahwa periode terkait terbuka untuk koreksi.</label>
            </section>
        <?php endif; ?>
        <?php if ($editable): ?>
            <div class="opening-save-bar">
                <span class="text-muted"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> <?= $correcting ? 'Pratinjau dampak wajib ditinjau sebelum jurnal diperbarui.' : 'Periksa kembali tanggal dan seluruh rincian sebelum menyimpan.' ?></span>
                <button type="submit" class="btn btn-primary btn-lg"><span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span> <?= $correcting ? 'Pratinjau dan koreksi jurnal' : 'Simpan draft saldo awal' ?></button>
            </div>
        <?php endif; ?>
    </fieldset>
</form>
<?php if ($trx && $trx->status === 'draft'): ?>
    <div class="opening-action-panel">
        <?php if ($this->can_post_transactions): ?>
            <div><strong>Draft siap ditinjau?</strong><span>Pastikan semua perubahan telah disimpan sebelum posting.</span></div>
            <button type="button" id="post-opening" class="btn btn-success"><span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Posting saldo awal</button>
        <?php else: ?>
            <div><strong>Draft tersimpan</strong><span>Menunggu posting oleh Akuntansi atau Admin Perusahaan.</span></div>
        <?php endif; ?>
    </div>
<?php elseif ($trx && $trx->status === 'diposting'): ?>
    <?php if ($this->can_post_transactions): ?>
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
    <?php else: ?>
        <p class="text-muted">Pembatalan transaksi hanya dapat dilakukan oleh Akuntansi atau Admin Perusahaan.</p>
    <?php endif; ?>
<?php endif; ?>
<?php if (!empty($opening['koreksi'])): ?>
    <section class="opening-cancel-panel">
        <h4>Riwayat koreksi saldo awal</h4>
        <div class="table-responsive"><table class="table table-bordered table-condensed">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Alasan</th></tr></thead>
            <tbody>
            <?php foreach ($opening['koreksi'] as $correction): ?>
                <tr><td><?= html_escape($correction['dibuat_pada']) ?></td>
                    <td><?= html_escape($correction['pengguna']) ?> (#<?= (int) $correction['id_pengguna'] ?>)</td>
                    <td><?= html_escape($correction['alasan']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </section>
<?php endif; ?>
<script>
$(function () {
    var opening = <?= json_encode($opening, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var options = <?= json_encode(array('akun' => $account_options, 'penyeimbang' => $equity_options, 'piutang' => $customer_options, 'utang' => $supplier_options, 'stok' => $product_options), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var assetAccounts = <?= json_encode($asset_account_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var editable = <?= $editable ? 'true' : 'false' ?>;
    var correcting = <?= $correcting ? 'true' : 'false' ?>;
    var csrf = <?php if ($this->config->item('csrf_protection')): ?><?= json_encode(array(
        'name' => $this->security->get_csrf_token_name(), 'value' => $this->security->get_csrf_hash())) ?><?php else: ?>null<?php endif; ?>;
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
                if (kind === 'akun' || kind === 'aset') input.addClass('account-search').attr('aria-label', 'Akun ' + field[0].replace(/_/g, ' '));
                fillSelect(input, kind === 'aset' ? assetAccounts[field[0]] : options[kind], values[field[0]]);
            } else {
                input = $('<input class="form-control">').attr('type', field[1] === 'date' ? 'date' : 'text');
                if (field[1] === 'money' || field[1] === 'quantity') input.attr('inputmode', 'decimal').attr('placeholder', field[1] === 'money' ? '0,00' : '0.0000');
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
        if (kind === 'aset' && values.id_aset_tetap != null) {
            row.append($('<input type="hidden" data-field="id_aset_tetap">').val(values.id_aset_tetap));
        }
        if (editable) row.append($('<td>').append($('<button type="button" class="btn btn-danger btn-sm">')
            .attr('aria-label', 'Hapus rincian ' + kind).append($('<span class="glyphicon glyphicon-trash" aria-hidden="true">'))
            .on('click', function () { row.remove(); updateCounts(); updatePreview(); })));
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
        if (amount === '') return BigInt(0);
        if (!/^\d+(?:\.\d{1,2})?$/.test(amount)) return null;
        return MoneyInput.cents(amount);
    }
    function sumMoney(kind, field) {
        var total = BigInt(0);
        $('#table-' + kind + ' [data-field="' + field + '"]').each(function () {
            var amount = cents($(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val());
            total = total === null || amount === null ? null : total + amount;
        });
        return total;
    }
    function displayMoney(value) {
        return 'Rp ' + MoneyInput.display(MoneyInput.fromCents(value));
    }
    function updatePreview() {
        var debitParts = [sumMoney('akun', 'debit'), sumMoney('piutang', 'jumlah'),
            sumMoney('stok', 'nilai'), sumMoney('aset', 'nilai_perolehan')];
        var creditParts = [sumMoney('akun', 'kredit'), sumMoney('utang', 'jumlah'),
            sumMoney('aset', 'akumulasi_penyusutan_awal')];
        if (debitParts.concat(creditParts).some(function (value) { return value === null; })) {
            $('#opening-preview-debit, #opening-preview-credit, #opening-preview-balance, #opening-preview-total')
                .text('Tidak dapat dihitung');
            return;
        }
        var debit = debitParts.reduce(function (a, b) { return a + b; }, BigInt(0));
        var credit = creditParts.reduce(function (a, b) { return a + b; }, BigInt(0));
        var difference = debit >= credit ? debit - credit : credit - debit;
        var total = debit >= credit ? debit : credit;
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
        if (csrf) data[csrf.name] = csrf.value;
        $('button').prop('disabled', true);
        $.post(url, data).done(function (response) {
            if (response && response.csrf) csrf = response.csrf;
            location.reload();
        }).fail(function (xhr) {
            if (xhr.responseJSON && xhr.responseJSON.csrf) csrf = xhr.responseJSON.csrf;
            $('#opening-message').empty().append($('<div class="alert alert-danger">').text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Permintaan gagal. Silakan coba kembali.'));
            $('button').prop('disabled', false);
        });
    }
    $('#opening-form').on('submit', function (event) {
        event.preventDefault();
        var data = {penyeimbang: $('#balancing-account').val()};
        Object.keys(fields).forEach(function (kind) {
            data[kind] = [];
            $('#table-' + kind + ' tbody tr:not(.opening-empty-row)').each(function () {
                var row = {};
                $(this).find('[data-field]').each(function () { row[$(this).data('field')] = $(this).is('[data-money]') ? MoneyInput.value(this) : $(this).val(); });
                data[kind].push(row);
            });
        });
        if (correcting) {
            var reason = $.trim($('#opening-correction-reason').val());
            if (!reason || !$('#opening-period-confirm').prop('checked')) {
                $('#opening-message').empty().append($('<div class="alert alert-warning">')
                    .text('Isi alasan dan konfirmasikan secara manual bahwa periode terkait terbuka.'));
                return;
            }
            var previewRequest = {data: JSON.stringify(data)};
            if (csrf) previewRequest[csrf.name] = csrf.value;
            $.post(<?= json_encode(site_url('saldo_awal/dampak_koreksi')) ?>, previewRequest).done(function (response) {
                if (response && response.csrf) csrf = response.csrf;
                var impact = response.data || {};
                var warning = (impact.peringatan || []).join('\n- ');
                var summary = (impact.ringkasan || []).join('\n- ');
                var message = 'Dampak koreksi pada jurnal tanggal ' + formatTanggalIndonesia(impact.tanggal_jurnal) + ':\n- '
                    + summary + '\n- '
                    + (impact.jumlah_mutasi_stok_terdampak || 0) + ' mutasi stok terkait.\n- '
                    + (impact.jumlah_periode_penyusutan_tercatat || 0) + ' catatan periode penyusutan terkait.\n- '
                    + (impact.jumlah_faktur_saldo_awal || 0) + ' faktur saldo awal.\n\n- ' + warning
                    + '\n\nLanjutkan memperbarui jurnal asli?';
                if (window.confirm(message)) {
                    request(<?= json_encode(site_url('saldo_awal/koreksi')) ?>, {
                        data: JSON.stringify(data), alasan: reason, konfirmasi_periode: '1',
                        baseline: impact.baseline
                    });
                }
            }).fail(function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.csrf) csrf = xhr.responseJSON.csrf;
                $('#opening-message').empty().append($('<div class="alert alert-danger">')
                    .text(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Pratinjau koreksi gagal.'));
            });
            return;
        }
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
