function formatTanggalIndonesia(value) {
    if (value == null || value === '') return '-';
    var text = String(value);
    var match = /^(\d{4})-(\d{2})-(\d{2})(.*)$/.exec(text);
    if (!match) return text;
    var year = Number(match[1]), month = Number(match[2]), day = Number(match[3]);
    var date = new Date(year, month - 1, day);
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) return text;
    return match[3] + '/' + match[2] + '/' + match[1] + match[4];
}
