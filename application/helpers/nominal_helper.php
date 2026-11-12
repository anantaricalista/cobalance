<?php
defined('BASEPATH') or exit('No direct script access allowed');

function format_nominal($value, $scale = 2)
{
    if ($value === null || $value === '') {
        return '-';
    }
    $value = (string) $value;
    if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $value, $parts)) {
        throw new InvalidArgumentException('Nominal tidak valid untuk ditampilkan.');
    }
    $fraction = isset($parts[3]) ? $parts[3] : '';
    if (strlen($fraction) > $scale && trim(substr($fraction, $scale), '0') !== '') {
        throw new InvalidArgumentException('Presisi nominal melebihi format tampilan.');
    }
    $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[2]);
    return $parts[1] . $whole . ($scale ? ',' . str_pad(substr($fraction, 0, $scale), $scale, '0') : '');
}

function jumlah_nominal($left, $right)
{
    $values = array();
    foreach (array($left, $right) as $value) {
        if ($value === null) {
            $value = '0';
        }
        if (!preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/D', (string) $value, $parts)) {
            throw new InvalidArgumentException('Nominal tidak valid untuk dijumlahkan.');
        }
        $digits = ltrim($parts[2] . str_pad(isset($parts[3]) ? $parts[3] : '', 2, '0'), '0');
        $values[] = array($parts[1] === '-' && $digits !== '', $digits === '' ? '0' : $digits);
    }
    list($negativeA, $a) = $values[0];
    list($negativeB, $b) = $values[1];
    $addition = $negativeA === $negativeB;
    $compare = strlen($a) === strlen($b) ? strcmp($a, $b) : (strlen($a) > strlen($b) ? 1 : -1);
    if ($compare < 0) {
        list($a, $b) = array($b, $a);
        $negativeA = $negativeB;
    }
    $b = str_pad($b, strlen($a), '0', STR_PAD_LEFT);
    $carry = 0;
    $result = '';
    for ($i = strlen($a) - 1; $i >= 0; $i--) {
        $digit = (int) $a[$i] + ($addition ? (int) $b[$i] : -(int) $b[$i]) + $carry;
        if ($addition) {
            $carry = intdiv($digit, 10);
            $digit %= 10;
        } else {
            $carry = $digit < 0 ? -1 : 0;
            if ($digit < 0) $digit += 10;
        }
        $result = $digit . $result;
    }
    if ($carry > 0) $result = $carry . $result;
    $result = ltrim($result, '0');
    $result = str_pad($result === '' ? '0' : $result, 3, '0', STR_PAD_LEFT);
    return ($negativeA && trim($result, '0') !== '' ? '-' : '')
        . substr($result, 0, -2) . '.' . substr($result, -2);
}

function kurangi_nominal($left, $right)
{
    $right = $right === null ? '0' : (string) $right;
    return jumlah_nominal($left, substr($right, 0, 1) === '-' ? substr($right, 1) : '-' . $right);
}
