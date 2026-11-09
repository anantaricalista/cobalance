<h3>Jurnal Transaksi</h3>
<p>Jurnal ini merupakan hasil posting transaksi dan hanya dapat dilihat atau diekspor. Untuk koreksi manual, gunakan
    <a href="<?= site_url('penyesuaian_akuntansi') ?>">Penyesuaian Akuntansi</a>; jurnal transaksi tidak dapat dibuat,
    diubah, atau dihapus langsung.</p>

<div class="table-responsive">
    <table id="table" class="table table-striped table-bordered" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th style="width:40px;">No</th>
                <th>Tanggal</th>
                <th>Deskripsi</th>
                <th>Referensi</th>
                <th style="width:150px; text-align:center;">Dokumen</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
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
                { "targets": [0, 4], "orderable": false }
            ]
        });

    });
</script>