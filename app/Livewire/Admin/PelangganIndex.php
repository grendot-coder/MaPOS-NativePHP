<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalePayment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pelanggan & Piutang - MaPOS')]
class PelangganIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'filter')]
    public string $filterPiutang = '';

    // Modal Form Member
    public bool $showFormModal = false;

    public bool $isEditing = false;

    public ?string $editingId = null;

    public string $kode_member = '';

    public string $nama = '';

    public string $telepon = '';

    public string $alamat = '';

    public $limit_kredit = 0;

    // Modal Bayar Piutang / Bon Pelanggan
    public bool $showPaymentModal = false;

    public ?string $payingCustomerId = null;

    public string $selectedSaleId = '';

    public $paymentAmount = 0;

    public string $paymentMethod = 'cash';

    public string $paymentNote = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPiutang(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->editingId = null;
        $this->kode_member = Customer::generateMemberCode();
        $this->nama = '';
        $this->telepon = '';
        $this->alamat = '';
        $this->limit_kredit = 500000;
        $this->showFormModal = true;
    }

    public function openEditModal(string $id): void
    {
        $this->resetValidation();
        $customer = Customer::findOrFail($id);
        $this->isEditing = true;
        $this->editingId = $customer->id;
        $this->kode_member = $customer->kode_member;
        $this->nama = $customer->nama;
        $this->telepon = $customer->telepon ?? '';
        $this->alamat = $customer->alamat ?? '';
        $this->limit_kredit = (float) $customer->limit_kredit;
        $this->showFormModal = true;
    }

    public function saveCustomer(): void
    {
        $uniqueRule = Rule::unique('customers', 'kode_member');
        if ($this->isEditing && $this->editingId) {
            $uniqueRule->ignore($this->editingId);
        }

        $this->validate([
            'kode_member' => ['required', 'string', 'max:50', $uniqueRule],
            'nama' => ['required', 'string', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'limit_kredit' => ['required', 'numeric', 'min:0'],
        ], [
            'kode_member.required' => 'Kode member wajib diisi.',
            'kode_member.unique' => 'Kode member sudah digunakan.',
            'nama.required' => 'Nama pelanggan wajib diisi.',
        ]);

        $data = [
            'kode_member' => trim($this->kode_member),
            'nama' => trim($this->nama),
            'telepon' => filled($this->telepon) ? trim($this->telepon) : null,
            'alamat' => filled($this->alamat) ? trim($this->alamat) : null,
            'limit_kredit' => (float) $this->limit_kredit,
        ];

        if ($this->isEditing && $this->editingId) {
            Customer::findOrFail($this->editingId)->update($data);
            $msg = 'Data pelanggan berhasil diperbarui.';
        } else {
            Customer::create($data);
            $msg = 'Pelanggan baru berhasil didaftarkan.';
        }

        $this->showFormModal = false;

        try {
            Flux::toast(variant: 'success', text: $msg);
        } catch (\Throwable) {
        }
    }

    public function openPaymentModal(string $customerId): void
    {
        $this->resetValidation();
        $this->payingCustomerId = $customerId;
        $unpaidSale = Sale::where('customer_id', $customerId)
            ->where('status_pembayaran', 'piutang')
            ->where('sisa_piutang', '>', 0)
            ->orderBy('waktu', 'asc')
            ->first();

        $this->selectedSaleId = $unpaidSale?->id ?? '';
        $this->paymentAmount = $unpaidSale ? (float) $unpaidSale->sisa_piutang : 0;
        $this->paymentMethod = 'cash';
        $this->paymentNote = '';
        $this->showPaymentModal = true;
    }

    public function updatedSelectedSaleId(): void
    {
        if ($this->selectedSaleId) {
            $sale = Sale::find($this->selectedSaleId);
            if ($sale) {
                $this->paymentAmount = (float) $sale->sisa_piutang;
            }
        }
    }

    public function savePiutangPayment(): void
    {
        $this->validate([
            'selectedSaleId' => ['required', 'exists:sales,id'],
            'paymentAmount' => ['required', 'numeric', 'min:100'],
            'paymentMethod' => ['required', 'in:cash,qris,transfer'],
        ], [
            'selectedSaleId.required' => 'Pilih nota bon yang akan dibayar.',
            'paymentAmount.min' => 'Nominal pembayaran minimal Rp 100.',
        ]);

        try {
            DB::transaction(function (): void {
                /** @var Sale $sale */
                $sale = Sale::lockForUpdate()->findOrFail($this->selectedSaleId);
                /** @var Customer $customer */
                $customer = Customer::lockForUpdate()->findOrFail($this->payingCustomerId);

                $sisa = (float) $sale->sisa_piutang;
                if ($sisa <= 0) {
                    throw new \InvalidArgumentException('Nota ini sudah lunas.');
                }

                $bayar = min($sisa, (float) $this->paymentAmount);

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $customer->id,
                    'user_id' => Auth::id(),
                    'jumlah' => $bayar,
                    'metode' => $this->paymentMethod,
                    'catatan' => filled($this->paymentNote) ? trim($this->paymentNote) : null,
                    'waktu' => now(),
                ]);

                $sisaBaru = max(0.0, $sisa - $bayar);
                $sale->update([
                    'sisa_piutang' => $sisaBaru,
                    'status_pembayaran' => $sisaBaru <= 0 ? 'lunas' : 'piutang',
                    'bayar' => (float) $sale->bayar + $bayar,
                ]);

                $customer->piutang_berjalan = max(0.0, (float) $customer->piutang_berjalan - $bayar);
                $customer->save();
            });

            $this->showPaymentModal = false;

            try {
                Flux::toast(variant: 'success', text: 'Pembayaran piutang berhasil dicatat.');
            } catch (\Throwable) {
            }
        } catch (\Throwable $e) {
            $this->addError('paymentAmount', $e->getMessage());
        }
    }

    public function delete(string $id): void
    {
        $customer = Customer::findOrFail($id);
        if ($customer->piutang_berjalan > 0) {
            try {
                Flux::toast(variant: 'danger', text: "Pelanggan [{$customer->nama}] masih memiliki piutang berjalan Rp ".number_format($customer->piutang_berjalan, 0, ',', '.'));
            } catch (\Throwable) {
            }

            return;
        }

        $customer->delete();

        try {
            Flux::toast(variant: 'success', text: "Pelanggan [{$customer->nama}] berhasil dihapus.");
        } catch (\Throwable) {
        }
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('nama', 'like', "%{$s}%")
                        ->orWhere('kode_member', 'like', "%{$s}%")
                        ->orWhere('telepon', 'like', "%{$s}%");
                });
            })
            ->when($this->filterPiutang === 'piutang', fn ($q) => $q->where('piutang_berjalan', '>', 0))
            ->orderBy('nama')
            ->paginate(15);

        $totalMember = Customer::count();
        $totalPiutang = (float) Customer::sum('piutang_berjalan');
        $totalPoinAktif = (int) Customer::sum('poin');

        $payingCustomer = null;
        $unpaidSales = collect();
        if ($this->showPaymentModal && $this->payingCustomerId) {
            $payingCustomer = Customer::find($this->payingCustomerId);
            $unpaidSales = Sale::where('customer_id', $this->payingCustomerId)
                ->where('status_pembayaran', 'piutang')
                ->where('sisa_piutang', '>', 0)
                ->orderBy('waktu', 'asc')
                ->get();
        }

        return view('livewire.admin.pelanggan-index', [
            'customers' => $customers,
            'totalMember' => $totalMember,
            'totalPiutang' => $totalPiutang,
            'totalPoinAktif' => $totalPoinAktif,
            'payingCustomer' => $payingCustomer,
            'unpaidSales' => $unpaidSales,
        ]);
    }
}
