<div class="space-y-6">
    <style>
        @media print {
            body * { visibility: hidden !important; }
            dialog, [data-flux-modal], flux-modal, [popover] {
                position: static !important; inset: auto !important; margin: 0 !important;
                padding: 0 !important; border: none !important; background: transparent !important;
                box-shadow: none !important; overflow: visible !important; max-height: none !important;
            }
            #slip-gaji-print-area, #slip-gaji-print-area * { visibility: visible !important; }
            #slip-gaji-print-area {
                position: fixed !important; left: 0 !important; top: 0 !important;
                width: 78mm !important; padding: 4mm !important; background: white !important; color: black !important;
                font-family: 'Courier New', Courier, monospace !important; font-size: 11px !important;
            }
        }
    </style>

    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Penggajian Karyawan (Payroll)</flux:heading>
            <flux:subheading>Buat slip gaji bulanan untuk kasir &amp; staf toko, potong kasbon otomatis, dan catat ke pengeluaran kas.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.karyawan.index') }}" variant="subtle" icon="identification" wire:navigate>
                Data Karyawan
            </flux:button>
            <input type="month" wire:model.live="filterBulan" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 font-mono" />
            <flux:button type="button" wire:click="openCreateModal" variant="primary" icon="plus">
                Buat Slip Gaji
            </flux:button>
        </div>
    </div>

    <div class="no-print grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Total Gaji Bersih Periode</div>
            <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-zinc-100">Rp {{ number_format($totalGajiPeriode, 0, ',', '.') }}</div>
            <div class="text-[11px] text-zinc-400">Periode {{ $filterBulan }}</div>
        </div>
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Kasbon Terpotong</div>
            <div class="text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">Rp {{ number_format($totalPotonganKasbon, 0, ',', '.') }}</div>
            <div class="text-[11px] text-zinc-400">Otomatis dari catatan kasbon</div>
        </div>
        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm space-y-1">
            <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Sudah Dibayarkan</div>
            <div class="text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
            <div class="text-[11px] text-zinc-400">Masuk buku pengeluaran gaji</div>
        </div>
    </div>

    <div class="no-print bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 uppercase font-semibold text-[11px]">
                    <tr>
                        <th class="p-3">No. Slip</th>
                        <th class="p-3">Karyawan & Jabatan</th>
                        <th class="p-3 text-right">Gaji + Tunj. + Bonus</th>
                        <th class="p-3 text-right">Pot. Kasbon</th>
                        <th class="p-3 text-right">Total Diterima</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800 font-mono">
                    @forelse($payrolls as $pr)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-bold text-zinc-900 dark:text-zinc-100">{{ $pr->no_slip }}</td>
                        <td class="p-3 font-sans">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $pr->employee?->nama ?? '-' }}</div>
                            <div class="text-[11px] text-zinc-400">{{ $pr->employee?->jabatan?->nama_jabatan ?? '-' }}</div>
                        </td>
                        <td class="p-3 text-right">Rp {{ number_format($pr->gaji_pokok + $pr->tunjangan + $pr->bonus, 0, ',', '.') }}</td>
                        <td class="p-3 text-right text-amber-600">- Rp {{ number_format($pr->potongan_kasbon + $pr->potongan_lain, 0, ',', '.') }}</td>
                        <td class="p-3 text-right font-bold text-zinc-900 dark:text-zinc-100">Rp {{ number_format($pr->total_diterima, 0, ',', '.') }}</td>
                        <td class="p-3 text-center">
                            @if($pr->status === 'dibayar')
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">DIBAYAR</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">DRAFT</span>
                            @endif
                        </td>
                        <td class="p-3 text-right font-sans space-x-1">
                            @if($pr->status === 'draft')
                            <flux:button type="button" wire:click="payPayroll('{{ $pr->id }}')" wire:confirm="Tandai slip {{ $pr->no_slip }} sudah dibayar & catat pengeluaran kas?" size="xs" variant="primary" icon="banknotes" class="bg-emerald-600 hover:bg-emerald-700">
                                Bayar
                            </flux:button>
                            @endif
                            <flux:button type="button" wire:click="showSlip('{{ $pr->id }}')" size="xs" variant="subtle" icon="printer">
                                Slip
                            </flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-zinc-400 font-sans">Belum ada slip gaji pada periode {{ $filterBulan }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payrolls->hasPages())
        <div class="p-3 border-t border-zinc-200 dark:border-zinc-800">{{ $payrolls->links() }}</div>
        @endif
    </div>

    <!-- Modal Buat Slip Gaji -->
    <flux:modal wire:model="showCreateModal" class="md:w-[500px] space-y-4">
        <div>
            <flux:heading size="lg">Buat Slip Gaji ({{ $filterBulan }})</flux:heading>
            <flux:subheading>Kasbon karyawan pada bulan terpilih ditarik otomatis agar tidak terpotong dua kali.</flux:subheading>
        </div>

        @error('payrollError')
        <div class="p-2.5 rounded border border-rose-300 bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-300 text-xs">{{ $message }}</div>
        @enderror

        <form wire:submit="savePayroll" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Pilih Karyawan *</label>
                <select wire:model.live="selectedEmployeeId" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2" required>
                    <option value="">-- Pilih Karyawan Aktif --</option>
                    @foreach($activeEmployees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->nama }} ({{ $emp->jabatan?->nama_jabatan ?? '-' }})</option>
                    @endforeach
                </select>
                @error('selectedEmployeeId') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Gaji Pokok (Rp) *</label>
                    <flux:input type="number" step="1000" min="0" wire:model.live="gaji_pokok" size="sm" class="font-mono" required />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Tunjangan (Rp) *</label>
                    <flux:input type="number" step="1000" min="0" wire:model.live="tunjangan" size="sm" class="font-mono" required />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Bonus / Lembur (Rp)</label>
                    <flux:input type="number" step="1000" min="0" wire:model.live="bonus" size="sm" class="font-mono" />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Potongan Lain (Rp)</label>
                    <flux:input type="number" step="1000" min="0" wire:model.live="potongan_lain" size="sm" class="font-mono" />
                </div>
            </div>

            <div class="p-3 rounded border border-amber-200 dark:border-amber-800/60 bg-amber-50/70 dark:bg-amber-950/30 space-y-1.5 font-mono">
                <div class="flex justify-between">
                    <span>Kasbon Belum Dipotong ({{ $filterBulan }}):</span>
                    <span class="font-bold text-amber-700 dark:text-amber-300">Rp {{ number_format($previewKasbon, 0, ',', '.') }}</span>
                </div>
                <label class="flex items-center gap-2 font-sans cursor-pointer text-[11px]">
                    <input type="checkbox" wire:model.live="potongKasbonOtomatis" class="rounded border-zinc-300 text-indigo-600" />
                    <span>Potong kasbon otomatis pada slip ini</span>
                </label>
            </div>

            @php
                $netPreview = max(0, (float)$gaji_pokok + (float)$tunjangan + (float)$bonus - ($potongKasbonOtomatis ? $previewKasbon : 0) - (float)$potongan_lain);
            @endphp
            <div class="p-3 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex justify-between items-center font-mono">
                <span class="font-bold">Total Gaji Diterima:</span>
                <span class="text-base font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($netPreview, 0, ',', '.') }}</span>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="bayarLangsung" class="rounded border-zinc-300 text-emerald-600" />
                <span>Langsung tandai <strong>DIBAYAR</strong> & catat ke Pengeluaran Gaji</span>
            </label>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Catatan (Opsional)</label>
                <flux:input wire:model="catatan" size="sm" placeholder="Contoh: Bonus kerajinan" />
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Slip Gaji</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Cetak Slip Gaji -->
    <flux:modal wire:model="showSlipModal" class="md:w-[440px] space-y-4">
        <div class="no-print">
            <flux:heading size="lg">Slip Gaji Karyawan</flux:heading>
        </div>

        @if($selectedPayroll)
        @php $storeProfile = \App\Models\StoreProfile::getProfile(); @endphp
        <div id="slip-gaji-print-area" class="p-4 bg-white text-zinc-900 border border-dashed border-zinc-300 rounded font-mono text-xs space-y-2">
            <div class="text-center border-b border-dashed border-zinc-400 pb-2">
                <div class="font-bold text-sm uppercase">{{ $storeProfile->nama_toko }}</div>
                <div class="text-[10px] font-bold">SLIP GAJI KARYAWAN</div>
            </div>
            <div class="text-[11px] space-y-0.5 border-b border-dashed border-zinc-300 pb-1">
                <div class="flex justify-between"><span>No. Slip:</span><strong>{{ $selectedPayroll->no_slip }}</strong></div>
                <div class="flex justify-between"><span>Periode:</span><span>{{ $selectedPayroll->periode_bulan }}</span></div>
                <div class="flex justify-between"><span>Pegawai:</span><strong>{{ $selectedPayroll->employee?->nama }}</strong></div>
                <div class="flex justify-between"><span>Jabatan:</span><span>{{ $selectedPayroll->employee?->jabatan?->nama_jabatan ?? '-' }}</span></div>
            </div>
            <div class="space-y-1 border-b border-dashed border-zinc-300 pb-2 text-[11px]">
                <div class="flex justify-between"><span>Gaji Pokok</span><span>Rp {{ number_format($selectedPayroll->gaji_pokok, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span>Tunjangan</span><span>Rp {{ number_format($selectedPayroll->tunjangan, 0, ',', '.') }}</span></div>
                @if($selectedPayroll->bonus > 0)
                <div class="flex justify-between"><span>Bonus / Lembur</span><span>Rp {{ number_format($selectedPayroll->bonus, 0, ',', '.') }}</span></div>
                @endif
                @if($selectedPayroll->potongan_kasbon > 0)
                <div class="flex justify-between"><span>Potongan Kasbon</span><span>- Rp {{ number_format($selectedPayroll->potongan_kasbon, 0, ',', '.') }}</span></div>
                @endif
                @if($selectedPayroll->potongan_lain > 0)
                <div class="flex justify-between"><span>Potongan Lain</span><span>- Rp {{ number_format($selectedPayroll->potongan_lain, 0, ',', '.') }}</span></div>
                @endif
            </div>
            <div class="flex justify-between font-bold text-sm">
                <span>TOTAL DITERIMA</span>
                <span>Rp {{ number_format($selectedPayroll->total_diterima, 0, ',', '.') }}</span>
            </div>
            <div class="text-[10px] text-center text-zinc-500 pt-2">
                Status: {{ strtoupper($selectedPayroll->status) }}
                @if($selectedPayroll->tanggal_bayar)
                ({{ $selectedPayroll->tanggal_bayar->format('d/m/Y') }})
                @endif
            </div>
        </div>
        @endif

        @php $slipPaper = ($storeProfile ?? \App\Models\StoreProfile::getProfile())->ukuran_kertas ?? '80mm'; @endphp
        <div class="no-print flex justify-end gap-2">
            <flux:button type="button" wire:click="$set('showSlipModal', false)" variant="ghost" size="sm">Tutup</flux:button>
            <flux:button type="button" onclick="window.printThermalReceipt('slip-gaji-print-area', '{{ $slipPaper }}')" variant="primary" size="sm" icon="printer">Cetak Slip</flux:button>
        </div>
    </flux:modal>
</div>
