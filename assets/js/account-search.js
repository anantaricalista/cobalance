(function () {
    'use strict';

    var $ = window.jQuery;
    var selector = 'select.account-search';
    var invalidFocused = false;
    var errorId = 0;

    if (!$ || !$.fn.select2) {
        console.error('Select2 atau jQuery tidak tersedia; pilihan akun menggunakan select bawaan.');
        return;
    }

    function sync(select) {
        if ($(select).hasClass('select2-hidden-accessible')) {
            $(select).trigger('change.select2');
            if (select.validity.valid || select.matches(':disabled')) clearError(select);
        }
    }

    function clearError(select) {
        var error = select._accountSearchError;
        if (!error) return;
        error.remove();
        select._accountSearchError = null;
        var selection = $(select).next('.select2').find('.select2-selection');
        selection.removeAttr('aria-invalid aria-describedby');
    }

    function setup(select) {
        var $select = $(select);
        if ($select.hasClass('select2-hidden-accessible')) return;
        var modal = $select.closest('.modal');
        var dropdownParent = $select.is('[data-account-dropdown-local]') ? $select.parent()
            : (modal.length ? modal : $(document.body));
        $select.select2({
            width: '100%',
            dropdownParent: dropdownParent,
            language: {
                noResults: function () { return 'Akun tidak ditemukan'; },
                searching: function () { return 'Mencari...'; }
            }
        });
        var label = select.labels && select.labels.length ? select.labels[0].textContent.trim()
            : select.getAttribute('aria-label');
        if (label) $select.next('.select2').find('.select2-selection').attr('aria-label', label);
        $select.on('select2:opening', function (event) {
            if (select.matches(':disabled')) event.preventDefault();
        });
        $select.on('select2:open', function () {
            window.setTimeout(function () {
                if (!$select.data('select2').isOpen()) return;
                var search = document.querySelector('.select2-container--open .select2-search__field');
                if (search) search.focus();
            }, 0);
        });
        $select.on('change', function () {
            if (select.validity.valid) clearError(select);
        });
    }

    function setupWithin(root) {
        if (root.nodeType !== 1) return;
        if (root.matches(selector)) setup(root);
        Array.prototype.forEach.call(root.querySelectorAll(selector), setup);
    }

    function syncWithin(root) {
        Array.prototype.forEach.call(root.querySelectorAll(selector), sync);
    }

    function start() {
        setupWithin(document.body);
        var pending = new Set();
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                var select = mutation.target.closest && mutation.target.closest(selector);
                if (select) pending.add(select);
                if (mutation.type === 'attributes' && mutation.target.tagName === 'FIELDSET') {
                    Array.prototype.forEach.call(mutation.target.querySelectorAll(selector), function (item) {
                        pending.add(item);
                    });
                }
                Array.prototype.forEach.call(mutation.addedNodes, setupWithin);
            });
            pending.forEach(sync);
            pending.clear();
        });
        observer.observe(document.body, {
            subtree: true, childList: true, characterData: true,
            attributes: true, attributeFilter: ['disabled', 'required']
        });

        document.addEventListener('invalid', function (event) {
            var select = event.target;
            if (!select.matches(selector) || !$(select).hasClass('select2-hidden-accessible')) return;
            event.preventDefault();
            if (!select._accountSearchError) {
                var error = document.createElement('span');
                error.id = 'account-search-error-' + (++errorId);
                error.className = 'account-search-error';
                error.setAttribute('role', 'alert');
                $(select).next('.select2').after(error);
                select._accountSearchError = error;
            }
            select._accountSearchError.textContent = select.validationMessage;
            $(select).next('.select2').find('.select2-selection')
                .attr({'aria-invalid': 'true', 'aria-describedby': select._accountSearchError.id});
            if (!invalidFocused) {
                invalidFocused = true;
                $(select).select2('open');
                window.setTimeout(function () { invalidFocused = false; }, 0);
            }
        }, true);

        document.addEventListener('reset', function (event) {
            window.setTimeout(function () {
                syncWithin(event.target);
                Array.prototype.forEach.call(event.target.querySelectorAll(selector), clearError);
            }, 0);
        });
        $(document).on('show.bs.modal', '.modal', function () { syncWithin(this); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
