<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    /**
     * Cari produk dengan filter golongan, search term, dan pagination.
     */
    public function paginate(
        ?string $search = null,
        ?string $golonganId = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 20
    ): LengthAwarePaginator {
        return Product::query()
            ->with('golongan')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode_produk', 'like', "%{$search}%");
                });
            })
            ->when($golonganId, fn ($query) => $query->where('golongan_id', $golonganId))
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    public function findById(string $id): ?Product
    {
        return Product::with('golongan')->find($id);
    }

    public function findByKodeProduk(string $kode): ?Product
    {
        return Product::with('golongan')->where('kode_produk', $kode)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function count(): int
    {
        return Product::count();
    }
}
