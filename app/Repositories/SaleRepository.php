<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SaleRepository
{
    public function paginate(
        ?int $kasirId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Sale::query()
            ->with(['kasir', 'items.product'])
            ->when($kasirId, fn ($q) => $q->where('kasir_id', $kasirId))
            ->when($startDate, fn ($q) => $q->whereDate('waktu', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('waktu', '<=', $endDate))
            ->latest('waktu')
            ->paginate($perPage);
    }

    public function findById(string $id): ?Sale
    {
        return Sale::with(['kasir', 'items.product'])->find($id);
    }

    public function findByNoTransaksi(string $no): ?Sale
    {
        return Sale::with(['kasir', 'items.product'])->where('no_transaksi', $no)->first();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Sale
    {
        return Sale::create($data);
    }
}
