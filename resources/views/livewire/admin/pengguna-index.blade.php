<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Akun Pengguna & Kasir</flux:heading>
            <flux:subheading>Kelola akun login Admin & Kasir agar dapat ditautkan ke Data Karyawan.</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('admin.karyawan.index') }}" variant="subtle" icon="identification" wire:navigate>
                Data Karyawan
            </flux:button>
            <flux:button type="button" wire:click="create" variant="primary" icon="user-plus">
                Tambah Akun Login
            </flux:button>
        </div>
    </div>

    <flux:card class="p-3">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email login..." icon="magnifying-glass" size="sm" clearable />
    </flux:card>

    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs whitespace-nowrap">
                <thead class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 text-zinc-500 uppercase font-semibold text-[11px]">
                    <tr>
                        <th class="p-3">Nama Akun</th>
                        <th class="p-3">Email Login</th>
                        <th class="p-3 text-center">Role Akses</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($users as $u)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition">
                        <td class="p-3 font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $u->name }}
                            @if($u->id === auth()->id())
                            <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 font-semibold">Saya</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-zinc-600 dark:text-zinc-300">{{ $u->email }}</td>
                        <td class="p-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $u->isAdmin() ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                                {{ $u->role?->label() ?? 'Kasir' }}
                            </span>
                        </td>
                        <td class="p-3 text-right space-x-1">
                            <flux:button type="button" wire:click="edit({{ $u->id }})" size="xs" variant="subtle" icon="pencil-square">Edit</flux:button>
                            @if($u->id !== auth()->id())
                            <flux:button type="button" wire:click="delete({{ $u->id }})" wire:confirm="Hapus akun {{ $u->email }}?" size="xs" variant="danger" icon="trash">Hapus</flux:button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-zinc-400">Tidak ada akun pengguna yang cocok.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="showModal" class="md:w-[460px] space-y-4">
        <div>
            <flux:heading size="lg">{{ $isEditing ? 'Edit Akun Pengguna' : 'Tambah Akun Login Baru' }}</flux:heading>
            <flux:subheading>Akun ini dapat digunakan untuk masuk ke kasir POS maupun dashboard admin.</flux:subheading>
        </div>

        <form wire:submit="save" class="space-y-3 text-xs">
            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Nama Tampilan *</label>
                <flux:input wire:model="name" size="sm" placeholder="Contoh: Kasir Shift Sore" required />
                @error('name') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Email Login *</label>
                    <flux:input type="email" wire:model="email" size="sm" placeholder="kasir2@mapos.test" required />
                    @error('email') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">Role Akses *</label>
                    <select wire:model="role" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                        <option value="kasir">Kasir (POS Only)</option>
                        <option value="admin">Administrator (Full Akses)</option>
                    </select>
                    @error('role') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block font-medium text-zinc-600 dark:text-zinc-300 mb-1">
                    Password {{ $isEditing ? '(Kosongkan jika tidak diubah)' : '*' }}
                </label>
                <flux:input type="password" wire:model="password" size="sm" placeholder="Minimal 6 karakter" :required="! $isEditing" />
                @error('password') <span class="text-rose-500 text-[11px]">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost" size="sm">Batal</flux:button>
                <flux:button type="submit" variant="primary" size="sm">Simpan Akun</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
