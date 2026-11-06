<!-- File: application/views/bagan_akun_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Bagan Akun (Chart of Accounts)</h1>
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
    <button type="button" class="btn btn-primary btn-sm" onclick="loadChart()">Tampilkan</button>
</form>

<div id="reportSection" class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width:60px;">No</th>
                <th style="width:100px;">Kode Akun</th>
                <th>Nama Akun</th>
                <th style="width:120px;">Tipe Akun</th>
                <th style="width:150px; text-align:right;">Saldo</th>
            </tr>
        </thead>
        <tbody id="chartBody">
            <!-- Data Bagan Akun akan muncul di sini -->
        </tbody>
    </table>
</div>

<script type="text/javascript">
    // Fungsi untuk memformat angka ke format Rupiah
    function formatRupiah(num) {
        return "Rp " + parseFloat(num).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Fungsi untuk mengubah tanggal ke format "d Month YYYY" (untuk header, jika diperlukan)
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

    // Fungsi untuk memuat data Bagan Akun melalui AJAX
    function loadChart() {
        var end_date = $('#end_date').val();
        $.ajax({
            url: "<?php echo site_url('bagan_akun/ajax_get_chart'); ?>",
            type: "POST",
            dataType: "JSON",
            data: { end_date: end_date },
            success: function (data) {
                var html = '';
                var no = 1;
                // Tampilkan header periode
                html += '<tr><td colspan="5" style="text-align:center;"><em>Periode: ' + formatDateIndo(end_date) + '</em></td></tr>';

                // Buat mapping akun berdasarkan id untuk menghitung tingkat indentasi
                var accountsById = {};
                data.forEach(function (row) {
                    accountsById[row.id_akun] = row;
                });

                // Fungsi untuk menghitung level indentasi
                function getIndentationLevel(account) {
                    var level = 0;
                    // Selama ada induk dan induk tersebut ditemukan dalam mapping
                    while (account.id_akun_induk && accountsById[account.id_akun_induk]) {
                        level++;
                        account = accountsById[account.id_akun_induk];
                    }
                    return level;
                }

                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    var level = getIndentationLevel(row);
                    var indent = "";
                    for (var j = 0; j < level; j++) {
                        indent += "- ";
                    }
                    html += '<tr>';
                    html += '<td>' + no++ + '</td>';
                    html += '<td>' + row.kode_akun + '</td>';
                    html += '<td style="text-align:left;">' + indent + row.nama_akun + '</td>';
                    html += '<td>' + row.tipe_akun + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.saldo) + '</td>';
                    html += '</tr>';
                }
                $('#chartBody').html(html);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error memuat data Bagan Akun');
            }
        });
    }

    function exportPDF() {
        var end_date = $('#end_date').val();
        window.open("<?php echo site_url('bagan_akun/export_pdf'); ?>?end_date=" + end_date, '_blank');
    }

    // Muat data Bagan Akun saat halaman pertama kali dibuka
    $(document).ready(function () {
        loadChart();
    });
</script>