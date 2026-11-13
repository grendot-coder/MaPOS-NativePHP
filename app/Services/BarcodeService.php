<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Menghitung check digit EAN-13 untuk 12 digit pertama.
     */
    public function calculateEan13CheckDigit(string $digits12): int
    {
        if (strlen($digits12) !== 12 || ! ctype_digit($digits12)) {
            throw new \InvalidArgumentException('Parameter harus berupa 12 digit angka numerik.');
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $digits12[$i];
            // Indeks ganjil (posisi ke-1, 3, 5, ...) bobot 1; indeks genap (posisi ke-2, 4, 6, ...) bobot 3
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Validasi kode barcode EAN-13 (13 digit dan check digit sesuai).
     */
    public function validateEan13(string $barcode): bool
    {
        if (strlen($barcode) !== 13 || ! ctype_digit($barcode)) {
            return false;
        }

        $digits12 = substr($barcode, 0, 12);
        $expectedCheckDigit = $this->calculateEan13CheckDigit($digits12);
        $actualCheckDigit = (int) $barcode[12];

        return $expectedCheckDigit === $actualCheckDigit;
    }

    /**
     * Buat kode internal EAN-13 untuk produk tanpa barcode pabrik.
     * Format: 20 + [device:1 digit] + [kodeGolongan:2 digit] + [urut:7 digit] + [checkDigit:1 digit]
     */
    public function generateInternalCode(string $kodeGolongan, int $sequenceNumber, int $deviceDigit = 0): string
    {
        $golongan2Digit = str_pad(substr($kodeGolongan, -2), 2, '0', STR_PAD_LEFT);
        $device1Digit = substr((string) $deviceDigit, 0, 1);
        $sequence7Digit = str_pad((string) $sequenceNumber, 7, '0', STR_PAD_LEFT);

        $digits12 = '20' . $device1Digit . $golongan2Digit . $sequence7Digit;
        $checkDigit = $this->calculateEan13CheckDigit($digits12);

        return $digits12 . $checkDigit;
    }

    /**
     * Deteksi tipe barcode secara otomatis.
     */
    public function detectBarcodeType(string $barcode): string
    {
        $barcode = trim($barcode);

        if (strlen($barcode) === 13 && ctype_digit($barcode) && $this->validateEan13($barcode)) {
            return 'EAN-13';
        }

        if (strlen($barcode) === 12 && ctype_digit($barcode)) {
            return 'UPC-A';
        }

        if (strlen($barcode) === 8 && ctype_digit($barcode)) {
            return 'EAN-8';
        }

        return 'CODE-128';
    }
}

