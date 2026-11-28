<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Data Karyawan &amp; Kasir</flux:heading>
            <flux:subheading>Kelola data karyawan, gaji pokok, serta akun login masing-masing kasir untuk shift &amp; penggajian.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.pengguna.index') }}" variant="subtle" icon="key" wire:navigate>
                Akun Login
            </flux:button>
            <flux:button href="{{ route('admin.jabatan.index') }}" variant="subtle" icon="briefcase" wire:navigate>
                Master Jabatan
            </flux:button>
            <flux:button type="button" wire:click="create" variant="primary" icon="user-plus">
                Tambah Karyawan
            </flux:button>
        </div>
    </div>

    <flux:card class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, atau HP..." icon="magnifying-glass" size="sm" clearable />

        <select wire:model.live="filterJabatan" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
            <option value="">Semua Jabatan</option>
            @foreach($jabatans as $j)
            <option value="{{ $j->id }}">[{{ $j->kode_jabatan }}] {{ $j->nama_jabatan }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-800 dark:text-zinc-200">
            <option value="1">Pegawai Aktif</option>
            <option value="0">Nonaktif</option>
            <option value="">Semua Status</option>
        </select>
    </flux:card>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 uppercase font-semibold text-[11px]">
                    <tr>
                        <th class="p-3">Kode</th>
                        <th class="p-3">Nama & Kontak</th>
                        <th class="p-3">Jabatan</th>
                        <th class="p-3 text-right">Gaji Pokok</th>
                        <th class="p-3 text-right">Tunjangan</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($employees as $emp)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-mono font-bold text-zinc-900 dark:text-zinc-100">{{ $emp->kode_karyawan }}</td>
                        <td class="p-3">
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $emp->nama }}</div>
                            <div class="text-[11px] font-mono text-zinc-400">
                                {{ $emp->telepon ?: '-' }}
                                @if($emp->user)
                                &bull; Login: {{ $emp->user->email }}
                                @endif
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-[11px] font-medium">
                                {{ $emp->jabatan?->nama_jabatan ?? '-' }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono font-semibold">Rp {{ number_format($emp->gaji_pokok, 0, ',', '.') }}</td>
                        <td class="p-3 text-right font-mono text-zinc-500">Rp {{ number_format($emp->tunjangan, 0, ',', '.') }}</td>
                        <td class="p-3 text-center">
                            @if($emp->status_aktif)
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">AKTIF</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border border-zinc-200 dark:border-zinc-700">NONAKTIF</span>
                            @endif
                        </td>
                        <td class="p-3 text-right space-x-1">
                            <flux:button type="button" wire:click="edit('{{ $emp->id }}')" size="xs" variant="subtle" icon="pencil-square">Edit</flux:button>
                            <flux:button type="button" wire:click="delete('{{ $emp->id }}')" wire:confirm="Hapus data karyawan {{ $emp->nama }}?" size="xs" variant="danger" icon="trash" />
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-zinc-400">Belum ada data karyawan. Pastikan Anda sudah membuat Master Jabatan terlebih dahulu.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
        <div class="p-3 border-t border-zinc-200 dark:border-zinc-800">{{ $employees->links() }}</div>
        @endif
    </div>

    <flux:modal wire:model="showModal" class="md:w-[500px] space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Data Karyawan' : 'Tambah Karyawan Baru' }}</flux:heading>
            <flux:subheading>Pilih jabatan agar gaji pokok & tunjangan default langsung terisi otomatis.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-3 text-xs">
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Kode Pegawai *</label>
                    <flux:input wire:model="kode_karyawan" size="sm" class="font-mono" required />
                    @error('kode_karyawan') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div class="col-span-2">
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Jabatan *</label>
                    <select wire:model.live="jabatan_id" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2" required>
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($jabatans as $j)
                        <option value="{{ $j->id }}">[{{ $j->kode_jabatan }}] {{ $j->nama_jabatan }}</option>
                        @endforeach
                    </select>
                    @error('jabatan_id') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nama Lengkap *</label>
                    <flux:input wire:model="nama" size="sm" required />
                    @error('nama') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">No. HP / WhatsApp</label>
                    <flux:input wire:model="telepon" size="sm" placeholder="08xxxxxxxxxx" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Gaji Pokok (Rp) *</label>
                    <flux:input type="number" step="50000" min="0" wire:model="gaji_pokok" size="sm" class="font-mono" required />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Tunjangan (Rp) *</label>
                    <flux:input type="number" step="10000" min="0" wire:model="tunjangan" size="sm" class="font-mono" required />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Tanggal Masuk</label>
                    <input type="date" wire:model="tanggal_masuk" class="w-full text-xs font-mono rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2" />
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Tautkan Akun Login Ada</label>
                    <select wire:model="user_id" @disabled($create_user_account) class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 disabled:opacity-50">
                        <option value="">-- Tidak Ditautkan --</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if(! $isEditing || ! $user_id)
            <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/40 space-y-2.5">
                <label class="flex items-center gap-2 cursor-pointer font-semibold text-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.live="create_user_account" class="rounded border-zinc-300 text-indigo-600" />
                    <span>Buat akun login kasir baru sekaligus untuk karyawan ini</span>
                </label>

                @if($create_user_account)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                    <div class="sm:col-span-2">
                        <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Email Login *</label>
                        <flux:input type="email" wire:model="login_email" size="sm" placeholder="nama@mapos.test" />
                        @error('login_email') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Role *</label>
                        <select wire:model="login_role" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-2.5 py-2">
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Password Awal *</label>
                        <flux:input type="password" wire:model="login_password" size="sm" placeholder="Minimal 6 karakter" />
                        @error('login_password') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                    </div>
                </div>
                @endif
            </div>
            @endif

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Alamat</label>
                <textarea wire:model="alamat" rows="2" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-2"></textarea>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model="status_aktif" class="rounded border-zinc-300 text-indigo-600" />
                <span>Status Pegawai Aktif</span>
            </label>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Karyawan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
