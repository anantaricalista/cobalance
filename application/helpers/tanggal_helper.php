<?php
defined('BASEPATH') or exit('No direct script access allowed');

function format_tanggal_indonesia($value)
{
    if ($value === null || $value === '') {
        return '-';
    }
    $value = (string) $value;
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})(.*)$/D', $value, $parts)
        || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
        return $value;
    }
    return $parts[3] . '/' . $parts[2] . '/' . $parts[1] . $parts[4];
}
