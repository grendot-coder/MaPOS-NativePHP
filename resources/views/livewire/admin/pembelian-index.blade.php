<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pembelian & Restock Barang</flux:heading>
            <flux:subheading>Catat faktur masuk dari supplier / agen kulakan untuk menambah stok gudang & etalase secara otomatis.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="openSupplierModal" variant="subtle" icon="user-plus">
                + Supplier Baru
            </flux:button>
            <flux:button :href="route('admin.pembelian.create')" variant="primary" icon="plus" wire:navigate class="bg-indigo-600 hover:bg-indigo-700">
                + Faktur Kulakan Baru
            </flux:button>
        </div>
    </div>

    <!-- 3 Kartu Statistik Ringkasan (High-Density, Solid Neutral, No Pills) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Faktur Masuk Bulan Ini</span>
                <flux:icon name="document-text" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">
                {{ number_format($totalFaktur, 0, ',', '.') }} <span class="text-xs font-normal text-zinc-500">faktur</span>
            </div>
            <div class="text-[11px] text-zinc-400">Periode: {{ date('F Y', strtotime($filterBulan . '-01')) }}</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Total Belanja / Kulakan</span>
                <flux:icon name="banknotes" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400">
                Rp {{ number_format($totalBelanja, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Total nilai modal stok masuk</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                <span>Sisa Hutang Supplier</span>
                <flux:icon name="clock" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">
                Rp {{ number_format($totalHutang, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Faktur berstatus tempo / belum lunas</div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <flux:card class="p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">Periode Bulan:</label>
                <input type="month" wire:model.live="filterBulan" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200 font-mono" />
            </div>

            <div>
                <label class="block text-[11px] font-medium text-zinc-500 mb-1">Status Pembayaran:</label>
                <select wire:model.live="statusFilter" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-1.5 text-zinc-800 dark:text-zinc-200">
                    <option value="">Semua Status</option>
                    <option value="lunas">Lunas</option>
                    <option value="hutang">Hutang (Tempo)</option>
                </select>
            </div>
        </div>

        <div class="w-full md:w-72">
            <label class="block text-[11px] font-medium text-zinc-500 mb-1">Cari Faktur / Supplier:</label>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Ketik no faktur atau supplier..." icon="magnifying-glass" clearable size="sm" />
        </div>
    </flux:card>

    <!-- Tabel Riwayat Faktur Pembelian (High-Density POS Layout) -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-semibold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">No. Faktur</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Supplier</th>
                        <th class="p-3 text-center">Jml Item</th>
                        <th class="p-3 text-right">Total Faktur</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3">Diterima Oleh</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($purchases as $p)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $p->no_faktur }}
                        </td>
                        <td class="p-3 font-mono text-[11px] text-zinc-600 dark:text-zinc-400">
                            {{ $p->tanggal->format('d/m/Y') }}
                        </td>
                        <td class="p-3">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->nama_supplier }}</div>
                            @if($p->supplier && $p->supplier->telepon)
                            <div class="text-[11px] text-zinc-400 font-mono">{{ $p->supplier->telepon }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-center font-mono">
                            <span class="text-zinc-700 dark:text-zinc-300 font-semibold">{{ $p->items->count() }} item</span>
                            <span class="text-[11px] text-zinc-400 block">({{ $p->items->sum('qty') }} pcs)</span>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-sm text-zinc-900 dark:text-zinc-100">
                            Rp {{ number_format($p->total, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-center">
                            @if($p->status_pembayaran === 'lunas')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                                LUNAS
                            </span>
                            @else
                            <div class="space-y-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                                    HUTANG: Rp {{ number_format($p->sisa_hutang, 0, ',', '.') }}
                                </span>
                                @if($p->jatuh_tempo)
                                @php $isOverdue = $p->jatuh_tempo->isPast() && ! $p->jatuh_tempo->isToday(); @endphp
                                <div class="text-[10px] font-mono {{ $isOverdue ? 'text-rose-600 font-bold' : 'text-zinc-400' }}">
                                    JT: {{ $p->jatuh_tempo->format('d/m/Y') }}
                                </div>
                                @endif
                            </div>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-[11px] text-zinc-500">
                            {{ $p->user->name ?? '-' }}
                        </td>
                        <td class="p-3 text-center space-x-1">
                            @if($p->status_pembayaran === 'hutang')
                            <button
                                type="button"
                                wire:click="openDebtModal('{{ $p->id }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded border border-indigo-300 dark:border-indigo-700 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 transition"
                                title="Bayar Cicilan / Pelunasan">
                                <flux:icon name="banknotes" class="size-3.5" />
                                <span>Cicil</span>
                            </button>
                            <button
                                type="button"
                                wire:click="markAsPaid('{{ $p->id }}')"
                                wire:confirm="Tandai faktur #{{ $p->no_faktur }} sudah LUNAS dibayar ke supplier?"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 transition"
                                title="Tandai Lunas">
                                <flux:icon name="check" class="size-3.5" />
                                <span>Lunasi</span>
                            </button>
                            @endif
                            <button
                                type="button"
                                wire:click="showDetail('{{ $p->id }}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded border border-zinc-300 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300 transition"
                                title="Lihat Rincian Faktur">
                                <flux:icon name="eye" class="size-3.5" />
                                <span>Rincian</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-xs text-zinc-400">
                            Belum ada faktur pembelian / kulakan yang tercatat pada periode ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
        <div class="p-3 border-t border-zinc-200 dark:border-zinc-800">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Detail Faktur -->
    @if($showDetailModal && $selectedPurchase)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <div class="text-sm font-bold font-mono text-zinc-900 dark:text-zinc-100">
                        FAKTUR #{{ $selectedPurchase->no_faktur }}
                    </div>
                    <div class="text-xs text-zinc-500">
                        {{ $selectedPurchase->tanggal->format('d F Y') }} &bull; Supplier: <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $selectedPurchase->nama_supplier }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($selectedPurchase->status_pembayaran === 'lunas')
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                        LUNAS
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                        HUTANG
                    </span>
                    @endif
                    <button type="button" wire:click="closeDetail" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>

            <!-- Modal Body (Table Item Faktur) -->
            <div class="p-4 overflow-y-auto flex-1 space-y-4">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 border-b border-zinc-200 dark:border-zinc-800 text-zinc-500 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="p-2">Kode</th>
                            <th class="p-2">Nama Barang</th>
                            <th class="p-2 text-center">Masuk Ke</th>
                            <th class="p-2 text-center">Qty</th>
                            <th class="p-2 text-right">Harga Modal Beli</th>
                            <th class="p-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($selectedPurchase->items as $item)
                        <tr>
                            <td class="p-2 font-mono text-[11px] text-zinc-500">
                                {{ $item->product->kode_produk ?? '-' }}
                            </td>
                            <td class="p-2 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $item->nama_produk }}
                            </td>
                            <td class="p-2 text-center">
                                <span class="px-1.5 py-0.5 text-[10px] font-mono uppercase rounded border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                    {{ $item->lokasi_tujuan }}
                                </span>
                            </td>
                            <td class="p-2 text-center font-mono font-bold">
                                {{ $item->qty }} {{ $item->product->satuan ?? 'pcs' }}
                            </td>
                            <td class="p-2 text-right font-mono">
                                Rp {{ number_format($item->harga_beli, 0, ',', '.') }}
                            </td>
                            <td class="p-2 text-right font-mono font-bold">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-zinc-300 dark:border-zinc-700 font-mono">
                        <tr>
                            <td colspan="5" class="p-2 text-right font-bold text-xs uppercase tracking-wider text-zinc-500">
                                Total Faktur:
                            </td>
                            <td class="p-2 text-right font-bold text-base text-zinc-900 dark:text-zinc-100">
                                Rp {{ number_format($selectedPurchase->total, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                @if($selectedPurchase->catatan)
                <div class="p-3 rounded border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 text-xs">
                    <span class="font-bold text-zinc-500 uppercase text-[10px] block mb-0.5">Catatan Faktur:</span>
                    <span class="text-zinc-700 dark:text-zinc-300">{{ $selectedPurchase->catatan }}</span>
                </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 flex justify-end gap-2">
                @if($selectedPurchase->status_pembayaran === 'hutang')
                <flux:button
                    type="button"
                    wire:click="markAsPaid('{{ $selectedPurchase->id }}')"
                    wire:confirm="Tandai faktur #{{ $selectedPurchase->no_faktur }} sudah LUNAS dibayar ke supplier?"
                    variant="primary"
                    size="sm"
                    icon="check"
                    class="bg-emerald-600 hover:bg-emerald-700">
                    Tandai Lunas
                </flux:button>
                @endif
                <flux:button type="button" wire:click="closeDetail" variant="subtle" size="sm">
                    Tutup
                </flux:button>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Tambah Supplier Cepat -->
    @if($showSupplierModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl w-full max-w-md p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Tambah Supplier Baru</h3>
                <button type="button" wire:click="$set('showSupplierModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <form wire:submit="saveSupplier" class="space-y-3 text-xs">
                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nama Supplier / PT / Toko Agen <span class="text-rose-500">*</span></label>
                    <flux:input wire:model="supplierNama" placeholder="Contoh: PT. Sumber Makmur" size="sm" required />
                    @error('supplierNama') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">No. Telepon / WhatsApp</label>
                    <flux:input wire:model="supplierTelepon" placeholder="08xxxxxxxxxx" size="sm" />
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nama Sales / Kontak PIC</label>
                    <flux:input wire:model="supplierKontak" placeholder="Nama sales atau agen" size="sm" />
                </div>

                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Alamat Supplier</label>
                    <textarea wire:model="supplierAlamat" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2 text-zinc-800 dark:text-zinc-200" placeholder="Alamat gudang / kantor supplier"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" wire:click="$set('showSupplierModal', false)" variant="subtle" size="sm">Batal</flux:button>
                    <flux:button type="submit" variant="primary" size="sm" class="bg-indigo-600 hover:bg-indigo-700">Simpan Supplier</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal Bayar Cicilan Hutang Supplier -->
    @if($showDebtModal && $payingPurchase)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-zinc-950/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg shadow-xl w-full max-w-md p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Bayar Hutang Faktur #{{ $payingPurchase->no_faktur }}</h3>
                    <p class="text-[11px] text-zinc-500">Sisa hutang: <strong class="font-mono text-amber-600">Rp {{ number_format($payingPurchase->sisa_hutang, 0, ',', '.') }}</strong></p>
                </div>
                <button type="button" wire:click="$set('showDebtModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>

            <form wire:submit="saveDebtPayment" class="space-y-3 text-xs">
                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nominal Bayar (Rp) *</label>
                    <flux:input type="number" step="100" min="100" wire:model="debtPaymentAmount" size="sm" class="font-mono font-bold" required />
                    @error('debtPaymentAmount') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Metode Pembayaran *</label>
                    <select wire:model="debtPaymentMethod" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="transfer">Transfer Bank</option>
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-zinc-700 dark:text-zinc-300 mb-1">Catatan (Opsional)</label>
                    <flux:input wire:model="debtPaymentNote" size="sm" placeholder="Contoh: Cicilan 1 / Pelunasan" />
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button type="button" wire:click="$set('showDebtModal', false)" variant="subtle" size="sm">Batal</flux:button>
                    <flux:button type="submit" variant="primary" size="sm" class="bg-emerald-600 hover:bg-emerald-700">Simpan Pembayaran</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
