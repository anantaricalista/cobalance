<!-- File: application/views/neraca_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Neraca (Balance Sheet)</h1>
    </div>
    <div class="col-xs-12 col-sm-6 text-right" style="padding-top:20px;">
        <button class="btn btn-warning btn-sm" onclick="exportPDF()" title="Export PDF">
            <i class="glyphicon glyphicon-file"></i>
        </button>
    </div>
</div>

<form id="filterForm" class="form-inline" style="margin-bottom:20px;">
    <div class="form-group">
        <label for="end_date">Tanggal Neraca:</label>
        <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="loadNeraca()">Tampilkan</button>
</form>

<div id="reportSection" class="table-responsive">
    <!-- Data Neraca akan muncul di sini -->
</div>

<script type="text/javascript">
    // Fungsi untuk mengubah tanggal ke format "d Month YYYY" dalam Bahasa Indonesia
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

    function formatRupiah(num) {
        var formatted = parseFloat(num).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return '<div style="display: flex; justify-content: space-between; width: 100%;">' +
            '<span>Rp.</span>' +
            '<span>' + formatted + '</span>' +
            '</div>';
    }

    function loadNeraca() {
        var end_date = $('#end_date').val();
        $.ajax({
            url: "<?php echo site_url('neraca/ajax_get_neraca'); ?>",
            type: "POST",
            dataType: "JSON",
            data: { end_date: end_date },
            success: function (data) {
                var html = '';
                html += '<h3>Periode: ' + formatDateIndo(end_date) + '</h3>';
                html += '<div class="row">';

                // Kolom Aset
                html += '  <div class="col-xs-12 col-sm-6">';
                html += '    <h4>Aset</h4>';
                html += '    <table class="table table-striped table-bordered">';
                html += '      <thead><tr><th style="width:80px;">Kode Akun</th><th>Nama Akun</th><th style="width:150px; text-align:right;">Saldo</th></tr></thead>';
                html += '      <tbody>';
                var total_aset = 0;
                for (var i = 0; i < data.aset.length; i++) {
                    var row = data.aset[i];
                    html += '<tr>';
                    html += '<td>' + row.kode_akun + '</td>';
                    html += '<td>' + row.nama_akun + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.total_balance) + '</td>';
                    html += '</tr>';
                    total_aset += parseFloat(row.total_balance);
                }
                html += '      </tbody>';
                html += '      <tfoot><tr style="font-weight:bold;"><td colspan="2" style="text-align:right;">Total Aset</td><td style="text-align:right;">' + formatRupiah(total_aset) + '</td></tr></tfoot>';
                html += '    </table>';
                html += '  </div>';

                // Kolom Liabilitas dan Ekuitas dalam satu kolom
                html += '  <div class="col-xs-12 col-sm-6">';
                // Tabel Liabilitas
                html += '    <h4>Liabilitas</h4>';
                html += '    <table class="table table-striped table-bordered">';
                html += '      <thead><tr><th style="width:80px;">Kode Akun</th><th>Nama Akun</th><th style="width:150px; text-align:right;">Saldo</th></tr></thead>';
                html += '      <tbody>';
                var total_liabilitas = 0;
                for (var j = 0; j < data.liabilitas.length; j++) {
                    var row = data.liabilitas[j];
                    html += '<tr>';
                    html += '<td>' + row.kode_akun + '</td>';
                    html += '<td>' + row.nama_akun + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.total_balance) + '</td>';
                    html += '</tr>';
                    total_liabilitas += parseFloat(row.total_balance);
                }
                html += '      </tbody>';
                html += '      <tfoot><tr style="font-weight:bold;"><td colspan="2" style="text-align:right;">Total Liabilitas</td><td style="text-align:right;">' + formatRupiah(total_liabilitas) + '</td></tr></tfoot>';
                html += '    </table>';

                // Tabel Ekuitas
                html += '    <h4>Ekuitas</h4>';
                html += '    <table class="table table-striped table-bordered">';
                html += '      <thead><tr><th style="width:80px;">Kode Akun</th><th>Nama Akun</th><th style="width:150px; text-align:right;">Saldo</th></tr></thead>';
                html += '      <tbody>';
                var total_ekuitas = 0;
                for (var k = 0; k < data.ekuitas.length; k++) {
                    var row = data.ekuitas[k];
                    html += '<tr>';
                    html += '<td>' + row.kode_akun + '</td>';
                    html += '<td>' + row.nama_akun + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.total_balance) + '</td>';
                    html += '</tr>';
                    total_ekuitas += parseFloat(row.total_balance);
                }
                html += '      </tbody>';
                html += '      <tfoot><tr style="font-weight:bold;"><td colspan="2" style="text-align:right;">Total Ekuitas</td><td style="text-align:right;">' + formatRupiah(total_ekuitas) + '</td></tr></tfoot>';
                html += '    </table>';
                html += '  </div>';
                html += '</div>';

                $('#reportSection').html(html);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error memuat data Neraca');
            }
        });
    }

    function exportPDF() {
        var end_date = $('#end_date').val();
        window.open("<?php echo site_url('neraca/export_pdf'); ?>?end_date=" + end_date, '_blank');
    }

    // Muat data neraca saat halaman pertama kali dibuka
    $(document).ready(function () {
        loadNeraca();
    });
</script>