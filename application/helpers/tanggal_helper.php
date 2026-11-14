<?php
defined('BASEPATH') or exit('No direct script access allowed');

function format_tanggal_indonesia($value, $lengkap = false)
{
    if ($value === null || $value === '') {
        return '-';
    }
    $value = (string) $value;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(.*)$/D', $value, $parts)
        || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
        return $value;
    }
    $bulan = $lengkap
        ? array('Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember')
        : array('Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
    return $parts[3] . ' ' . $bulan[(int) $parts[2] - 1] . ' ' . $parts[1] . $parts[4];
}
