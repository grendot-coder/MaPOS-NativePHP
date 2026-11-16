<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Master Golongan Produk</flux:heading>
            <flux:subheading>Klasifikasi kategori produk untuk penomoran kode barcode internal dan filter SmartPos.</flux:subheading>
        </div>
        <div>
            <flux:button wire:click="create" variant="primary" icon="plus">
                Tambah Golongan
            </flux:button>
        </div>
    </div>

    <flux:card class="space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs font-semibold uppercase text-zinc-500">
                        <th class="py-3 px-4">Kode (2 Digit)</th>
                        <th class="py-3 px-4">Nama Golongan</th>
                        <th class="py-3 px-4 text-center">Jumlah Produk</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($golongans as $g)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="py-3 px-4 font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $g->kode_golongan }}
                            </td>
                            <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $g->nama_golongan }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $g->products_count }} Produk
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                <flux:button wire:click="edit('{{ $g->id }}')" size="sm" variant="subtle" icon="pencil-square">
                                    Edit
                                </flux:button>
                                <flux:button wire:click="delete('{{ $g->id }}')" wire:confirm="Yakin ingin menghapus golongan ini?" size="sm" variant="danger" icon="trash">
                                    Hapus
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-zinc-500">
                                Belum ada data golongan. Silakan klik tombol Tambah Golongan di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    <!-- Modal Form Tambah / Edit Golongan -->
    <flux:modal wire:model="showModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Golongan' : 'Tambah Golongan Baru' }}</flux:heading>
            <flux:subheading>Kode golongan 2 digit digunakan pada segmen digit barcode internal.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="kode_golongan" label="Kode Golongan (2 Digit)" placeholder="01" maxlength="2" required />
            <flux:input wire:model="nama_golongan" label="Nama Golongan" placeholder="Contoh: Makanan & Minuman" required />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $isEditing ? 'Simpan Perubahan' : 'Tambahkan' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

