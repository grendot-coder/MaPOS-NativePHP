<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Kategori Produk</flux:heading>
            <flux:subheading>Kelola kategori barang dan kode prefix barcode untuk mempermudah inventaris toko.</flux:subheading>
        </div>
        <div>
            <flux:button wire:click="create" variant="primary" icon="plus">
                Tambah Kategori
            </flux:button>
        </div>
    </div>

    <!-- Summary Stats Row (Rectangular cards, no pills) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Total Kategori</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $golongans->count() }}</p>
            </div>
            <div class="p-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                <flux:icon name="tag" class="size-5" />
            </div>
        </div>

        <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Total Produk Terkategori</p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">{{ $golongans->sum('products_count') }}</p>
            </div>
            <div class="p-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                <flux:icon name="cube" class="size-5" />
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <flux:card class="p-3">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari kode prefix atau nama kategori barang..."
                class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-9 pr-4 py-2 text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-zinc-900 dark:focus:ring-zinc-100" />
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-zinc-400">
                <flux:icon name="magnifying-glass" class="size-4" />
            </div>
        </div>
    </flux:card>

    <!-- Tabel Data Kategori -->
    <flux:card class="p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-100 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 text-xs font-semibold uppercase text-zinc-600 dark:text-zinc-400">
                    <tr>
                        <th class="py-3 px-4 w-32">Kode Prefix</th>
                        <th class="py-3 px-4">Nama Kategori</th>
                        <th class="py-3 px-4 text-center w-40">Jumlah Produk</th>
                        <th class="py-3 px-4 text-right w-44">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($golongans as $g)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md font-mono text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 border border-zinc-200 dark:border-zinc-700">
                                    {{ $g->kode_golongan }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $g->nama_golongan }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('admin.produk.index', ['golongan' => $g->id]) }}" wire:navigate class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 text-zinc-700 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="Lihat semua produk di kategori ini">
                                    {{ $g->products_count }} Produk &rarr;
                                </a>
                            </td>
                            <td class="py-3 px-4 text-right space-x-1">
                                <flux:button wire:click="edit('{{ $g->id }}')" size="sm" variant="subtle" icon="pencil-square">
                                    Edit
                                </flux:button>
                                <flux:button wire:click="delete('{{ $g->id }}')" wire:confirm="Yakin ingin menghapus kategori ini?" size="sm" variant="danger" icon="trash">
                                    Hapus
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-zinc-500">
                                <flux:icon name="tag" class="size-8 mx-auto mb-2 text-zinc-400" />
                                <p class="text-sm font-medium">Belum ada kategori yang cocok</p>
                                <p class="text-xs text-zinc-400 mt-1">Tambahkan kategori baru atau ubah kata kunci pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    <!-- Modal Form Tambah / Edit Kategori -->
    <flux:modal wire:model="showModal" class="md:w-96 space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Kategori' : 'Tambah Kategori Baru' }}</flux:heading>
            <flux:subheading>Kode prefix 2 digit digunakan sebagai awalan barcode internal barang.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="kode_golongan" label="Kode Prefix (2 Digit)" placeholder="01" maxlength="2" required />
            <flux:input wire:model="nama_golongan" label="Nama Kategori" placeholder="Contoh: Makanan & Minuman" required />

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">
                    {{ $isEditing ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
