<?php

namespace Database\Seeders;

use App\Models\Golongan;
use Illuminate\Database\Seeder;

class GolonganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $golongans = [
            ['kode_golongan' => '01', 'nama_golongan' => 'Alat Tulis/Station'],
            ['kode_golongan' => '02', 'nama_golongan' => 'Food'],
            ['kode_golongan' => '03', 'nama_golongan' => 'Non Food'],
            ['kode_golongan' => '04', 'nama_golongan' => 'Kosmetik'],
            ['kode_golongan' => '05', 'nama_golongan' => 'Accessories'],
            ['kode_golongan' => '06', 'nama_golongan' => 'Lain-lain'],
        ];

        foreach ($golongans as $data) {
            Golongan::updateOrCreate(
                ['kode_golongan' => $data['kode_golongan']],
                $data
            );
        }
    }
}

