<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorHTML;
use Picqer\Barcode\BarcodeGeneratorSVG;

class BarcodeService
{
    public function __construct()
    {
        $this->ensureAutoload();
    }

    protected function ensureAutoload(): void
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Picqer\\Barcode\\';
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $baseDir = dirname(__DIR__, 2);
                $file = $baseDir.'/vendor/picqer/php-barcode-generator/src/'.str_replace('\\', '/', $relative).'.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });
    }

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

        $digits12 = '20'.$device1Digit.$golongan2Digit.$sequence7Digit;
        $checkDigit = $this->calculateEan13CheckDigit($digits12);

        return $digits12.$checkDigit;
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

    /**
     * Render Barcode dalam format SVG murni (ringan, vektor, tajam dicetak di printer thermal).
     */
    public function renderSvg(string $barcode, int $widthFactor = 2, int $height = 50): string
    {
        $barcode = trim($barcode);
        $generator = new BarcodeGeneratorSVG;
        $type = $this->detectBarcodeType($barcode);

        try {
            if ($type === 'EAN-13') {
                return $generator->getBarcode($barcode, $generator::TYPE_EAN_13, $widthFactor, $height);
            }
            if ($type === 'UPC-A') {
                return $generator->getBarcode($barcode, $generator::TYPE_UPC_A, $widthFactor, $height);
            }
            if ($type === 'EAN-8') {
                return $generator->getBarcode($barcode, $generator::TYPE_EAN_8, $widthFactor, $height);
            }

            return $generator->getBarcode($barcode, $generator::TYPE_CODE_128, $widthFactor, $height);
        } catch (\Throwable) {
            // Fallback aman ke CODE-128 jika tipe spesifik menemui kendala karakter
            return $generator->getBarcode($barcode, $generator::TYPE_CODE_128, $widthFactor, $height);
        }
    }

    /**
     * Render Barcode dalam format HTML (div bergaris, bekerja tanpa ekstensi image apa pun).
     */
    public function renderHtml(string $barcode, int $widthFactor = 2, int $height = 50): string
    {
        $barcode = trim($barcode);
        $generator = new BarcodeGeneratorHTML;
        $type = $this->detectBarcodeType($barcode);

        try {
            if ($type === 'EAN-13') {
                return $generator->getBarcode($barcode, $generator::TYPE_EAN_13, $widthFactor, $height);
            }

            return $generator->getBarcode($barcode, $generator::TYPE_CODE_128, $widthFactor, $height);
        } catch (\Throwable) {
            return $generator->getBarcode($barcode, $generator::TYPE_CODE_128, $widthFactor, $height);
        }
    }
}
