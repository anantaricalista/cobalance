function formatTanggalIndonesia(value) {
    if (value == null || value === '') return '-';
    var text = String(value);
    var match = /^(\d{4})-(\d{2})-(\d{2})(.*)$/.exec(text);
    if (!match) return text;
    var year = Number(match[1]), month = Number(match[2]), day = Number(match[3]);
    var leap = year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0);
    var days = [31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    if (year < 1 || month < 1 || month > 12 || day < 1 || day > days[month - 1]) return text;
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return match[3] + ' ' + months[month - 1] + ' ' + match[1] + match[4];
}
