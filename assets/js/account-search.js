(function () {
    'use strict';

    var selector = 'select.account-search';

    function update(select) {
        var control = select._accountSearch;
        if (!control) return;
        var term = control.input.value.trim().toLocaleLowerCase();
        var matches = 0;
        var keptSelection = false;
        Array.prototype.forEach.call(select.options, function (option) {
            var matchesTerm = !term || option.textContent.toLocaleLowerCase().indexOf(term) !== -1;
            if (option.value && !option.disabled && matchesTerm) matches++;
            var keep = option.selected && !matchesTerm;
            option.hidden = !matchesTerm && !keep && option.value !== '';
            if (keep && option.value) keptSelection = true;
        });
        control.input.disabled = select.matches(':disabled');
        control.status.textContent = term
            ? matches + ' akun cocok' + (keptSelection ? '; pilihan saat ini tetap ditampilkan' : '') + '.'
            : '';
    }

    function setup(select) {
        if (select._accountSearch) return;
        var label = select.labels && select.labels.length ? select.labels[0].textContent.trim()
            : select.getAttribute('aria-label') || 'akun';
        var wrapper = document.createElement('div');
        wrapper.className = 'account-search-control';
        var input = document.createElement('input');
        input.type = 'search';
        input.className = 'form-control input-sm';
        input.placeholder = 'Cari kode atau nama akun';
        input.setAttribute('aria-label', 'Cari ' + label);
        input.autocomplete = 'off';
        var status = document.createElement('span');
        status.className = 'account-search-status';
        status.setAttribute('aria-live', 'polite');
        wrapper.appendChild(input);
        wrapper.appendChild(status);
        select.parentNode.insertBefore(wrapper, select);
        select._accountSearch = {input: input, status: status};
        input.addEventListener('input', function () { update(select); });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === 'ArrowDown') {
                event.preventDefault();
                select.focus();
            }
        });
        select.addEventListener('change', function () { update(select); });
        update(select);
    }

    function setupWithin(root) {
        if (root.nodeType !== 1) return;
        if (root.matches(selector)) setup(root);
        Array.prototype.forEach.call(root.querySelectorAll(selector), setup);
    }

    function resetWithin(root) {
        Array.prototype.forEach.call(root.querySelectorAll(selector), function (select) {
            if (select._accountSearch) {
                select._accountSearch.input.value = '';
                update(select);
            }
        });
    }

    function start() {
        setupWithin(document.body);
        var pending = new Set();
        var scheduled = false;
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes') {
                    if (mutation.target.matches(selector)) pending.add(mutation.target);
                    else if (mutation.target.tagName === 'FIELDSET') {
                        Array.prototype.forEach.call(mutation.target.querySelectorAll(selector), function (select) {
                            pending.add(select);
                        });
                    }
                    return;
                }
                var parent = mutation.target.closest && mutation.target.closest(selector);
                if (parent) pending.add(parent);
                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    setupWithin(node);
                });
            });
            if (!scheduled && pending.size) {
                scheduled = true;
                requestAnimationFrame(function () {
                    pending.forEach(update);
                    pending.clear();
                    scheduled = false;
                });
            }
        });
        observer.observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['disabled']});
        document.addEventListener('reset', function (event) {
            window.setTimeout(function () { resetWithin(event.target); }, 0);
        });
        if (window.jQuery) {
            window.jQuery(document).on('show.bs.modal', '.modal', function () { resetWithin(this); });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
}());
