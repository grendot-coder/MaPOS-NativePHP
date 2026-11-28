<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dropshipper;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\StoreProfile;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class MasterOperasionalSeeder extends Seeder
{
    /**
     * Seed default store profile, job positions, employees, members, suppliers, and dropshippers.
     */
    public function run(): void
    {
        StoreProfile::getProfile();

        $jabatans = [
            [
                'kode_jabatan' => 'JBT-0001',
                'nama_jabatan' => 'Kepala Toko',
                'gaji_pokok_default' => 3500000,
                'tunjangan_default' => 500000,
                'deskripsi' => 'Penanggung jawab operasional harian toko & laporan.',
            ],
            [
                'kode_jabatan' => 'JBT-0002',
                'nama_jabatan' => 'Kasir',
                'gaji_pokok_default' => 2200000,
                'tunjangan_default' => 250000,
                'deskripsi' => 'Melayani transaksi POS dan pencatatan shift kas.',
            ],
            [
                'kode_jabatan' => 'JBT-0003',
                'nama_jabatan' => 'Staff Gudang & Pramuniaga',
                'gaji_pokok_default' => 2000000,
                'tunjangan_default' => 150000,
                'deskripsi' => 'Penerimaan barang supplier, stok opname, dan display rak.',
            ],
        ];

        foreach ($jabatans as $data) {
            Jabatan::firstOrCreate(['kode_jabatan' => $data['kode_jabatan']], $data);
        }

        $jabatanKepala = Jabatan::where('kode_jabatan', 'JBT-0001')->first();
        $jabatanKasir = Jabatan::where('kode_jabatan', 'JBT-0002')->first();
        $jabatanGudang = Jabatan::where('kode_jabatan', 'JBT-0003')->first();

        $choerulUser = User::where('email', 'choerul@mapos.test')->first();
        $raihaanUser = User::where('email', 'raihaan@mapos.test')->first();

        if ($jabatanKasir) {
            Employee::updateOrCreate(
                ['kode_karyawan' => 'EMP-0001'],
                [
                    'jabatan_id' => $jabatanKasir->id,
                    'user_id' => $choerulUser?->id,
                    'nama' => 'Choerul Nuril Huda',
                    'telepon' => '0811-0000-0001',
                    'alamat' => 'Jl. Melati No. 4',
                    'tanggal_masuk' => '2026-01-15',
                    'gaji_pokok' => $jabatanKasir->gaji_pokok_default,
                    'tunjangan' => $jabatanKasir->tunjangan_default,
                    'status_aktif' => true,
                ]
            );

            Employee::updateOrCreate(
                ['kode_karyawan' => 'EMP-0002'],
                [
                    'jabatan_id' => $jabatanKasir->id,
                    'user_id' => $raihaanUser?->id,
                    'nama' => 'Raihaan Adian',
                    'telepon' => '0811-0000-0002',
                    'alamat' => 'Jl. Kenanga No. 9',
                    'tanggal_masuk' => '2026-02-01',
                    'gaji_pokok' => $jabatanKasir->gaji_pokok_default,
                    'tunjangan' => $jabatanKasir->tunjangan_default,
                    'status_aktif' => true,
                ]
            );
        }

        if ($jabatanKepala) {
            Employee::updateOrCreate(
                ['kode_karyawan' => 'EMP-0003'],
                [
                    'jabatan_id' => $jabatanKepala->id,
                    'user_id' => null,
                    'nama' => 'Budi Santoso',
                    'telepon' => '0811-0000-0003',
                    'alamat' => 'Jl. Diponegoro No. 15',
                    'tanggal_masuk' => '2026-01-01',
                    'gaji_pokok' => $jabatanKepala->gaji_pokok_default,
                    'tunjangan' => $jabatanKepala->tunjangan_default,
                    'status_aktif' => true,
                ]
            );
        }

        if ($jabatanGudang) {
            Employee::updateOrCreate(
                ['kode_karyawan' => 'EMP-0004'],
                [
                    'jabatan_id' => $jabatanGudang->id,
                    'user_id' => null,
                    'nama' => 'Andi Wijaya',
                    'telepon' => '0811-0000-0004',
                    'alamat' => 'Jl. Cempaka No. 21',
                    'tanggal_masuk' => '2026-02-10',
                    'gaji_pokok' => $jabatanGudang->gaji_pokok_default,
                    'tunjangan' => $jabatanGudang->tunjangan_default,
                    'status_aktif' => true,
                ]
            );
        }

        Customer::firstOrCreate(
            ['kode_member' => 'MBR-0101'],
            [
                'nama' => 'Pelanggan Setia Ritel',
                'telepon' => '0812-1000-1001',
                'alamat' => 'Jl. Merdeka No. 12',
                'poin' => 25,
                'limit_kredit' => 500000,
                'piutang_berjalan' => 0,
            ]
        );

        Customer::firstOrCreate(
            ['kode_member' => 'MBR-0102'],
            [
                'nama' => 'Warung Sembako Harapan',
                'telepon' => '0812-1000-1002',
                'alamat' => 'Jl. Pasar Baru No. 8',
                'poin' => 100,
                'limit_kredit' => 2000000,
                'piutang_berjalan' => 0,
            ]
        );

        Supplier::firstOrCreate(
            ['nama' => 'PT. Sumber Makmur Distribusi'],
            [
                'telepon' => '021-5550199',
                'nama_kontak' => 'Pak Hendra',
                'alamat' => 'Kawasan Pergudangan Sentra Timur',
                'catatan' => 'Supplier utama kebutuhan makanan & minuman',
            ]
        );

        Supplier::firstOrCreate(
            ['nama' => 'Agen Grosir Nusantara'],
            [
                'telepon' => '0813-4444-8888',
                'nama_kontak' => 'Ibu Sari',
                'alamat' => 'Jl. Raya Gudang Peluru No. 5',
                'catatan' => 'Supplier ATK & kebutuhan rumah tangga',
            ]
        );

        Dropshipper::firstOrCreate(
            ['kode_dropshipper' => 'DRP-0101'],
            [
                'nama' => 'Berkah Mart Reseller',
                'telepon' => '0857-7777-1234',
                'alamat' => 'Jl. Ahmad Yani No. 20',
                'diskon_khusus' => 5.0,
                'status_aktif' => true,
            ]
        );
    }
}
