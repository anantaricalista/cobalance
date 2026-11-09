<script>
$(function () {
    if (!$('body').hasClass('transaction-ui')) return;

    $('h3').each(function (index) {
        var $title = $(this).addClass('module-title').attr('id', 'transaction-page-title-' + index);
        var $intro = $('<div class="module-intro">');
        var $next = $title.next();
        while ($next.length && $next.is('p')) {
            var $current = $next;
            $next = $current.next();
            $intro.append($current);
        }
        if ($intro.children().length) $intro.insertAfter($title);
    });

    $('h4').each(function () {
        var $heading = $(this);
        if ($heading.next('.table-responsive, form').length
            || /riwayat|daftar aset|saldo stok|saldo akun|rincian barang|rincian|alokasi ke tagihan|pembatalan/i.test($heading.text())) {
            $heading.addClass('module-section-title');
        }
    });

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
        var $clear = $('<button type="button" class="btn btn-default history-clear">')
            .append($('<span class="glyphicon glyphicon-remove" aria-hidden="true">'))
            .append(' Bersihkan');
        var $count = $('<span class="history-count" aria-live="polite">');
        var $searchGroup = $('<div class="history-search-group">').append($search, $clear);
        var $statusFilter = null;
        if (statusColumn >= 0) {
            $statusFilter = $('<select class="form-control history-status-filter">')
                .attr('aria-label', 'Saring riwayat berdasarkan status')
                .append($('<option>').val('').text('Semua status'));
            var statuses = {};
            $rows.each(function () {
                var status = $.trim($(this).children('td').eq(statusColumn).text());
                if (status) statuses[status.toLowerCase()] = status;
            });
            Object.keys(statuses).sort().forEach(function (key) {
                $statusFilter.append($('<option>').val(key).text(statuses[key]));
            });
        }
        $tools.append($searchGroup);
        if ($statusFilter) $tools.append($statusFilter);
        $tools.append($count).insertBefore($tableContainer);
        var columnCount = $table.find('thead th').length;
        var $empty = $('<tr class="history-no-results">')
            .append($('<td>').attr('colspan', columnCount).addClass('text-center text-muted')
                .text('Tidak ada transaksi yang cocok dengan pencarian.'));

        function update() {
            var term = $.trim($search.val()).toLowerCase();
            var selectedStatus = $statusFilter ? $statusFilter.val() : '';
            var visible = 0;
            var hasSearch = term !== '';
            $rows.each(function () {
                var rowStatus = statusColumn >= 0
                    ? $.trim($(this).children('td').eq(statusColumn).text()).toLowerCase() : '';
                var match = (!term || $(this).text().toLowerCase().indexOf(term) !== -1)
                    && (!selectedStatus || rowStatus === selectedStatus);
                $(this).toggle(match);
                if (match) visible++;
            });
            $empty.toggle(visible === 0);
            if (visible === 0) $table.find('tbody').append($empty);
            else $empty.detach();
            $empty.children('td').text(hasSearch
                ? 'Tidak ada transaksi yang cocok dengan pencarian.'
                : 'Belum ada transaksi pada riwayat ini.');
            $clear.prop('disabled', !hasSearch);
            $count.text(visible + ' dari ' + $rows.length + ' transaksi');
        }

        $search.on('input', update);
        if ($statusFilter) $statusFilter.on('change', update);
        $clear.on('click', function () {
            $search.val('');
            if ($statusFilter) $statusFilter.val('');
            update();
            $search.trigger('focus');
        });
        update();
        $tableContainer.data('history-enhanced', true);
    });
});
</script>
