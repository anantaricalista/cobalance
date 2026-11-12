<script>
window.MoneyInput = (function ($) {
    var instances = new WeakMap();
    var maxAmount = '9999999999999.99';

    function validFormat(value) {
        return /^(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d{1,2})?$/.test(value);
    }

    function display(value) {
        var text = String(value == null ? '' : value);
        if (text === '') return '';
        if (!/^-?\d+(?:\.\d+)?$/.test(text)) return text;
        var parts = text.split('.');
        return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') +
            ',' + (parts[1] || '').padEnd(2, '0');
    }

    function cents(value) {
        var text = String(value == null ? '0' : value);
        if (!/^-?\d+(?:\.\d{1,2})?$/.test(text)) throw new Error('Nominal tidak valid.');
        var negative = text.charAt(0) === '-';
        var parts = (negative ? text.slice(1) : text).split('.');
        var result = BigInt(parts[0] + (parts[1] || '').padEnd(2, '0'));
        return negative ? -result : result;
    }

    function fromCents(value) {
        var text = value.toString();
        var negative = text.charAt(0) === '-';
        if (negative) text = text.slice(1);
        text = text.padStart(3, '0');
        return (negative ? '-' : '') + text.slice(0, -2) + '.' + text.slice(-2);
    }

    function validate(input) {
        var number = instances.get(input).getNumericString();
        var message = '';
        if (number && (cents(number) > cents(maxAmount) ||
            (input.getAttribute('min') !== null && cents(number) < cents(input.getAttribute('min'))) ||
            (input.getAttribute('max') !== null && cents(number) > cents(input.getAttribute('max'))))) {
            message = 'Nominal berada di luar batas yang diizinkan.';
        }
        input.setCustomValidity(message);
    }

    function reject(input, message) {
        input.setCustomValidity(message);
        input.reportValidity();
    }

    function init(input, initial) {
        var $input = $(input);
        $input.attr('type', 'text').attr('inputmode', 'decimal').attr('data-money', '1')
            .attr('placeholder', 'Contoh: 1.000,50')
            .attr('title', 'Gunakan koma untuk desimal dan titik untuk ribuan, misalnya 1.000,50.');
        input.value = '';
        var instance = new AutoNumeric(input, '', {
            digitGroupSeparator: '.',
            decimalCharacter: ',',
            decimalCharacterAlternative: null,
            decimalPlaces: 2,
            minimumValue: '0',
            maximumValue: maxAmount,
            overrideMinMaxLimits: 'ignore',
            emptyInputBehavior: 'null',
            selectOnFocus: false,
            unformatOnHover: false,
            modifyValueOnWheel: false,
            modifyValueOnUpDownArrow: false,
            createLocalList: false
        });
        instances.set(input, instance);
        input.addEventListener('keydown', function (event) {
            if (event.key === '.' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                event.stopImmediatePropagation();
                reject(input, 'Gunakan koma untuk desimal; titik ribuan ditambahkan otomatis.');
            }
        }, true);
        input.addEventListener('paste', function (event) {
            var pasted = event.clipboardData && event.clipboardData.getData('text/plain');
            var combined = input.value.slice(0, input.selectionStart) + pasted + input.value.slice(input.selectionEnd);
            if (!pasted || !validFormat(pasted) ||
                !/^\d*(?:,\d{0,2})?$/.test(combined.replace(/\./g, ''))) {
                event.preventDefault();
                event.stopImmediatePropagation();
                reject(input, 'Tempel nominal dalam format Indonesia, misalnya 1.000,50, dengan maksimal dua desimal.');
            }
        }, true);
        $input.on('input keyup blur change paste', function () {
            validate(this);
        });
        set(input, initial == null ? '' : initial);
    }

    function set(input, value) {
        var text = String(value == null ? '' : value);
        if (text && !/^\d+(?:\.\d{1,2})?$/.test(text)) {
            throw new Error('Nilai nominal awal tidak valid.');
        }
        instances.get(input).set(text === '' ? null : text);
        validate(input);
    }

    function value(input) {
        var raw = instances.get(input).getNumericString();
        return raw == null ? '' : raw;
    }

    function serialize(form) {
        var inputs = $(form).find('[data-money]').toArray();
        var values = inputs.map(function (input) { return input.value; });
        inputs.forEach(function (input) { input.value = value(input); });
        var result = $(form).serialize();
        inputs.forEach(function (input, i) { input.value = values[i]; });
        return result;
    }

    return {init: init, set: set, value: value, serialize: serialize, display: display,
        cents: cents, fromCents: fromCents};
})(jQuery);
</script>
