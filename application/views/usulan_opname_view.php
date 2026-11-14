<h3>Usulan Stock Opname</h3>
<?php if ($this->session->flashdata('sukses')): ?><div class="alert alert-success"><?= html_escape($this->session->flashdata('sukses')) ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= html_escape($this->session->flashdata('error')) ?></div><?php endif; ?>
<p>Usulan dari gudang tidak mengubah stok. Klik <strong>Buat penyesuaian</strong> untuk membuka form penyesuaian yang terisi,
    posting draft-nya, lalu kembali ke sini dan tandai usulan <strong>Selesai</strong>. Stok saat ini dapat sudah berbeda dari saat usulan dibuat.</p>
<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead><tr><th>Waktu</th><th>Produk</th><th>Sistem (saat usulan)</th><th>Fisik</th><th>Stok sekarang</th><th>Catatan</th><th>Oleh</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
            <tr>
                <td><?= html_escape($u->dibuat_pada) ?></td>
                <td><?= html_escape($u->kode_produk . ' - ' . $u->nama_produk) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->jumlah_sistem)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->jumlah_fisik)) ?></td>
                <td><?= html_escape(format_jumlah_indonesia($u->jumlah_sekarang)) ?> <?= html_escape($u->satuan) ?></td>
                <td><?= html_escape($u->catatan) ?></td><td><?= html_escape($u->nama_lengkap) ?></td>
                <td><?= html_escape($u->status) ?></td>
                <td>
                <?php if ($u->status === 'menunggu'): ?>
                    <a class="btn btn-xs btn-primary" href="<?= site_url('penyesuaian_persediaan?' . http_build_query(array(
                        'produk' => (int) $u->id_produk, 'jumlah' => $u->jumlah_fisik, 'ket' => 'Opname gudang #' . (int) $u->id_usulan_opname))) ?>">Buat penyesuaian</a>
                    <?php foreach (array('diproses' => array('Selesai', 'success'), 'ditolak' => array('Tolak', 'danger')) as $status => $label): ?>
                    <form method="post" action="<?= site_url('usulan_opname/ubah') ?>" style="display:inline">
                        <input type="hidden" name="usulan_form_token" value="<?= html_escape($form_token) ?>">
                        <?php if ($this->config->item('csrf_protection')): ?>
                            <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
                        <?php endif; ?>
                        <input type="hidden" name="id" value="<?= (int) $u->id_usulan_opname ?>">
                        <input type="hidden" name="status" value="<?= $status ?>">
                        <button type="submit" class="btn btn-xs btn-<?= $label[1] ?>"><?= $label[0] ?></button>
                    </form>
                    <?php endforeach; ?>
                <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9">Belum ada usulan.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
