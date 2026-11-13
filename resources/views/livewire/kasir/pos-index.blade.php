<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" level="1">Point of Sale (Kasir)</flux:heading>
                <flux:badge color="emerald" size="sm">Kasir Siap</flux:badge>
            </div>
            <flux:subheading>Kasir: {{ auth()->user()->name }} &bull; Shift Aktif: {{ now()->translatedFormat('l, d F Y') }}</flux:subheading>
        </div>
        <div>
            <flux:badge color="blue" size="lg">{{ $totalProdukSiapJual }} Produk Siap Jual</flux:badge>
        </div>
    </div>

    <!-- Banner Info Transaksi Kasir -->
    <flux:card class="p-8 text-center space-y-4 bg-zinc-50 dark:bg-zinc-800/40 border-dashed">
        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
            <flux:icon name="shopping-cart" class="size-8" />
        </div>
        <div>
            <flux:heading size="lg">Layar Transaksi Kasir POS</flux:heading>
            <flux:text class="max-w-md mx-auto text-zinc-500">
                Layar penuh pemindaian barcode dengan scanner fisik, kalkulasi kembalian, struk cetak, dan pemotongan stok otomatis dijadwalkan pada <strong>Hari 5</strong>.
            </flux:text>
        </div>
        <div class="pt-2">
            <flux:button href="{{ route('admin.produk.index') }}" variant="subtle" icon="cube" wire:navigate>
                Lihat Daftar Harga & Stok Produk (Katalog)
            </flux:button>
        </div>
    </flux:card>
</div>
