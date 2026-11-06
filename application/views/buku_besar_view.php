<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Buku Besar (General Ledger)</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button class="btn btn-warning btn-sm" onclick="exportPDF()" title="Export PDF">
            <i class="glyphicon glyphicon-file"></i>
        </button>
    </div>
</div>

<form id="filterForm" class="form-inline" style="margin-bottom:20px;">
    <div class="form-group">
        <label for="id_akun">Akun:</label>
        <select name="id_akun" id="id_akun" class="form-control">
            <option value="">-- Pilih Akun --</option>
            <?php foreach ($akun_list as $akun): ?>
                <option value="<?php echo $akun->id_akun; ?>">
                    <?php echo $akun->nama_akun . " (" . $akun->kode_akun . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="start_date">Tanggal Mulai:</label>
        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-01'); ?>">
    </div>
    <div class="form-group">
        <label for="end_date">Tanggal Selesai:</label>
        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo date('Y-m-t'); ?>">
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="loadLedger()">Tampilkan</button>
</form>

<div class="table-responsive">
    <table id="ledgerTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th>Debit</th>
                <th>Kredit</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody id="ledgerBody">
            <!-- Data ledger akan muncul di sini -->
        </tbody>
    </table>
</div>

<script type="text/javascript">
    // Fungsi untuk mengubah tanggal ke format "d Month YYYY" (nama bulan Indonesia)
    function formatDateIndo(dateStr) {
        var parts = dateStr.split("-");
        var year = parts[0];
        var month = parseInt(parts[1], 10);
        var day = parseInt(parts[2], 10);
        var monthNames = [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];
        return day + " " + monthNames[month - 1] + " " + year;
    }

    // Fungsi untuk memformat angka ke format Rupiah
    function formatRupiah(num) {
        return "Rp " + parseFloat(num).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function loadLedger() {
        var id_akun = $('#id_akun').val();
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();

        if (id_akun == '') {
            alert('Pilih akun terlebih dahulu.');
            return;
        }

        $.ajax({
            url: "<?php echo site_url('buku_besar/ajax_get_ledger'); ?>",
            type: "POST",
            dataType: "JSON",
            data: { id_akun: id_akun, start_date: start_date, end_date: end_date },
            success: function (data) {
                var opening = parseFloat(data.opening);
                var transactions = data.transactions;
                var running_balance = opening;
                var html = '';

                // Baris saldo awal
                html += '<tr>';
                html += '<td>0</td>';
                html += '<td colspan="4" style="text-align:center;"><em>Saldo Awal</em></td>';
                html += '<td></td>';
                html += '<td style="text-align:right;">' + formatRupiah(running_balance) + '</td>';
                html += '</tr>';

                for (var i = 0; i < transactions.length; i++) {
                    var trx = transactions[i];
                    var debit = parseFloat(trx.debit);
                    var kredit = parseFloat(trx.kredit);
                    running_balance += (debit - kredit);
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>' + formatDateIndo(trx.tanggal) + '</td>';
                    html += '<td>' + trx.deskripsi + '</td>';
                    html += '<td>' + trx.referensi + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(debit) + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(kredit) + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(running_balance) + '</td>';
                    html += '</tr>';
                }

                $('#ledgerBody').html(html);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error memuat data ledger');
            }
        });
    }

    function exportPDF() {
        var id_akun = $('#id_akun').val();
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();

        if (id_akun == '') {
            alert('Pilih akun terlebih dahulu.');
            return;
        }

        window.open("<?php echo site_url('buku_besar/export_pdf'); ?>?id_akun=" + id_akun + "&start_date=" + start_date + "&end_date=" + end_date, '_blank');
    }
</script>