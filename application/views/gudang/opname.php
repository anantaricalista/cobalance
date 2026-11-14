<h3>Stock Opname</h3>
<?php if ($this->session->flashdata('sukses')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('sukses')) ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
<p>Catat hasil hitung fisik dengan membandingkannya terhadap stok sistem dan batas minimum. Usulan hanya menjadi pemberitahuan; stok dan jurnal baru berubah setelah penyesuaian diproses Akuntansi/Admin.</p>
<form method="post" action="<?= site_url('gudang/opname/simpan') ?>" class="form-horizontal">
    <input type="hidden" name="gudang_form_token" value="<?= html_escape($form_token) ?>">
    <?php if ($this->config->item('csrf_protection')): ?>
        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
    <?php endif; ?>
    <div class="form-group"><label class="col-sm-2 control-label">Produk</label><div class="col-sm-6">
        <select name="produk" class="form-control" required>
            <option value="">-- Pilih produk --</option>
            <?php foreach ($produk as $p): if (!$p->aktif) { continue; } ?>
                <option value="<?= (int) $p->id_produk ?>"><?= html_escape($p->kode_produk . ' - ' . $p->nama_produk . ' (stok: ' . format_jumlah_indonesia($p->jumlah) . ' ' . $p->satuan . '; minimum: ' . format_jumlah_indonesia($p->stok_minimum) . ')') ?></option>
            <?php endforeach; ?>
        </select></div></div>
    <div class="form-group"><label class="col-sm-2 control-label">Jumlah fisik</label><div class="col-sm-3">
        <input type="text" name="jumlah_fisik" class="form-control" inputmode="decimal"
            maxlength="19" pattern="\d{1,14}(,\d{1,4})?" placeholder="Contoh: 12,5"
            title="Gunakan koma untuk desimal, maksimal 4 angka di belakang koma."
            aria-describedby="physical-quantity-help" required>
        <span id="physical-quantity-help" class="help-block">Gunakan koma untuk desimal, misalnya 12,5 atau 0,25.</span></div></div>
    <div class="form-group"><label class="col-sm-2 control-label">Catatan</label><div class="col-sm-6">
        <input type="text" name="catatan" class="form-control" maxlength="255" required></div></div>
    <div class="form-group"><div class="col-sm-offset-2 col-sm-6"><button type="submit" class="btn btn-primary">Kirim usulan</button></div></div>
</form>
<h4>Riwayat usulan</h4>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Waktu</th><th>Produk</th><th>Sistem</th><th>Fisik</th><th>Selisih</th><th>Catatan</th><th>Oleh</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($usulan as $u): ?>
            <tr>
                <td><?= html_escape(format_tanggal_indonesia($u->dibuat_pada)) ?></td>
                <td><?= html_escape($u->kode_produk . ' - ' . $u->nama_produk) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->jumlah_sistem)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->jumlah_fisik)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->selisih_jumlah)) ?></td>
                <td><?= html_escape($u->catatan) ?></td><td><?= html_escape($u->nama_lengkap) ?></td>
                <td><?php
                    $status = array('menunggu' => array('Menunggu', 'warning'), 'diproses' => array('Selesai', 'success'), 'ditolak' => array('Ditolak', 'default'));
                    $label = isset($status[$u->status]) ? $status[$u->status] : array($u->status, 'default');
                ?><span class="label label-<?= $label[1] ?>"><?= html_escape($label[0]) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$usulan): ?><tr><td colspan="8">Belum ada usulan.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
