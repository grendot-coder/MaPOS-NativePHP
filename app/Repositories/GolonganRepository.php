<?php

namespace App\Repositories;

use App\Models\Golongan;
use Illuminate\Database\Eloquent\Collection;

class GolonganRepository
{
    /**
     * @return Collection<int, Golongan>
     */
    public function all(): Collection
    {
        return Golongan::orderBy('kode_golongan')->get();
    }

    public function findById(string $id): ?Golongan
    {
        return Golongan::find($id);
    }

    public function findByKode(string $kode): ?Golongan
    {
        return Golongan::where('kode_golongan', $kode)->first();
    }

    /**
     * @param array{nama_golongan: string, kode_golongan: string} $data
     */
    public function create(array $data): Golongan
    {
        return Golongan::create($data);
    }

    /**
     * @param array{nama_golongan?: string, kode_golongan?: string} $data
     */
    public function update(Golongan $golongan, array $data): bool
    {
        return $golongan->update($data);
    }

    public function delete(Golongan $golongan): bool
    {
        return $golongan->delete();
    }
}

