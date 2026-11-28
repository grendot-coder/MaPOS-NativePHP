<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Master Jabatan</flux:heading>
            <flux:subheading>Atur struktur jabatan pegawai serta standar gaji pokok & tunjangan bawaan.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.karyawan.index') }}" variant="subtle" icon="users" wire:navigate>
                Data Karyawan
            </flux:button>
            <flux:button type="button" wire:click="create" variant="primary" icon="plus">
                Tambah Jabatan
            </flux:button>
        </div>
    </div>

    <flux:card class="p-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari kode atau nama jabatan..." icon="magnifying-glass" size="sm" clearable />
    </flux:card>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 uppercase font-semibold text-[11px]">
                    <tr>
                        <th class="p-3">Kode</th>
                        <th class="p-3">Nama Jabatan</th>
                        <th class="p-3 text-right">Gaji Pokok Standar</th>
                        <th class="p-3 text-right">Tunjangan Standar</th>
                        <th class="p-3 text-center">Jumlah Karyawan</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($jabatans as $j)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $j->kode_jabatan }}</td>
                        <td class="p-3">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $j->nama_jabatan }}</div>
                            @if($j->deskripsi)
                            <div class="text-[11px] text-zinc-400">{{ \Illuminate\Support\Str::limit($j->deskripsi, 50) }}</div>
                            @endif
                        </td>
                        <td class="p-3 text-right font-mono font-semibold">Rp {{ number_format($j->gaji_pokok_default, 0, ',', '.') }}</td>
                        <td class="p-3 text-right font-mono text-zinc-600 dark:text-zinc-300">Rp {{ number_format($j->tunjangan_default, 0, ',', '.') }}</td>
                        <td class="p-3 text-center">
                            <a href="{{ route('admin.karyawan.index', ['jabatan' => $j->id]) }}" wire:navigate class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-zinc-100 dark:bg-zinc-800 hover:text-indigo-600">
                                {{ $j->employees_count }} Pegawai
                            </a>
                        </td>
                        <td class="p-3 text-right space-x-1">
                            <flux:button type="button" wire:click="edit('{{ $j->id }}')" size="xs" variant="subtle" icon="pencil-square">Edit</flux:button>
                            <flux:button type="button" wire:click="delete('{{ $j->id }}')" wire:confirm="Hapus jabatan {{ $j->nama_jabatan }}?" size="xs" variant="danger" icon="trash">Hapus</flux:button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-zinc-400">Belum ada data jabatan. Tambahkan jabatan seperti Kepala Toko, Kasir, Pramuniaga, atau Gudang.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="showModal" class="md:w-[460px] space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Jabatan' : 'Tambah Jabatan Baru' }}</flux:heading>
            <flux:subheading>Standar gaji dan tunjangan akan terisi otomatis saat mendaftarkan karyawan pada jabatan ini.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-3 text-xs">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Kode *</label>
                    <flux:input wire:model="kode_jabatan" size="sm" class="font-mono" required />
                    @error('kode_jabatan') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nama Jabatan *</label>
                    <flux:input wire:model="nama_jabatan" size="sm" placeholder="Contoh: Kasir / Kepala Toko / Gudang" required />
                    @error('nama_jabatan') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Gaji Pokok Standar (Rp) *</label>
                    <flux:input type="number" step="50000" min="0" wire:model="gaji_pokok_default" size="sm" class="font-mono" required />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Tunjangan Standar (Rp) *</label>
                    <flux:input type="number" step="10000" min="0" wire:model="tunjangan_default" size="sm" class="font-mono" required />
                </div>
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Deskripsi Tugas (Opsional)</label>
                <textarea wire:model="deskripsi" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Jabatan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
