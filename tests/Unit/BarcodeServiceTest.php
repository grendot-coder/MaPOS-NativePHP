<?php

use App\Services\BarcodeService;

test('it calculates ean13 check digit correctly as specified in doc', function () {
    $service = new BarcodeService();

    // Contoh dari dokumen: 20 + 0 + 01 + 0000001 -> 200010000001 check digit = 4
    expect($service->calculateEan13CheckDigit('200010000001'))->toBe(4);
});

test('it generates internal code accurately', function () {
    $service = new BarcodeService();

    $code = $service->generateInternalCode('01', 1, 0);
    expect($code)->toBe('2000100000014');
    expect($service->validateEan13($code))->toBeTrue();
});

test('it validates retail ean13 barcodes', function () {
    $service = new BarcodeService();

    expect($service->validateEan13('8998866200233'))->toBeTrue();
    expect($service->validateEan13('8998866200230'))->toBeFalse();
    expect($service->validateEan13('12345'))->toBeFalse();
});

test('it auto detects barcode types correctly', function () {
    $service = new BarcodeService();

    expect($service->detectBarcodeType('8998866200233'))->toBe('EAN-13');
    expect($service->detectBarcodeType('012345678905'))->toBe('UPC-A');
    expect($service->detectBarcodeType('12345670'))->toBe('EAN-8');
    expect($service->detectBarcodeType('PROD-CUSTOM-99'))->toBe('CODE-128');
});

test('it renders svg and html barcodes correctly', function () {
    $service = new BarcodeService();

    $svg = $service->renderSvg('8998866200233');
    expect($svg)->toContain('<svg');
    expect($svg)->toContain('</svg>');

    $html = $service->renderHtml('8998866200233');
    expect($html)->toContain('<div');
    expect($html)->toContain('</div>');
});
