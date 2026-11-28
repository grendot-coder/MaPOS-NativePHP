<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Master Dropshipper</flux:heading>
            <flux:subheading>Kelola mitra reseller dropship beserta diskon khusus dan cetak label pengiriman atas nama pengirim.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="create" variant="primary" icon="plus">
                Tambah Dropshipper
            </flux:button>
        </div>
    </div>

    <flux:card class="p-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kode, nama toko reseller, atau telepon..." icon="magnifying-glass" size="sm" clearable />
    </flux:card>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 uppercase font-semibold text-[11px]">
                    <tr>
                        <th class="p-3">Kode</th>
                        <th class="p-3">Nama Dropshipper / Toko</th>
                        <th class="p-3">Kontak & Alamat</th>
                        <th class="p-3 text-right">Diskon Reseller</th>
                        <th class="p-3 text-center">Order</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($dropshippers as $d)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $d->kode_dropshipper }}</td>
                        <td class="p-3 font-semibold text-zinc-900 dark:text-zinc-100">{{ $d->nama }}</td>
                        <td class="p-3">
                            <div class="font-mono text-zinc-700 dark:text-zinc-300">{{ $d->telepon ?: '-' }}</div>
                            @if($d->alamat)
                            <div class="text-[11px] text-zinc-400">{{ \Illuminate\Support\Str::limit($d->alamat, 45) }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-right font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                            {{ number_format((float) $d->diskon_khusus, 1, ',', '.') }}%
                        </td>
                        <td class="p-3 text-center font-mono">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800">
                                {{ $d->sales_count }} Order
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            @if($d->status_aktif)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Aktif</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="p-3 text-right space-x-1">
                            <flux:button type="button" wire:click="edit('{{ $d->id }}')" size="xs" variant="subtle" icon="pencil-square">Edit</flux:button>
                            <flux:button type="button" wire:click="delete('{{ $d->id }}')" wire:confirm="Hapus / nonaktifkan dropshipper {{ $d->nama }}?" size="xs" variant="danger" icon="trash">Hapus</flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-zinc-400">Belum ada data mitra dropshipper.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="showModal" class="md:w-[480px] space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Dropshipper' : 'Tambah Dropshipper Baru' }}</flux:heading>
            <flux:subheading>Nama & telepon dropshipper akan tercetak sebagai pengirim pada label pengiriman dropship.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-3 text-xs">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Kode *</label>
                    <flux:input wire:model="kode_dropshipper" size="sm" class="font-mono" required />
                    @error('kode_dropshipper') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nama Pengirim / Toko Reseller *</label>
                    <flux:input wire:model="nama" size="sm" placeholder="Contoh: Toko Berkah Online" required />
                    @error('nama') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">No. Telepon / WhatsApp</label>
                    <flux:input wire:model="telepon" size="sm" placeholder="08xxxxxxxxxx" />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Diskon Reseller (%)</label>
                    <flux:input type="number" step="0.5" min="0" max="100" wire:model="diskon_khusus" size="sm" class="font-mono" />
                    @error('diskon_khusus') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Alamat Asal Pengirim (Opsional)</label>
                <textarea wire:model="alamat" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2"></textarea>
            </div>

            <label class="flex items-center gap-2 cursor-pointer pt-1">
                <input type="checkbox" wire:model="status_aktif" class="rounded border-zinc-300 text-indigo-600" />
                <span class="font-medium text-zinc-700 dark:text-zinc-300">Mitra Dropshipper Aktif</span>
            </label>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Dropshipper</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
