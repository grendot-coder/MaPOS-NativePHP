<?php

namespace App\Services;

use App\Enums\PengeluaranKategori;
use App\Models\CashShift;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Pengeluaran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayrollService
{
    public function generateSlipNumber(string $periodeBulan): string
    {
        $ym = str_replace('-', '', $periodeBulan);
        $prefix = "SLIP-{$ym}-";

        $query = Payroll::withTrashed()
            ->where('no_slip', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(no_slip) DESC, no_slip DESC');

        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $last = $query->first();
        $next = $last ? ((int) substr($last->no_slip, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil daftar kasbon karyawan pada periode bulan tertentu yang belum dipotong di slip gaji mana pun.
     *
     * @return Collection<int, Pengeluaran>
     */
    public function getUnsettledKasbon(string $employeeId, string $periodeBulan): Collection
    {
        [$year, $month] = explode('-', $periodeBulan);

        return Pengeluaran::where('employee_id', $employeeId)
            ->where('kategori', PengeluaranKategori::Kasbon)
            ->whereNull('payroll_id')
            ->whereYear('waktu', $year)
            ->whereMonth('waktu', $month)
            ->orderBy('waktu', 'asc')
            ->get();
    }

    /**
     * Buat slip gaji baru sekaligus mengunci kasbon yang dipotong agar tidak terpotong dua kali.
     */
    public function createPayroll(
        User $creator,
        Employee $employee,
        string $periodeBulan,
        float $gajiPokok,
        float $tunjangan = 0,
        float $bonus = 0,
        float $potonganLain = 0,
        bool $potongKasbonOtomatis = true,
        bool $bayarLangsung = false,
        ?string $catatan = null
    ): Payroll {
        return DB::transaction(function () use (
            $creator,
            $employee,
            $periodeBulan,
            $gajiPokok,
            $tunjangan,
            $bonus,
            $potonganLain,
            $potongKasbonOtomatis,
            $bayarLangsung,
            $catatan
        ): Payroll {
            $existing = Payroll::where('employee_id', $employee->id)
                ->where('periode_bulan', $periodeBulan)
                ->first();

            if ($existing) {
                throw new InvalidArgumentException("Slip gaji [{$employee->nama}] untuk periode {$periodeBulan} sudah dibuat ({$existing->no_slip}).");
            }

            $kasbons = $potongKasbonOtomatis
                ? $this->getUnsettledKasbon($employee->id, $periodeBulan)
                : collect();

            $potonganKasbon = (float) $kasbons->sum('nominal');
            $totalDiterima = max(0.0, $gajiPokok + $tunjangan + $bonus - $potonganKasbon - $potonganLain);

            $payroll = Payroll::create([
                'no_slip' => $this->generateSlipNumber($periodeBulan),
                'employee_id' => $employee->id,
                'user_id' => $creator->id,
                'periode_bulan' => $periodeBulan,
                'gaji_pokok' => $gajiPokok,
                'tunjangan' => $tunjangan,
                'bonus' => $bonus,
                'potongan_kasbon' => $potonganKasbon,
                'potongan_lain' => $potonganLain,
                'total_diterima' => $totalDiterima,
                'status' => $bayarLangsung ? 'dibayar' : 'draft',
                'tanggal_bayar' => $bayarLangsung ? now()->toDateString() : null,
                'catatan' => filled($catatan) ? trim($catatan) : null,
            ]);

            if ($kasbons->isNotEmpty()) {
                Pengeluaran::whereIn('id', $kasbons->pluck('id'))->update(['payroll_id' => $payroll->id]);
            }

            if ($bayarLangsung) {
                $this->recordSalaryExpense($creator, $employee, $payroll);
            }

            return $payroll->load(['employee.jabatan', 'kasbonItems']);
        });
    }

    /**
     * Tandai slip gaji draft sebagai dibayar dan catat ke arus kas pengeluaran.
     */
    public function markAsPaid(User $payer, string $payrollId): Payroll
    {
        return DB::transaction(function () use ($payer, $payrollId): Payroll {
            /** @var Payroll $payroll */
            $payroll = Payroll::with('employee')->lockForUpdate()->findOrFail($payrollId);
            if ($payroll->status === 'dibayar') {
                return $payroll;
            }

            $payroll->update([
                'status' => 'dibayar',
                'tanggal_bayar' => now()->toDateString(),
            ]);

            if ($payroll->employee) {
                $this->recordSalaryExpense($payer, $payroll->employee, $payroll);
            }

            return $payroll->fresh(['employee.jabatan', 'kasbonItems']);
        });
    }

    protected function recordSalaryExpense(User $user, Employee $employee, Payroll $payroll): void
    {
        if ($payroll->total_diterima <= 0) {
            return;
        }

        $dateCode = now()->format('Ymd');
        $countToday = Pengeluaran::whereDate('waktu', now()->toDateString())->count() + 1;
        $noRef = sprintf('GAJ-%s-%04d', $dateCode, $countToday);
        $activeShift = CashShift::activeShift($user->id)->first();

        Pengeluaran::create([
            'no_referensi' => $noRef,
            'kategori' => PengeluaranKategori::Gaji,
            'nama_karyawan' => $employee->nama,
            'employee_id' => $employee->id,
            'nominal' => $payroll->total_diterima,
            'keterangan' => "Pembayaran Gaji {$payroll->periode_bulan} ({$payroll->no_slip})",
            'user_id' => $user->id,
            'cash_shift_id' => $activeShift?->id,
            'waktu' => now(),
        ]);
    }
}
