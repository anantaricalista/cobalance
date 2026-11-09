<script>
$(function () {
    if (!$('body').hasClass('transaction-ui')) return;

    $('.table-responsive').each(function () {
        var $table = $(this).find('table').first();
        if (!$table.length) return;

        var statusColumn = -1;
        $table.find('thead th').each(function (index) {
            if ($.trim($(this).text()).toLowerCase() === 'status') statusColumn = index;
        });
        if (statusColumn >= 0) {
            $table.find('tbody tr').each(function () {
                var $cell = $(this).children('td').eq(statusColumn);
                var status = $.trim($cell.text()).toLowerCase();
                if (!/^(draft|diposting|dibatalkan)$/.test(status)) return;
                var style = status === 'draft' ? 'is-draft' :
                    (status === 'diposting' ? 'is-posted' : 'is-cancelled');
                $cell.empty().append($('<span class="transaction-status">')
                    .addClass(style).text(status));
            });
        }
    });

    $('h4').each(function () {
        if (!/riwayat/i.test($(this).text())) return;
        var $tableContainer = $(this).nextAll('.table-responsive').first();
        if (!$tableContainer.length || $tableContainer.data('history-enhanced')) return;
        var $rows = $tableContainer.find('tbody tr').filter(function () {
            return !$(this).find('td[colspan]').length;
        });
        if (!$rows.length) return;

        var $tools = $('<div class="history-tools">');
        var $search = $('<input type="search" class="form-control" autocomplete="off">')
            .attr('aria-label', 'Cari transaksi pada riwayat')
            .attr('placeholder', 'Cari nomor, keterangan, atau status...');
        var $count = $('<span class="history-count" aria-live="polite">');
        $tools.append($search, $count).insertBefore($tableContainer);
        var columnCount = $table.find('thead th').length;
        var $empty = $('<tr class="history-no-results">')
            .append($('<td>').attr('colspan', columnCount).addClass('text-center text-muted')
                .text('Tidak ada transaksi yang cocok dengan pencarian.'));

        function update() {
            var term = $.trim($search.val()).toLowerCase();
            var visible = 0;
            $rows.each(function () {
                var match = !term || $(this).text().toLowerCase().indexOf(term) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $empty.toggle(visible === 0);
            if (visible === 0) $table.find('tbody').append($empty);
            else $empty.detach();
            $count.text(visible + ' dari ' + $rows.length + ' transaksi');
        }

        $search.on('input', update);
        update();
        $tableContainer.data('history-enhanced', true);
    });
});
</script>
