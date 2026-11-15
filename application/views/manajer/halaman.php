<?php
$params = isset($start) ? array('mulai' => $start, 'akhir' => $end) : array();
?>
<p>
    <?php if ($page > 1): $params['halaman'] = $page - 1; ?><a class="btn btn-default" href="<?= html_escape(site_url($route . '?' . http_build_query($params))) ?>">Sebelumnya</a><?php endif; ?>
    Halaman <?= (int) $page ?>
    <?php if ($more && $page < 999999): $params['halaman'] = $page + 1; ?><a class="btn btn-default" href="<?= html_escape(site_url($route . '?' . http_build_query($params))) ?>">Berikutnya</a><?php endif; ?>
</p>
