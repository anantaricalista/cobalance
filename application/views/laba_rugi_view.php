<!-- File: application/views/laba_rugi_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Laporan Laba Rugi</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button class="btn btn-warning btn-sm" onclick="exportPDF()" title="Export PDF">
            <i class="glyphicon glyphicon-file"></i>
        </button>
    </div>
</div>

<form id="filterForm" class="form-inline" style="margin-bottom:20px;">
    <div class="form-group">
        <label for="start_date">Tanggal Mulai:</label>
        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
    </div>
    <div class="form-group">
        <label for="end_date">Tanggal Selesai:</label>
        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="loadLabaRugi()">Tampilkan</button>
</form>

<div id="reportSection" class="table-responsive">
    <!-- Laporan Laba Rugi akan tampil di sini -->
</div>

<script type="text/javascript">
    // Fungsi untuk mengubah tanggal ke format "d Month YYYY" (Bahasa Indonesia)
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

    function loadLabaRugi() {
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();

        $.ajax({
            url: "<?php echo site_url('laba_rugi/ajax_get_laba_rugi'); ?>",
            type: "POST",
            dataType: "JSON",
            data: { start_date: start_date, end_date: end_date },
            success: function (data) {
                var html = '';
                html += '<h3>Periode: ' + formatDateIndo(start_date) + ' s/d ' + formatDateIndo(end_date) + '</h3>';

                // Bagian Pendapatan
                html += '<h4>Pendapatan</h4>';
                html += '<table class="table table-striped table-bordered">';
                html += '<thead><tr><th style="width:80px;">Kode Akun</th><th>Nama Akun</th><th style="width:150px; text-align:right;">Jumlah</th></tr></thead>';
                html += '<tbody>';
                if (data.pendapatan.length > 0) {
                    for (var i = 0; i < data.pendapatan.length; i++) {
                        var row = data.pendapatan[i];
                        html += '<tr>';
                        html += '<td>' + row.kode_akun + '</td>';
                        html += '<td>' + row.nama_akun + '</td>';
                        html += '<td style="text-align:right;">' + formatRupiah(row.amount) + '</td>';
                        html += '</tr>';
                    }
                } else {
                    html += '<tr><td colspan="3" style="text-align:center;">Data tidak ditemukan</td></tr>';
                }
                html += '</tbody>';
                html += '<tfoot><tr style="font-weight:bold;"><td colspan="2" style="text-align:right;">Total Pendapatan</td><td style="text-align:right;">' + formatRupiah(data.total_pendapatan) + '</td></tr></tfoot>';
                html += '</table>';

                // Bagian Beban
                html += '<h4>Beban</h4>';
                html += '<table class="table table-striped table-bordered">';
                html += '<thead><tr><th style="width:80px;">Kode Akun</th><th>Nama Akun</th><th style="width:150px; text-align:right;">Jumlah</th></tr></thead>';
                html += '<tbody>';
                if (data.beban.length > 0) {
                    for (var j = 0; j < data.beban.length; j++) {
                        var row = data.beban[j];
                        html += '<tr>';
                        html += '<td>' + row.kode_akun + '</td>';
                        html += '<td>' + row.nama_akun + '</td>';
                        html += '<td style="text-align:right;">' + formatRupiah(row.amount) + '</td>';
                        html += '</tr>';
                    }
                } else {
                    html += '<tr><td colspan="3" style="text-align:center;">Data tidak ditemukan</td></tr>';
                }
                html += '</tbody>';
                html += '<tfoot><tr style="font-weight:bold;"><td colspan="2" style="text-align:right;">Total Beban</td><td style="text-align:right;">' + formatRupiah(data.total_beban) + '</td></tr></tfoot>';
                html += '</table>';

                // Laba Bersih
                html += '<h4 style="text-align:right;">Laba Bersih: ' + formatRupiah(data.laba_bersih) + '</h4>';

                $('#reportSection').html(html);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error memuat laporan Laba Rugi');
            }
        });
    }

    function exportPDF() {
        var start_date = $('#start_date').val();
        var end_date = $('#end_date').val();
        window.open("<?php echo site_url('laba_rugi/export_pdf'); ?>?start_date=" + start_date + "&end_date=" + end_date, '_blank');
    }

    // Load laporan secara default saat halaman pertama kali dibuka
    $(document).ready(function () {
        loadLabaRugi();
    });
</script>