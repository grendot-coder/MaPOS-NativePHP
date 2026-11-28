<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Pelanggan, Member & Piutang</flux:heading>
            <flux:subheading>Kelola kartu member, poin loyalitas belanja, limit kredit bon, serta cicilan pelunasan piutang pelanggan.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button type="button" wire:click="openCreateModal" variant="primary" icon="user-plus">
                + Tambah Member
            </flux:button>
        </div>
    </div>

    <!-- 3 Kartu Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <span>Total Member Terdaftar</span>
                <flux:icon name="users" class="size-4 text-zinc-400" />
            </div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">{{ number_format($totalMember) }}</div>
            <div class="text-[11px] text-zinc-400">Pelanggan aktif</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <span>Total Piutang (Bon) Berjalan</span>
                <flux:icon name="clock" class="size-4 text-amber-500" />
            </div>
            <div class="text-2xl font-bold font-mono {{ $totalPiutang > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-zinc-400">Tagihan pelanggan belum lunas</div>
        </div>

        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-zinc-500">
                <span>Total Poin Loyalitas Aktif</span>
                <flux:icon name="star" class="size-4 text-indigo-500" />
            </div>
            <div class="text-2xl font-bold font-mono text-indigo-600 dark:text-indigo-400">{{ number_format($totalPoinAktif) }} pts</div>
            <div class="text-[11px] text-zinc-400">1 poin per Rp 10.000 belanja lunas</div>
        </div>
    </div>

    <!-- Filter -->
    <flux:card class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kode member, nama, atau nomor HP..." icon="magnifying-glass" size="sm" clearable />
        </div>
        <div>
            <select wire:model.live="filterPiutang" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
                <option value="">Semua Member</option>
                <option value="piutang">Hanya yang Punya Piutang (Bon)</option>
            </select>
        </div>
    </flux:card>

    <!-- Tabel Pelanggan -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 font-semibold uppercase text-[11px]">
                    <tr>
                        <th class="p-3">Kode Member</th>
                        <th class="p-3">Nama Pelanggan & Kontak</th>
                        <th class="p-3 text-center">Poin</th>
                        <th class="p-3 text-right">Limit Bon</th>
                        <th class="p-3 text-right">Piutang Berjalan</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($customers as $c)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $c->kode_member }}
                        </td>
                        <td class="p-3">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $c->nama }}</div>
                            <div class="text-[11px] font-mono text-zinc-400">
                                {{ $c->telepon ?: '-' }} {{ $c->alamat ? '• '.\Illuminate\Support\Str::limit($c->alamat, 35) : '' }}
                            </div>
                        </td>
                        <td class="p-3 text-center font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ number_format($c->poin) }}
                        </td>
                        <td class="p-3 text-right font-mono text-zinc-500">
                            Rp {{ number_format($c->limit_kredit, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold {{ $c->piutang_berjalan > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-400' }}">
                            Rp {{ number_format($c->piutang_berjalan, 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-center space-x-1">
                            @if($c->piutang_berjalan > 0)
                            <flux:button type="button" wire:click="openPaymentModal('{{ $c->id }}')" size="xs" variant="primary" icon="banknotes" class="bg-emerald-600 hover:bg-emerald-700">
                                Bayar Bon
                            </flux:button>
                            @endif
                            <flux:button type="button" wire:click="openEditModal('{{ $c->id }}')" size="xs" variant="subtle" icon="pencil-square" />
                            <flux:button type="button" wire:click="delete('{{ $c->id }}')" wire:confirm="Hapus member {{ $c->nama }}?" size="xs" variant="danger" icon="trash" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-xs text-zinc-400">
                            Belum ada data pelanggan/member yang sesuai.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="p-3 border-t border-zinc-200 dark:border-zinc-800">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- Modal Form Member -->
    <flux:modal wire:model="showFormModal" class="md:w-[460px] space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Data Member' : 'Daftarkan Member Baru' }}</flux:heading>
            <flux:subheading>Member dapat mengumpulkan poin belanja dan bertransaksi tempo sesuai limit kredit.</flux:subheading>
        </div>

        <form wire:submit="saveCustomer" class="space-y-3 text-xs">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Kode Member *</label>
                    <flux:input wire:model="kode_member" size="sm" class="font-mono" required />
                    @error('kode_member') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">No. HP / WhatsApp</label>
                    <flux:input wire:model="telepon" size="sm" placeholder="08xxxxxxxxxx" />
                </div>
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nama Lengkap *</label>
                <flux:input wire:model="nama" size="sm" required />
                @error('nama') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Limit Kredit Bon / Tempo (Rp) *</label>
                <flux:input type="number" step="10000" min="0" wire:model="limit_kredit" size="sm" class="font-mono" />
                <span class="text-[11px] text-zinc-400">Isi 0 jika pelanggan hanya boleh transaksi tunai/QRIS/transfer.</span>
                @error('limit_kredit') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Alamat</label>
                <textarea wire:model="alamat" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showFormModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Member</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Bayar Cicilan Piutang -->
    <flux:modal wire:model="showPaymentModal" class="md:w-[480px] space-y-4">
        <div>
            <flux:heading size="lg">Pembayaran Piutang / Bon Pelanggan</flux:heading>
            @if($payingCustomer)
            <flux:subheading>{{ $payingCustomer->nama }} &bull; Total Piutang: <strong>Rp {{ number_format($payingCustomer->piutang_berjalan, 0, ',', '.') }}</strong></flux:subheading>
            @endif
        </div>

        <form wire:submit="savePiutangPayment" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Pilih Nota Bon *</label>
                <select wire:model.live="selectedSaleId" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 font-mono">
                    @foreach($unpaidSales as $us)
                    <option value="{{ $us->id }}">{{ $us->no_transaksi }} ({{ $us->waktu->format('d/m/Y') }}) — Sisa: Rp {{ number_format($us->sisa_piutang, 0, ',', '.') }}</option>
                    @endforeach
                </select>
                @error('selectedSaleId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nominal Bayar (Rp) *</label>
                    <flux:input type="number" step="100" min="100" wire:model="paymentAmount" size="sm" class="font-mono font-bold" required />
                    @error('paymentAmount') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Metode Pembayaran *</label>
                    <select wire:model="paymentMethod" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                        <option value="cash">Tunai (Cash)</option>
                        <option value="transfer">Transfer Bank</option>
                        <option value="qris">QRIS</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Catatan (Opsional)</label>
                <flux:input wire:model="paymentNote" size="sm" placeholder="Contoh: Cicilan tahap 1 / Pelunasan" />
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showPaymentModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm" class="bg-emerald-600 hover:bg-emerald-700">Simpan Pembayaran</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
