<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_persediaan_kalkulasi
{
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->ci->load->database();
    }

    public function hitung($asal, $sudah, $jumlah)
    {
        if (PHP_INT_SIZE < 8) {
            throw new RuntimeException('Perhitungan retur memerlukan PHP 64-bit.');
        }
        $keys = array('nilai_sebelum_pajak', 'nilai_pajak', 'diskon', 'harga_pokok');
        if (!is_array($asal) || !is_array($sudah) || !isset($asal['jumlah'], $sudah['jumlah'])) {
            throw new InvalidArgumentException('Data perhitungan retur tidak lengkap.');
        }
        $original = $this->decimal_units($asal['jumlah'], 14, 4);
        $returned = $this->decimal_units($sudah['jumlah'], 14, 4);
        $requested = $this->decimal_units($jumlah, 14, 4);
        if ($original <= 0 || $returned > $original || $requested <= 0 || $requested > $original - $returned) {
            throw new DomainException('Jumlah retur harus positif dan tidak boleh melebihi sisa jumlah transaksi.');
        }
        $remainingQuantity = $original - $returned;
        $output = array('jumlah' => $this->format($requested, 4));
        foreach ($keys as $key) {
            if (!isset($asal[$key], $sudah[$key])) {
                throw new InvalidArgumentException('Data perhitungan retur tidak lengkap.');
            }
            $originalValue = $this->decimal_units($asal[$key], 13, 2);
            $returnedValue = $this->decimal_units($sudah[$key], 13, 2);
            if ($returnedValue > $originalValue) {
                throw new DomainException('Nilai retur terdahulu melebihi nilai transaksi asal.');
            }
            $remainingValue = $originalValue - $returnedValue;
            if ($requested === $remainingQuantity || $remainingValue === 0) {
                $amount = $remainingValue;
            } else {
                $query = $this->ci->db->query('SELECT CAST(ROUND(CAST(? AS DECIMAL(15,2)) *
                    CAST(? AS DECIMAL(18,4)) / CAST(? AS DECIMAL(18,4)), 2) AS CHAR) AS nilai',
                    array($this->format($remainingValue, 2), $this->format($requested, 4),
                        $this->format($remainingQuantity, 4)));
                if (!$query || !$query->row()) {
                    throw new RuntimeException('Gagal menghitung nilai retur.');
                }
                $amount = $this->decimal_units($query->row()->nilai, 13, 2);
                if ($amount > $remainingValue) {
                    throw new DomainException('Nilai retur melebihi sisa transaksi asal.');
                }
            }
            $output[$key] = $this->format($amount, 2);
        }
        $net = $this->decimal_units($output['nilai_sebelum_pajak'], 13, 2);
        $tax = $this->decimal_units($output['nilai_pajak'], 13, 2);
        if ($net > 999999999999999 - $tax) {
            throw new DomainException('Total retur melebihi batas database.');
        }
        $output['subtotal'] = $this->format($net + $tax, 2);
        return $output;
    }

    private function decimal_units($value, $digits, $scale)
    {
        if ((!is_string($value) && !is_int($value))
            || !preg_match('/^\d{1,' . $digits . '}(?:\.\d{1,' . $scale . '})?$/D', (string) $value)) {
            throw new InvalidArgumentException('Jumlah atau nilai retur tidak valid.');
        }
        $parts = explode('.', (string) $value);
        $factor = $scale === 4 ? 10000 : 100;
        return (int) $parts[0] * $factor
            + (int) str_pad(isset($parts[1]) ? $parts[1] : '', $scale, '0');
    }

    private function format($units, $scale)
    {
        $factor = $scale === 4 ? 10000 : 100;
        return intdiv($units, $factor) . '.' . str_pad((string) ($units % $factor), $scale, '0', STR_PAD_LEFT);
    }
}
