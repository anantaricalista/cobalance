<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Aset_tetap_kalkulasi
{
    private $awal;
    private $bulan;
    private $nilai;
    private $harga;
    private $akumulasiAwal;

    public function __construct($options = array())
    {
        if (PHP_INT_SIZE < 8 || !is_array($options)
            || !isset($options['nilai_perolehan'], $options['nilai_residu'], $options['umur_manfaat_bulan'],
                $options['tanggal_mulai_penyusutan'], $options['akumulasi_awal'])) {
            throw new InvalidArgumentException('Data perhitungan penyusutan tidak lengkap.');
        }

        $cost = $this->sen($options['nilai_perolehan']);
        $residual = $this->sen($options['nilai_residu']);
        $opening = $this->sen($options['akumulasi_awal']);
        $life = $options['umur_manfaat_bulan'];
        if ((!is_string($life) && !is_int($life)) || !ctype_digit((string) $life)
            || (int) $life < 1 || (int) $life > 120000) {
            throw new InvalidArgumentException('Umur manfaat harus antara 1 dan 120000 bulan.');
        }
        $life = (int) $life;
        if ($cost === 0 || $residual > $cost || $opening > $cost - $residual) {
            throw new DomainException('Nilai aset, residu, atau akumulasi awal tidak valid.');
        }

        $start = $this->bulan($options['tanggal_mulai_penyusutan']);
        $cutoff = isset($options['tanggal_saldo_awal']) && $options['tanggal_saldo_awal'] !== ''
            ? $this->bulan($options['tanggal_saldo_awal']) : null;
        if ($cutoff === null && $opening !== 0) {
            throw new DomainException('Tanggal saldo awal wajib diisi untuk akumulasi awal.');
        }
        $elapsed = $cutoff !== null && $cutoff >= $start ? min($life, $cutoff - $start + 1) : 0;
        if ($elapsed === 0 && $opening !== 0) {
            throw new DomainException('Akumulasi awal tidak boleh ada sebelum penyusutan dimulai.');
        }
        $this->awal = $start + $elapsed;
        $this->bulan = $life - $elapsed;
        $this->nilai = $cost - $residual - $opening;
        $this->harga = $cost;
        $this->akumulasiAwal = $opening;
        if ($this->bulan === 0 && $this->nilai !== 0) {
            throw new DomainException('Umur manfaat sudah habis tetapi nilai yang dapat disusutkan masih tersisa.');
        }
    }

    public function nilai_bulan($tanggal)
    {
        $index = $this->bulan($tanggal) - $this->awal;
        if ($index < 0 || $index >= $this->bulan) {
            return '0.00';
        }
        $quotient = intdiv($this->nilai, $this->bulan);
        $remainder = $this->nilai % $this->bulan;
        $amount = $quotient + intdiv($remainder * ($index + 1), $this->bulan)
            - intdiv($remainder * $index, $this->bulan);
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    public function pelepasan($akumulasiTransaksi, $hasil)
    {
        $depreciation = $this->sen($akumulasiTransaksi);
        $proceeds = $this->sen($hasil);
        if ($depreciation > $this->nilai) {
            throw new DomainException('Akumulasi penyusutan melebihi nilai yang dapat disusutkan.');
        }
        $total = $this->akumulasiAwal + $depreciation;
        $bookValue = $this->harga - $total;
        $gain = max(0, $proceeds - $bookValue);
        $loss = max(0, $bookValue - $proceeds);
        return array(
            'akumulasi' => $this->uang($total),
            'nilai_buku' => $this->uang($bookValue),
            'keuntungan' => $this->uang($gain),
            'kerugian' => $this->uang($loss)
        );
    }

    private function uang($amount)
    {
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    public function kumulatif_sampai($tanggal)
    {
        $periods = max(0, min($this->bulan, $this->bulan($tanggal) - $this->awal + 1));
        if ($periods === 0) {
            return '0.00';
        }
        $amount = intdiv($this->nilai, $this->bulan) * $periods
            + intdiv(($this->nilai % $this->bulan) * $periods, $this->bulan);
        return intdiv($amount, 100) . '.' . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    private function bulan($date)
    {
        if (!is_string($date) || !preg_match('/^[1-9]\d{3}-\d{2}-\d{2}$/D', $date)
            || !($value = DateTime::createFromFormat('!Y-m-d', $date)) || $value->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Tanggal penyusutan tidak valid.');
        }
        return (int) $value->format('Y') * 12 + (int) $value->format('n') - 1;
    }

    private function sen($amount)
    {
        if ((!is_string($amount) && !is_int($amount))
            || !preg_match('/^\d{1,13}(?:\.\d{1,2})?$/D', (string) $amount)) {
            throw new InvalidArgumentException('Nilai aset tidak valid.');
        }
        $parts = explode('.', (string) $amount);
        return (int) $parts[0] * 100 + (int) str_pad(isset($parts[1]) ? $parts[1] : '', 2, '0');
    }
}
