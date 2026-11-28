<?php

use App\Enums\PengeluaranKategori;
use App\Enums\UserRole;
use App\Livewire\Admin\JabatanIndex;
use App\Livewire\Admin\KaryawanIndex;
use App\Livewire\Admin\PengeluaranIndex;
use App\Livewire\Admin\PenggajianIndex;
use App\Livewire\Kasir\PosIndex;
use App\Models\CashShift;
use App\Models\Employee;
use App\Models\Jabatan;
use App\Models\Payroll;
use App\Models\Pengeluaran;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();
});

test('admin can access kepegawaian pages and kasir is forbidden', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $kasir = User::where('role', UserRole::Kasir)->first();

    $this->actingAs($admin)->get(route('admin.jabatan.index'))->assertOk()->assertSee('Master Jabatan');
    $this->actingAs($admin)->get(route('admin.karyawan.index'))->assertOk()->assertSee('Data Karyawan');
    $this->actingAs($admin)->get(route('admin.penggajian.index'))->assertOk()->assertSee('Penggajian');

    $this->actingAs($kasir)->get(route('admin.jabatan.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.karyawan.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.penggajian.index'))->assertForbidden();
});

test('admin can create jabatan and employee inherits salary defaults', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    Livewire::actingAs($admin)
        ->test(JabatanIndex::class)
        ->call('create')
        ->set('nama_jabatan', 'Kepala Shift')
        ->set('gaji_pokok_default', 3200000)
        ->set('tunjangan_default', 300000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $jabatan = Jabatan::where('nama_jabatan', 'Kepala Shift')->first();
    expect($jabatan)->not->toBeNull();
    expect((float) $jabatan->gaji_pokok_default)->toBe(3200000.0);

    Livewire::actingAs($admin)
        ->test(KaryawanIndex::class)
        ->call('create')
        ->set('jabatan_id', $jabatan->id)
        ->assertSet('gaji_pokok', 3200000.0)
        ->assertSet('tunjangan', 300000.0)
        ->set('nama', 'Siti Rahma')
        ->set('telepon', '081234567890')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $employee = Employee::where('nama', 'Siti Rahma')->first();
    expect($employee)->not->toBeNull();
    expect($employee->jabatan_id)->toBe($jabatan->id);

    // Deleting jabatan that still has employees should be rejected
    Livewire::actingAs($admin)
        ->test(JabatanIndex::class)
        ->call('delete', $jabatan->id);

    expect(Jabatan::find($jabatan->id))->not->toBeNull();
});

test('employee kasbon is automatically deducted on payroll and prevents double deduction', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $jabatan = Jabatan::create([
        'kode_jabatan' => 'JBT-TEST1',
        'nama_jabatan' => 'Kasir Senior',
        'gaji_pokok_default' => 2500000,
        'tunjangan_default' => 200000,
    ]);

    $employee = Employee::create([
        'kode_karyawan' => 'EMP-TEST1',
        'jabatan_id' => $jabatan->id,
        'nama' => 'Budi Santoso',
        'gaji_pokok' => 2500000,
        'tunjangan' => 200000,
        'status_aktif' => true,
    ]);

    $periode = now()->format('Y-m');

    // Record Kasbon linked to employee via PengeluaranIndex
    Livewire::actingAs($admin)
        ->test(PengeluaranIndex::class)
        ->call('openCreateModal', 'kasbon')
        ->set('selectedEmployeeId', $employee->id)
        ->assertSet('nama_karyawan', 'Budi Santoso')
        ->set('nominal', 150000)
        ->set('keterangan', 'Pinjaman kasbon mendadak')
        ->set('waktu', now()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $kasbon = Pengeluaran::where('employee_id', $employee->id)->first();
    expect($kasbon)->not->toBeNull();
    expect($kasbon->payroll_id)->toBeNull();

    // Generate payroll for current month
    Livewire::actingAs($admin)
        ->test(PenggajianIndex::class)
        ->set('filterBulan', $periode)
        ->call('openCreateModal')
        ->set('selectedEmployeeId', $employee->id)
        ->assertSet('previewKasbon', 150000.0)
        ->set('bonus', 100000)
        ->set('potongKasbonOtomatis', true)
        ->set('bayarLangsung', false)
        ->call('savePayroll')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $payroll = Payroll::where('employee_id', $employee->id)->first();
    expect($payroll)->not->toBeNull();
    expect((float) $payroll->potongan_kasbon)->toBe(150000.0);
    // 2,500,000 + 200,000 + 100,000 - 150,000 = 2,650,000
    expect((float) $payroll->total_diterima)->toBe(2650000.0);
    expect($payroll->status)->toBe('draft');

    $kasbon->refresh();
    expect($kasbon->payroll_id)->toBe($payroll->id);

    // Selecting same employee again should show 0 unsettled kasbon (no double deduction)
    Livewire::actingAs($admin)
        ->test(PenggajianIndex::class)
        ->set('filterBulan', $periode)
        ->call('openCreateModal')
        ->set('selectedEmployeeId', $employee->id)
        ->assertSet('previewKasbon', 0.0);

    // Mark payroll as paid -> creates Gaji expense record
    Livewire::actingAs($admin)
        ->test(PenggajianIndex::class)
        ->set('filterBulan', $periode)
        ->call('payPayroll', $payroll->id);

    $payroll->refresh();
    expect($payroll->status)->toBe('dibayar');
    expect($payroll->tanggal_bayar)->not->toBeNull();

    $gajiExpense = Pengeluaran::where('kategori', PengeluaranKategori::Gaji)
        ->where('employee_id', $employee->id)
        ->first();
    expect($gajiExpense)->not->toBeNull();
    expect((float) $gajiExpense->nominal)->toBe(2650000.0);
});

