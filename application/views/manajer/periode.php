<form class="form-inline" method="get" style="margin:15px 0">
    <label for="owner-start">Dari</label>
    <input id="owner-start" class="form-control" type="date" name="mulai" value="<?= html_escape($start) ?>" required>
    <label for="owner-end">Sampai</label>
    <input id="owner-end" class="form-control" type="date" name="akhir" value="<?= html_escape($end) ?>" required>
    <button class="btn btn-primary" type="submit">Tampilkan</button>
</form>
