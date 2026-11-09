<script>
window.MoneyInput = (function ($) {
    function canonical(value) {
        return value.replace(/\./g, '').replace(',', '.');
    }

    function format(value) {
        var parts = value.split(',');
        return parts[0].replace(/\./g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.') +
            (parts.length > 1 ? ',' + parts[1] : '');
    }

    function validate(input) {
        var value = input.value;
        var number = canonical(value);
        var message = '';
        if (value && !/^\d+(?:\.\d{3})*(?:,\d{1,2})?$/.test(value)) {
            message = 'Gunakan angka dengan maksimal dua desimal, misalnya 1.000.000,50.';
        } else if (value && ((input.getAttribute('min') !== null && Number(number) < Number(input.getAttribute('min'))) ||
            (input.getAttribute('max') !== null && Number(number) > Number(input.getAttribute('max'))))) {
            message = 'Nominal berada di luar batas yang diizinkan.';
        }
        input.setCustomValidity(message);
    }

    function init(input, initial) {
        var $input = $(input);
        $input.attr('type', 'text').attr('inputmode', 'decimal').attr('data-money', '1');
        $input.on('input', function () {
            var start = this.selectionStart;
            var before = this.value.slice(0, start).replace(/\./g, '').length;
            if (/^[\d.,]*$/.test(this.value) && (this.value.match(/,/g) || []).length <= 1) {
                this.value = format(this.value);
                var pos = 0, count = 0;
                while (pos < this.value.length && count < before) {
                    if (this.value.charAt(pos) !== '.') count++;
                    pos++;
                }
                this.setSelectionRange(pos, pos);
            }
            validate(this);
        });
        set(input, initial == null ? '' : initial);
    }

    function set(input, value) {
        var text = String(value == null ? '' : value);
        input.value = format(text.replace('.', ','));
        validate(input);
    }

    function value(input) {
        return canonical(input.value);
    }

    function serialize(form) {
        var inputs = $(form).find('[data-money]').toArray();
        var values = inputs.map(function (input) { return input.value; });
        inputs.forEach(function (input) { input.value = value(input); });
        var result = $(form).serialize();
        inputs.forEach(function (input, i) { input.value = values[i]; });
        return result;
    }

    return {init: init, set: set, value: value, serialize: serialize};
})(jQuery);
</script>