test('kasir can open shift by selecting active employee', function () {
    $kasir = User::where('role', UserRole::Kasir)->first();
    $jabatan = Jabatan::create([
        'kode_jabatan' => 'JBT-KSR',
        'nama_jabatan' => 'Kasir',
        'gaji_pokok_default' => 2000000,
        'tunjangan_default' => 0,
    ]);
    $employee = Employee::create([
        'kode_karyawan' => 'EMP-KSR',
        'jabatan_id' => $jabatan->id,
        'user_id' => $kasir->id,
        'nama' => 'Rina Permata',
        'gaji_pokok' => 2000000,
        'tunjangan' => 0,
        'status_aktif' => true,
    ]);

    Livewire::actingAs($kasir)
        ->test(PosIndex::class)
        ->call('openShiftModal')
        ->set('selectedShiftEmployeeId', $employee->id)
        ->assertSet('namaPetugasInput', 'Rina Permata')
        ->set('saldoAwalInput', 150000)
        ->call('openShift')
        ->assertHasNoErrors();

    $shift = CashShift::where('user_id', $kasir->id)->where('status', 'open')->first();
    expect($shift)->not->toBeNull();
    expect($shift->employee_id)->toBe($employee->id);
    expect($shift->nama_petugas)->toBe('Rina Permata');
});

test('seeders create individual cashier accounts for Choerul and Raihaan and exclude admin from payroll', function () {
    $admin = User::where('email', 'admin@mapos.test')->first();
    $choerul = User::where('email', 'choerul@mapos.test')->first();
    $raihaan = User::where('email', 'raihaan@mapos.test')->first();

    expect($choerul)->not->toBeNull();
    expect($choerul->name)->toBe('Choerul Nuril Huda');
    expect($choerul->role)->toBe(UserRole::Kasir);

    expect($raihaan)->not->toBeNull();
    expect($raihaan->name)->toBe('Raihaan Adian');
    expect($raihaan->role)->toBe(UserRole::Kasir);

    expect(Employee::where('user_id', $admin->id)->exists())->toBeFalse();
    expect(Employee::where('user_id', $choerul->id)->value('nama'))->toBe('Choerul Nuril Huda');
    expect(Employee::where('user_id', $raihaan->id)->value('nama'))->toBe('Raihaan Adian');
});

test('admin can create employee with new cashier login account and pos shift auto-selects them', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $jabatanKasir = Jabatan::where('kode_jabatan', 'JBT-0002')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(KaryawanIndex::class)
        ->call('create')
        ->set('jabatan_id', $jabatanKasir->id)
        ->set('nama', 'Dina Kusuma')
        ->set('create_user_account', true)
        ->set('login_email', 'dina@mapos.test')
        ->set('login_role', 'kasir')
        ->set('login_password', 'secret123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $newUser = User::where('email', 'dina@mapos.test')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->role)->toBe(UserRole::Kasir);

    $newEmployee = Employee::where('user_id', $newUser->id)->first();
    expect($newEmployee)->not->toBeNull();
    expect($newEmployee->nama)->toBe('Dina Kusuma');

    Livewire::actingAs($newUser)
        ->test(PosIndex::class)
        ->call('openShiftModal')
        ->assertSet('selectedShiftEmployeeId', $newEmployee->id)
        ->assertSet('namaPetugasInput', 'Dina Kusuma');
});
