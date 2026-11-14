<?php
defined('BASEPATH') or exit('No direct script access allowed');

function format_jumlah_indonesia($value)
{
    if ($value === null || $value === '') {
        return '-';
    }
    $value = (string) $value;
    if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $value, $parts)) {
        throw new InvalidArgumentException('Jumlah tidak valid untuk ditampilkan.');
    }
    $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[2]);
    $fraction = isset($parts[3]) ? rtrim($parts[3], '0') : '';
    $negative = $parts[1] === '-' && (trim($parts[2], '0') !== '' || $fraction !== '');
    return ($negative ? '-' : '') . $whole . ($fraction === '' ? '' : ',' . $fraction);
}
