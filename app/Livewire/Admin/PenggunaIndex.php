<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Akun Pengguna & Kasir - MaPOS')]
class PenggunaIndex extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'kasir';

    public string $password = '';

    public function create(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->role = UserRole::Kasir->value;
        $this->password = '';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->isEditing = true;
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role?->value ?? UserRole::Kasir->value;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $emailUnique = Rule::unique('users', 'email');
        if ($this->isEditing && $this->editingId) {
            $emailUnique->ignore($this->editingId);
        }

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', $emailUnique],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Kasir->value])],
            'password' => $this->isEditing
                ? ['nullable', 'string', 'min:6']
                : ['required', 'string', 'min:6'],
        ];

        $this->validate($rules, [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email login wajib diisi.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Password awal wajib diisi minimal 6 karakter.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        if ($this->isEditing && $this->editingId) {
            $user = User::findOrFail($this->editingId);

            if ($user->id === Auth::id() && $this->role !== UserRole::Admin->value) {
                $this->addError('role', 'Anda tidak dapat menurunkan role akun yang sedang Anda gunakan.');

                return;
            }

            $data = [
                'name' => trim($this->name),
                'email' => strtolower(trim($this->email)),
                'role' => UserRole::from($this->role),
            ];

            if (filled($this->password)) {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);
            $msg = 'Data akun pengguna berhasil diperbarui.';
        } else {
            User::create([
                'name' => trim($this->name),
                'email' => strtolower(trim($this->email)),
                'role' => UserRole::from($this->role),
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);
            $msg = 'Akun pengguna baru berhasil dibuat.';
        }

        $this->showModal = false;

        try {
            Flux::toast(variant: 'success', text: $msg);
        } catch (\Throwable) {
        }
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            try {
                Flux::toast(variant: 'danger', text: 'Tidak dapat menghapus akun yang sedang aktif login.');
            } catch (\Throwable) {
            }

            return;
        }

        if ($user->role === UserRole::Admin && User::where('role', UserRole::Admin)->count() <= 1) {
            try {
                Flux::toast(variant: 'danger', text: 'Minimal harus ada 1 akun Admin aktif di sistem.');
            } catch (\Throwable) {
            }

            return;
        }

        $user->delete();

        try {
            Flux::toast(variant: 'success', text: 'Akun pengguna berhasil dihapus.');
        } catch (\Throwable) {
        }
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function ($q, $s): void {
                $q->where(function ($sub) use ($s): void {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.pengguna-index', [
            'users' => $users,
        ]);
    }
}
