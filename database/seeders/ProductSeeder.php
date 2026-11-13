<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Golongan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\BarcodeService;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barcodeService = app(BarcodeService::class);

        $gAlatTulis = Golongan::where('kode_golongan', '01')->firstOrFail();
        $gFood = Golongan::where('kode_golongan', '02')->firstOrFail();
        $gNonFood = Golongan::where('kode_golongan', '03')->firstOrFail();
        $gKosmetik = Golongan::where('kode_golongan', '04')->firstOrFail();
        $gAccessories = Golongan::where('kode_golongan', '05')->firstOrFail();
        $gLain = Golongan::where('kode_golongan', '06')->firstOrFail();

        // Daftar 15 produk contoh dengan atribut lengkap SmartPos
        $products = [
            // FOOD - Barcode Ritel EAN-13
            [
                'nama' => 'Indomie Mi Goreng Spesial 85g',
                'kode_produk' => '8998866200233',
                'golongan_id' => $gFood->id,
                'satuan' => 'bks',
                'harga_beli' => 2800,
                'harga_retail' => 3500,
                'harga_grosir_1' => 3300,
                'harga_grosir_2' => 3200,
                'harga_grosir_3' => 3100,
                'diskon' => 0,
                'stok_etalase' => 50,
                'stok_gudang' => 200,
                'stok' => 250,
                'min_stok' => 20,
            ],
            [
                'nama' => 'Air Mineral Aqua 600ml',
                'kode_produk' => '8996001600269',
                'golongan_id' => $gFood->id,
                'satuan' => 'botol',
                'harga_beli' => 3000,
                'harga_retail' => 4000,
                'harga_grosir_1' => 3800,
                'harga_grosir_2' => 3600,
                'harga_grosir_3' => 3500,
                'diskon' => 0,
                'stok_etalase' => 40,
                'stok_gudang' => 120,
                'stok' => 160,
                'min_stok' => 24,
            ],
            [
                'nama' => 'Teh Botol Sosro Kotak 250ml',
                'kode_produk' => '8999999195045',
                'golongan_id' => $gFood->id,
                'satuan' => 'kotak',
                'harga_beli' => 3200,
                'harga_retail' => 4500,
                'harga_grosir_1' => 4200,
                'harga_grosir_2' => 4000,
                'harga_grosir_3' => 3900,
                'diskon' => 0,
                'stok_etalase' => 30,
                'stok_gudang' => 90,
                'stok' => 120,
                'min_stok' => 15,
            ],
            [
                'nama' => 'Biskuit Roma Kelapa 300g',
                'kode_produk' => '899275312345'.$barcodeService->calculateEan13CheckDigit('899275312345'),
                'golongan_id' => $gFood->id,
                'satuan' => 'bks',
                'harga_beli' => 9500,
                'harga_retail' => 12000,
                'harga_grosir_1' => 11500,
                'harga_grosir_2' => 11000,
                'harga_grosir_3' => 10500,
                'diskon' => 5.0,
                'stok_etalase' => 20,
                'stok_gudang' => 60,
                'stok' => 80,
                'min_stok' => 10,
            ],

            // NON FOOD - Barcode Ritel EAN-13
            [
                'nama' => 'Pasta Gigi Pepsodent White 120g',
                'kode_produk' => '8999999036041',
                'golongan_id' => $gNonFood->id,
                'satuan' => 'tube',
                'harga_beli' => 11000,
                'harga_retail' => 14500,
                'harga_grosir_1' => 13800,
                'harga_grosir_2' => 13500,
                'harga_grosir_3' => 13000,
                'diskon' => 0,
                'stok_etalase' => 25,
                'stok_gudang' => 50,
                'stok' => 75,
                'min_stok' => 10,
            ],
            [
                'nama' => 'Sabun Mandi Lifebuoy Total 10 110g',
                'kode_produk' => '899999955512'.$barcodeService->calculateEan13CheckDigit('899999955512'),
                'golongan_id' => $gNonFood->id,
                'satuan' => 'pcs',
                'harga_beli' => 4500,
                'harga_retail' => 6000,
                'harga_grosir_1' => 5700,
                'harga_grosir_2' => 5500,
                'harga_grosir_3' => 5300,
                'diskon' => 0,
                'stok_etalase' => 35,
                'stok_gudang' => 70,
                'stok' => 105,
                'min_stok' => 15,
            ],
            [
                'nama' => 'Deterjen Rinso Molto Matic 800g',
                'kode_produk' => '899999977712'.$barcodeService->calculateEan13CheckDigit('899999977712'),
                'golongan_id' => $gNonFood->id,
                'satuan' => 'pack',
                'harga_beli' => 22000,
                'harga_retail' => 26500,
                'harga_grosir_1' => 25500,
                'harga_grosir_2' => 25000,
                'harga_grosir_3' => 24000,
                'diskon' => 0,
                'stok_etalase' => 15,
                'stok_gudang' => 30,
                'stok' => 45,
                'min_stok' => 5,
            ],

            // ALAT TULIS / STATION - Campuran Ritel & Kode Internal
            [
                'nama' => 'Pulpen Standard AE7 Hitam 0.5mm',
                'kode_produk' => '899317611001'.$barcodeService->calculateEan13CheckDigit('899317611001'),
                'golongan_id' => $gAlatTulis->id,
                'satuan' => 'pcs',
                'harga_beli' => 2200,
                'harga_retail' => 3500,
                'harga_grosir_1' => 3000,
                'harga_grosir_2' => 2800,
                'harga_grosir_3' => 2600,
                'diskon' => 0,
                'stok_etalase' => 60,
                'stok_gudang' => 240,
                'stok' => 300,
                'min_stok' => 30,
            ],
            [
                'nama' => 'Buku Tulis Sinar Dunia 38 Lembar',
                'kode_produk' => '899274198765'.$barcodeService->calculateEan13CheckDigit('899274198765'),
                'golongan_id' => $gAlatTulis->id,
                'satuan' => 'buku',
                'harga_beli' => 3500,
                'harga_retail' => 5000,
                'harga_grosir_1' => 4500,
                'harga_grosir_2' => 4200,
                'harga_grosir_3' => 4000,
                'diskon' => 0,
                'stok_etalase' => 40,
                'stok_gudang' => 160,
                'stok' => 200,
                'min_stok' => 20,
            ],
            // PRODUK KODE INTERNAL (tanpa barcode pabrik, format 20 + 0 + golongan + urut + check)
            [
                'nama' => 'Penghapus Pensil Joyko Putih EB-30',
                'kode_produk' => $barcodeService->generateInternalCode('01', 1, 0), // 2000100000014
                'golongan_id' => $gAlatTulis->id,
                'satuan' => 'pcs',
                'harga_beli' => 1500,
                'harga_retail' => 2500,
                'harga_grosir_1' => 2200,
                'harga_grosir_2' => 2000,
                'harga_grosir_3' => 1800,
                'diskon' => 0,
                'stok_etalase' => 30,
                'stok_gudang' => 100,
                'stok' => 130,
                'min_stok' => 15,
            ],

            // KOSMETIK - Campuran Ritel & Internal
            [
                'nama' => 'Bedak Marcks Classic White 40g',
                'kode_produk' => '899888812345'.$barcodeService->calculateEan13CheckDigit('899888812345'),
                'golongan_id' => $gKosmetik->id,
                'satuan' => 'pcs',
                'harga_beli' => 13000,
                'harga_retail' => 17000,
                'harga_grosir_1' => 16000,
                'harga_grosir_2' => 15500,
                'harga_grosir_3' => 15000,
                'diskon' => 0,
                'stok_etalase' => 15,
                'stok_gudang' => 30,
                'stok' => 45,
                'min_stok' => 5,
            ],
            [
                'nama' => 'Lip Balm Wardah Everyday Fruity',
                'kode_produk' => $barcodeService->generateInternalCode('04', 1, 0),
                'golongan_id' => $gKosmetik->id,
                'satuan' => 'pcs',
                'harga_beli' => 18000,
                'harga_retail' => 23000,
                'harga_grosir_1' => 21500,
                'harga_grosir_2' => 21000,
                'harga_grosir_3' => 20000,
                'diskon' => 0,
                'stok_etalase' => 10,
                'stok_gudang' => 20,
                'stok' => 30,
                'min_stok' => 5,
            ],

            // ACCESSORIES & LAIN-LAIN - Kode Internal
            [
                'nama' => 'Gantungan Kunci Akrilik Karakter',
                'kode_produk' => $barcodeService->generateInternalCode('05', 1, 0),
                'golongan_id' => $gAccessories->id,
                'satuan' => 'pcs',
                'harga_beli' => 4000,
                'harga_retail' => 8000,
                'harga_grosir_1' => 7000,
                'harga_grosir_2' => 6500,
                'harga_grosir_3' => 6000,
                'diskon' => 0,
                'stok_etalase' => 20,
                'stok_gudang' => 40,
                'stok' => 60,
                'min_stok' => 10,
            ],
            [
                'nama' => 'Jepit Rambut Mutiara Vintage',
                'kode_produk' => $barcodeService->generateInternalCode('05', 2, 0),
                'golongan_id' => $gAccessories->id,
                'satuan' => 'pcs',
                'harga_beli' => 2500,
                'harga_retail' => 5000,
                'harga_grosir_1' => 4500,
                'harga_grosir_2' => 4000,
                'harga_grosir_3' => 3500,
                'diskon' => 0,
                'stok_etalase' => 25,
                'stok_gudang' => 50,
                'stok' => 75,
                'min_stok' => 10,
            ],
            [
                'nama' => 'Kantong Plastik Kresek Tebal Putih',
                'kode_produk' => $barcodeService->generateInternalCode('06', 1, 0),
                'golongan_id' => $gLain->id,
                'satuan' => 'pack',
                'harga_beli' => 7000,
                'harga_retail' => 10000,
                'harga_grosir_1' => 9500,
                'harga_grosir_2' => 9000,
                'harga_grosir_3' => 8500,
                'diskon' => 0,
                'stok_etalase' => 30,
                'stok_gudang' => 70,
                'stok' => 100,
                'min_stok' => 15,
            ],
        ];

        foreach ($products as $item) {
            $product = Product::updateOrCreate(
                ['kode_produk' => $item['kode_produk']],
                $item
            );

            // Buat record saldo awal di stock_movements jika belum ada
            if ($product->wasRecentlyCreated) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'jenis' => StockMovementType::Penyesuaian,
                    'jumlah' => $product->stok,
                    'saldo_sesudah' => $product->stok,
                    'keterangan' => 'Saldo awal stok sistem MaPOS',
                ]);
            }
        }
    }
}
