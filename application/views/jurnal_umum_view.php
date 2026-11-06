<!-- File: application/views/jurnal_umum_view.php -->
<div class="row" style="margin-bottom:10px;">
    <div class="col-xs-12 col-sm-6">
        <h1>Jurnal Umum</h1>
    </div>
</div>

<div class="table-responsive">
    <table id="journalTable" class="table table-striped table-bordered">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th style="width:120px;">Tanggal</th>
                <th>Deskripsi</th>
                <th style="width:120px;">Referensi</th>
                <th style="width:60px;">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal untuk menampilkan detail jurnal -->
<div class="modal fade" id="modal_detail" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detail Jurnal</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th style="width:5%;">No</th>
                                <th style="width:20%;">Kode Akun</th>
                                <th>Nama Akun</th>
                                <th style="width:20%;">Debit</th>
                                <th style="width:20%;">Kredit</th>
                            </tr>
                        </thead>
                        <tbody id="detailBody">
                            <!-- Data detail jurnal akan muncul di sini -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                    <i class="glyphicon glyphicon-remove"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var table;
    $(document).ready(function () {
        table = $('#journalTable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "<?php echo site_url('jurnal_umum/ajax_list'); ?>",
                "type": "POST"
            },
            "columnDefs": [
                { "targets": [0, 4], "orderable": false },
                {
                    "targets": 1,  // Kolom tanggal
                    "render": function (data, type, row, meta) {
                        return formatDateIndo(data);
                    }
                }
            ],
            "order": [[1, "asc"]]
        });
    });

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

    // Fungsi untuk memformat angka ke format Rupiah
    function formatRupiah(num) {
        return "Rp " + parseFloat(num).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function view_detail(id) {
        $.ajax({
            url: "<?php echo site_url('jurnal_umum/ajax_detail/'); ?>" + id,
            type: "GET",
            dataType: "JSON",
            success: function (data) {
                var html = '';
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    html += '<tr>';
                    html += '<td>' + (i + 1) + '</td>';
                    html += '<td>' + row.kode_akun + '</td>';
                    html += '<td>' + row.nama_akun + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.debit) + '</td>';
                    html += '<td style="text-align:right;">' + formatRupiah(row.kredit) + '</td>';
                    html += '</tr>';
                }
                $('#detailBody').html(html);
                $('#modal_detail').modal('show');
            },
            error: function () {
                alert('Error mengambil data detail jurnal.');
            }
        });
    }
</script>