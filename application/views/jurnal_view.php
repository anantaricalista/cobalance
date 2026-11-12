<h3>Jurnal Transaksi</h3>
<p>Jurnal transaksi hanya dapat dilihat dan diekspor. Pilih <strong>Detail</strong> untuk memeriksa akun serta nilai
    debit/kredit, atau <strong>PDF</strong> untuk membuka dokumen. Untuk koreksi manual, gunakan
    <a href="<?= site_url('penyesuaian_akuntansi') ?>">Penyesuaian Akuntansi</a>.</p>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th style="width:190px; text-align:center;">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<div class="modal fade" id="journal-detail-modal" tabindex="-1" role="dialog" aria-labelledby="journal-detail-title">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="journal-detail-title">Detail jurnal</h4>
            </div>
            <div class="modal-body">
                <div id="journal-detail-message" role="status" aria-live="polite"></div>
                <dl class="dl-horizontal">
                    <dt>Tanggal</dt><dd id="journal-detail-date">-</dd>
                    <dt>Deskripsi</dt><dd id="journal-detail-description">-</dd>
                    <dt>Referensi</dt><dd id="journal-detail-reference">-</dd>
                </dl>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th style="width: 110px;">Kode akun</th><th>Nama akun</th>
                            <th class="text-right">Debit</th><th class="text-right">Kredit</th></tr></thead>
                        <tbody id="journal-detail-rows"></tbody>
                        <tfoot><tr><th colspan="2" class="text-right">Total</th>
                            <th id="journal-detail-debit" class="text-right">Rp 0,00</th>
                            <th id="journal-detail-credit" class="text-right">Rp 0,00</th></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
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
                { "targets": [0, 4], "orderable": false },
                {
                    "targets": 1,
                    "render": function (data, type) {
                        if (type !== 'display' || !data) return data || '';
                        var parts = data.split('-');
                        if (parts.length !== 3) return $('<span>').text(data).html();
                        var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                        return isNaN(date.getTime()) ? $('<span>').text(data).html() :
                            date.toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'});
                    }
                },
                {
                    "targets": 3,
                    "render": function (data, type) {
                        return type === 'display' && !data ? '<span class="text-muted">—</span>' : data;
                    }
                }
            ],
            "language": {
                "processing": "Memuat jurnal...",
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ jurnal",
                "info": "Menampilkan _START_–_END_ dari _TOTAL_ jurnal",
                "infoEmpty": "Belum ada jurnal",
                "infoFiltered": "(disaring dari _MAX_ jurnal)",
                "zeroRecords": "Jurnal tidak ditemukan",
                "emptyTable": "Belum ada jurnal transaksi",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        $('#table').on('click', '.view-journal', function () {
            var id = $(this).data('jurnal-id');
            var button = $(this);
            button.prop('disabled', true);
            $('#journal-detail-message').empty();
            $('#journal-detail-rows').empty();
            $('#journal-detail-date, #journal-detail-description, #journal-detail-reference').text('-');
            $('#journal-detail-debit, #journal-detail-credit').text('Memuat...');
            $('#journal-detail-modal').modal('show');

            $.getJSON(<?= json_encode(site_url('jurnal/ajax_detail')) ?> + '/' + encodeURIComponent(id))
                .done(function (journal) {
                    $('#journal-detail-date').text(journal.tanggal || '-');
                    $('#journal-detail-description').text(journal.deskripsi || '-');
                    $('#journal-detail-reference').text(journal.referensi || '-');
                    var debitTotal = BigInt(0);
                    var creditTotal = BigInt(0);
                    (journal.details || []).forEach(function (detail) {
                        var debit = moneyCents(detail.debit);
                        var credit = moneyCents(detail.kredit);
                        debitTotal += debit;
                        creditTotal += credit;
                        var row = $('<tr>');
                        row.append($('<td>').text(detail.kode_akun || '-'));
                        row.append($('<td>').text(detail.nama_akun || '-'));
                        row.append($('<td class="text-right">').text(formatMoney(debit)));
                        row.append($('<td class="text-right">').text(formatMoney(credit)));
                        $('#journal-detail-rows').append(row);
                    });
                    if (!journal.details || !journal.details.length) {
                        $('#journal-detail-rows').append($('<tr>').append(
                            $('<td colspan="4" class="text-center text-muted">').text('Jurnal ini belum memiliki rincian.')));
                    }
                    $('#journal-detail-debit').text(formatMoney(debitTotal));
                    $('#journal-detail-credit').text(formatMoney(creditTotal));
                })
                .fail(function (xhr) {
                    $('#journal-detail-message').empty().append(
                        $('<div class="alert alert-danger">').text(xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message : 'Detail jurnal gagal dimuat.'));
                    $('#journal-detail-debit, #journal-detail-credit').text('-');
                })
                .always(function () {
                    button.prop('disabled', false);
                });
        });
    });

    function moneyCents(value) {
        return MoneyInput.cents(value == null ? '0' : value);
    }

    function formatMoney(cents) {
        return 'Rp ' + MoneyInput.display(MoneyInput.fromCents(cents));
    }

</script>