<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Data Karyawan - MaPOS')]
class KaryawanIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'jabatan')]
    public string $filterJabatan = '';

    public string $filterStatus = '1';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?string $editingId = null;

    public string $kode_karyawan = '';

    public string $jabatan_id = '';

    public ?string $user_id = null;

    public string $nama = '';

    public string $telepon = '';

    public string $alamat = '';

    public string $tanggal_masuk = '';

    public $gaji_pokok = 0;

    public $tunjangan = 0;

    public bool $status_aktif = true;

    public bool $create_user_account = false;

    public string $login_email = '';

    public string $login_password = '';

    public string $login_role = 'kasir';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterJabatan(): void
    {
        $this->resetPage();
    }

    public function updatedJabatanId(string $value): void
    {
        if (! $this->isEditing && ! empty($value)) {
            $jabatan = Jabatan::find($value);
            if ($jabatan) {
                $this->gaji_pokok = (float) $jabatan->gaji_pokok_default;
                $this->tunjangan = (float) $jabatan->tunjangan_default;
            }
        }
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editingId = null;
        $this->kode_karyawan = Employee::generateCode();
        $firstJabatan = Jabatan::orderBy('kode_jabatan')->first();
        $this->jabatan_id = $firstJabatan?->id ?? '';
        $this->user_id = null;
        $this->nama = '';
        $this->telepon = '';
        $this->alamat = '';
        $this->tanggal_masuk = date('Y-m-d');
        $this->gaji_pokok = $firstJabatan ? (float) $firstJabatan->gaji_pokok_default : 0;
        $this->tunjangan = $firstJabatan ? (float) $firstJabatan->tunjangan_default : 0;
        $this->status_aktif = true;
        $this->create_user_account = false;
        $this->login_email = '';
        $this->login_password = '';
        $this->login_role = UserRole::Kasir->value;
        $this->showModal = true;
    }

    public function edit(string $id): void
    {
        $this->resetValidation();
        $emp = Employee::findOrFail($id);
        $this->isEditing = true;
        $this->editingId = $emp->id;
        $this->kode_karyawan = $emp->kode_karyawan;
        $this->jabatan_id = $emp->jabatan_id;
        $this->user_id = $emp->user_id ? (string) $emp->user_id : null;
        $this->nama = $emp->nama;
        $this->telepon = $emp->telepon ?? '';
        $this->alamat = $emp->alamat ?? '';
        $this->tanggal_masuk = $emp->tanggal_masuk?->format('Y-m-d') ?? '';
        $this->gaji_pokok = (float) $emp->gaji_pokok;
        $this->tunjangan = (float) $emp->tunjangan;
        $this->status_aktif = (bool) $emp->status_aktif;
        $this->create_user_account = false;
        $this->login_email = '';
        $this->login_password = '';
        $this->login_role = UserRole::Kasir->value;
        $this->showModal = true;
    }

    public function save(): void
    {
        $uniqueRule = Rule::unique('employees', 'kode_karyawan');
        if ($this->isEditing && $this->editingId) {
            $uniqueRule->ignore($this->editingId);
        }

        $rules = [
            'kode_karyawan' => ['required', 'string', 'max:30', $uniqueRule],
            'jabatan_id' => ['required', 'exists:jabatans,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'nama' => ['required', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'tanggal_masuk' => ['nullable', 'date'],
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
            'tunjangan' => ['required', 'numeric', 'min:0'],
        ];

        if ($this->create_user_account) {
            $rules['login_email'] = ['required', 'email', 'max:150', Rule::unique('users', 'email')];
            $rules['login_password'] = ['required', 'string', 'min:6'];
            $rules['login_role'] = ['required', Rule::in([UserRole::Kasir->value, UserRole::Admin->value])];
        }

        $this->validate($rules, [
            'kode_karyawan.required' => 'Kode karyawan wajib diisi.',
            'jabatan_id.required' => 'Pilih jabatan terlebih dahulu.',
            'nama.required' => 'Nama lengkap karyawan wajib diisi.',
            'login_email.required' => 'Email akun kasir wajib diisi.',
            'login_email.unique' => 'Email akun tersebut sudah digunakan.',
            'login_password.required' => 'Password akun baru wajib diisi minimal 6 karakter.',
            'login_password.min' => 'Password akun baru minimal 6 karakter.',
        ]);

        $msg = DB::transaction(function (): string {
            $resolvedUserId = filled($this->user_id) ? (int) $this->user_id : null;

            if ($this->create_user_account) {
                $user = User::create([
                    'name' => trim($this->nama),
                    'email' => strtolower(trim($this->login_email)),
                    'role' => UserRole::from($this->login_role),
                    'password' => Hash::make($this->login_password),
                    'email_verified_at' => now(),
                ]);
                $resolvedUserId = $user->id;
            }

            $data = [
                'kode_karyawan' => trim($this->kode_karyawan),
                'jabatan_id' => $this->jabatan_id,
                'user_id' => $resolvedUserId,
                'nama' => trim($this->nama),
                'telepon' => filled($this->telepon) ? trim($this->telepon) : null,
                'alamat' => filled($this->alamat) ? trim($this->alamat) : null,
                'tanggal_masuk' => filled($this->tanggal_masuk) ? $this->tanggal_masuk : null,
                'gaji_pokok' => (float) $this->gaji_pokok,
                'tunjangan' => (float) $this->tunjangan,
                'status_aktif' => (bool) $this->status_aktif,
            ];

            if ($this->isEditing && $this->editingId) {
                Employee::findOrFail($this->editingId)->update($data);

                return 'Data karyawan berhasil diperbarui.';
            }

            Employee::create($data);

            return $this->create_user_account
                ? 'Karyawan & akun login baru berhasil didaftarkan.'
                : 'Karyawan baru berhasil didaftarkan.';
        });

        $this->showModal = false;

        try {
            Flux::toast(variant: 'success', text: $msg);
        } catch (\Throwable) {
        }
    }

    public function delete(string $id): void
    {
        $emp = Employee::findOrFail($id);
        $emp->delete();

        try {
            Flux::toast(variant: 'success', text: "Karyawan [{$emp->nama}] berhasil dihapus.");
        } catch (\Throwable) {
        }
    }

    public function render()
    {
        $employees = Employee::with(['jabatan', 'user'])
            ->when($this->search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama', 'like', "%{$s}%")
                        ->orWhere('kode_karyawan', 'like', "%{$s}%")
                        ->orWhere('telepon', 'like', "%{$s}%");
                });
            })
            ->when($this->filterJabatan, fn ($q, $j) => $q->where('jabatan_id', $j))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status_aktif', $this->filterStatus === '1'))
            ->orderBy('nama')
            ->paginate(15);

        $jabatans = Jabatan::orderBy('kode_jabatan')->get();
        $users = User::orderBy('name')->get();

        return view('livewire.admin.karyawan-index', [
            'employees' => $employees,
            'jabatans' => $jabatans,
            'users' => $users,
        ]);
    }
}
