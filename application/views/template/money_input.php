<script>
window.MoneyInput = (function ($) {
    function canonical(value) {
        return value.replace(/\./g, '').replace(',', '.');
    }

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
        var value = input.value;
        var number = canonical(value);
        var message = '';
        if (value && !validFormat(value)) {
            message = 'Gunakan koma untuk desimal dan titik untuk ribuan, misalnya 1.000,50.';
        } else if (value && ((input.getAttribute('min') !== null && cents(number) < cents(input.getAttribute('min'))) ||
            (input.getAttribute('max') !== null && cents(number) > cents(input.getAttribute('max'))))) {
            message = 'Nominal berada di luar batas yang diizinkan.';
        }
        input.setCustomValidity(message);
    }

    function init(input, initial) {
        var $input = $(input);
        $input.attr('type', 'text').attr('inputmode', 'decimal').attr('data-money', '1')
            .attr('placeholder', 'Contoh: 1.000,50')
            .attr('title', 'Gunakan koma untuk desimal dan titik untuk ribuan, misalnya 1.000,50.');
        $input.on('input', function () {
            validate(this);
        });
        $input.on('blur', function () {
            if (this.value && validFormat(this.value)) this.value = display(canonical(this.value));
            validate(this);
        });
        set(input, initial == null ? '' : initial);
    }

    function set(input, value) {
        var text = String(value == null ? '' : value);
        input.value = display(text);
        validate(input);
    }

    function value(input) {
        if (!input.value) return '';
        return validFormat(input.value) ? canonical(input.value) : 'invalid';
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
