<?php

namespace App\Livewire\Admin;

use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayrollService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Penggajian & Slip Gaji - MaPOS')]
class PenggajianIndex extends Component
{
    use WithPagination;

    #[Url(as: 'periode')]
    public string $filterBulan = '';

    // Modal Buat Slip Gaji
    public bool $showCreateModal = false;

    public string $selectedEmployeeId = '';

    public $gaji_pokok = 0;

    public $tunjangan = 0;

    public $bonus = 0;

    public $potongan_lain = 0;

    public float $previewKasbon = 0;

    public bool $potongKasbonOtomatis = true;

    public bool $bayarLangsung = false;

    public string $catatan = '';

    // Modal Cetak Slip Gaji
    public bool $showSlipModal = false;

    public ?string $selectedPayrollId = null;

    public function mount(): void
    {
        if (empty($this->filterBulan)) {
            $this->filterBulan = date('Y-m');
        }
    }

    public function updatedFilterBulan(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->selectedEmployeeId = '';
        $this->gaji_pokok = 0;
        $this->tunjangan = 0;
        $this->bonus = 0;
        $this->potongan_lain = 0;
        $this->previewKasbon = 0;
        $this->potongKasbonOtomatis = true;
        $this->bayarLangsung = false;
        $this->catatan = '';
        $this->showCreateModal = true;
    }

    public function updatedSelectedEmployeeId(string $employeeId, PayrollService $service): void
    {
        if (empty($employeeId)) {
            $this->gaji_pokok = 0;
            $this->tunjangan = 0;
            $this->previewKasbon = 0;

            return;
        }

        $emp = Employee::find($employeeId);
        if ($emp) {
            $this->gaji_pokok = (float) $emp->gaji_pokok;
            $this->tunjangan = (float) $emp->tunjangan;
            $this->previewKasbon = (float) $service->getUnsettledKasbon($emp->id, $this->filterBulan)->sum('nominal');
        }
    }

    public function savePayroll(PayrollService $service): void
    {
        $this->validate([
            'selectedEmployeeId' => ['required', 'exists:employees,id'],
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
            'tunjangan' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'potongan_lain' => ['nullable', 'numeric', 'min:0'],
        ], [
            'selectedEmployeeId.required' => 'Pilih karyawan terlebih dahulu.',
        ]);

        try {
            $employee = Employee::findOrFail($this->selectedEmployeeId);
            $payroll = $service->createPayroll(
                Auth::user(),
                $employee,
                $this->filterBulan,
                (float) $this->gaji_pokok,
                (float) $this->tunjangan,
                (float) ($this->bonus ?? 0),
                (float) ($this->potongan_lain ?? 0),
                $this->potongKasbonOtomatis,
                $this->bayarLangsung,
                $this->catatan
            );

            $this->showCreateModal = false;

            try {
                Flux::toast(variant: 'success', text: "Slip gaji {$payroll->no_slip} berhasil dibuat.");
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            $this->addError('payrollError', $e->getMessage());
        }
    }

    public function payPayroll(string $payrollId, PayrollService $service): void
    {
        $payroll = $service->markAsPaid(Auth::user(), $payrollId);

        try {
            Flux::toast(variant: 'success', text: "Gaji {$payroll->no_slip} dibayar & tercatat di arus kas.");
        } catch (\Throwable) {
        }
    }

    public function showSlip(string $payrollId): void
    {
        $this->selectedPayrollId = $payrollId;
        $this->showSlipModal = true;
    }

    public function render()
    {
        $payrolls = Payroll::with(['employee.jabatan', 'kasbonItems'])
            ->when($this->filterBulan, fn ($q, $b) => $q->where('periode_bulan', $b))
            ->latest('created_at')
            ->paginate(15);

        $statsBase = Payroll::when($this->filterBulan, fn ($q, $b) => $q->where('periode_bulan', $b));
        $totalGajiPeriode = (float) (clone $statsBase)->sum('total_diterima');
        $totalPotonganKasbon = (float) (clone $statsBase)->sum('potongan_kasbon');
        $totalDibayar = (float) (clone $statsBase)->where('status', 'dibayar')->sum('total_diterima');

        $activeEmployees = Employee::with('jabatan')
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        $selectedPayroll = $this->selectedPayrollId
            ? Payroll::with(['employee.jabatan', 'user', 'kasbonItems'])->find($this->selectedPayrollId)
            : null;

        return view('livewire.admin.penggajian-index', [
            'payrolls' => $payrolls,
            'activeEmployees' => $activeEmployees,
            'totalGajiPeriode' => $totalGajiPeriode,
            'totalPotonganKasbon' => $totalPotonganKasbon,
            'totalDibayar' => $totalDibayar,
            'selectedPayroll' => $selectedPayroll,
        ]);
    }
}
