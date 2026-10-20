<?php

namespace App\Http\Controllers\Business;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IdentifierSequence;
use App\Models\User;
use App\Rules\MalaysianMobile;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        $query = Customer::query()->visibleTo($user);

        if (filled($search)) {
            $digits = Phone::digits($search);
            $query->where(function ($q) use ($search, $digits): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nick_name', 'like', "%{$search}%")
                    ->orWhere('customer_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('kp_no', 'like', "%{$search}%");
                if ($digits !== '') {
                    $q->orWhere('hp_no_normalized', 'like', "%{$digits}%");
                }
            });
        }

        if ($filter === 'prospect') {
            $query->where('category', CustomerCategory::Prospect);
        } elseif ($filter === 'customer') {
            $query->where('category', CustomerCategory::Customer)->where('is_vip', false);
        } elseif ($filter === 'vip') {
            $query->where('is_vip', true);
        } elseif ($filter === 'lead') {
            $query->where('category', CustomerCategory::Lead);
        }

        $customers = $query->latest()->get();

        // Calculate counts for tabs
        $baseQuery = Customer::query()->visibleTo($user);
        $totalCount = (clone $baseQuery)->count();
        $prospectCount = (clone $baseQuery)->where('category', CustomerCategory::Prospect)->count();
        $customerCount = (clone $baseQuery)->where('category', CustomerCategory::Customer)->where('is_vip', false)->count();
        $vipCount = (clone $baseQuery)->where('is_vip', true)->count();

        return view('business.customer', compact(
            'customers',
            'search',
            'filter',
            'totalCount',
            'prospectCount',
            'customerCount',
            'vipCount'
        ));
    }

    public function checkPhone(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;
        $hpNo = trim((string) $request->input('hp_no', ''));
        $customerId = $request->input('customer_id');

        if ($hpNo === '' || $hpNo === '+60' || $hpNo === '60') {
            return response()->json([
                'valid' => false,
                'available' => false,
                'duplicate' => false,
                'message' => null,
            ]);
        }

        // Validate Malaysian mobile format and sufficiency (must start with 601 and have 7-9 digits after)
        if (! Phone::isValidMobile($hpNo)) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'duplicate' => false,
                'message' => 'Nombor telefon tidak mencukupi atau format tidak sah (cth: +60123456789).',
            ]);
        }

        $canonicalHp = Phone::toWhatsApp($hpNo);
        $digitsHp = Phone::digits($hpNo);
        $partnerId = $bo?->partner_id;

        $query = Customer::query();

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        } elseif ($bo) {
            $query->where('business_owner_id', $bo->id);
        }

        if ($customerId) {
            $query->where('id', '!=', $customerId);
        }

        $exists = $query->where(function ($q) use ($hpNo, $canonicalHp, $digitsHp): void {
            $q->where('hp_no', $hpNo);
            if ($canonicalHp) {
                $q->orWhere('hp_no', $canonicalHp)
                    ->orWhere('hp_no_normalized', $canonicalHp);
            }
            if ($digitsHp) {
                $q->orWhere('hp_no_normalized', $digitsHp);
                if (str_starts_with($digitsHp, '60')) {
                    $local = '0'.substr($digitsHp, 2);
                    $q->orWhere('hp_no', $local);
                }
            }
        })->exists();

        return response()->json([
            'valid' => true,
            'available' => ! $exists,
            'duplicate' => $exists,
            'message' => $exists
                ? 'Nombor telefon ini telah didaftarkan untuk pelanggan lain.'
                : 'Nombor telefon sah dan tersedia.',
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $bo = $user->businessOwner;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'nick_name' => ['nullable', 'string', 'max:255'],
            'hp_no' => ['required', 'string', 'max:30', new MalaysianMobile],
            'kp_no' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'category' => ['nullable', 'string'],
            'is_vip' => ['nullable', 'boolean'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'abo_no' => ['nullable', 'string', 'max:50'],
            'dd_name' => ['nullable', 'string', 'max:255'],
            'dd_hp_no' => ['nullable', 'string', 'max:30', new MalaysianMobile],
            'address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'address_line_3' => ['nullable', 'string', 'max:255'],
            'bandar' => ['nullable', 'string', 'max:100'],
            'negeri' => ['nullable', 'string', 'max:100'],
            'poskod' => ['nullable', 'string', 'max:20'],
            'remarks' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
        ]);

        // Duplicate phone check within Partner / BO scope
        $hpNo = $validated['hp_no'];
        $canonicalHp = Phone::toWhatsApp($hpNo);
        $digitsHp = Phone::digits($hpNo);
        $partnerId = $bo?->partner_id;

        $duplicateQuery = Customer::query();
        if ($partnerId) {
            $duplicateQuery->where('partner_id', $partnerId);
        } elseif ($bo) {
            $duplicateQuery->where('business_owner_id', $bo->id);
        }

        $duplicateExists = $duplicateQuery->where(function ($q) use ($hpNo, $canonicalHp, $digitsHp): void {
            $q->where('hp_no', $hpNo);
            if ($canonicalHp) {
                $q->orWhere('hp_no', $canonicalHp)
                    ->orWhere('hp_no_normalized', $canonicalHp);
            }
            if ($digitsHp) {
                $q->orWhere('hp_no_normalized', $digitsHp);
                if (str_starts_with($digitsHp, '60')) {
                    $local = '0'.substr($digitsHp, 2);
                    $q->orWhere('hp_no', $local);
                }
            }
        })->exists();

        if ($duplicateExists) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This phone number is already registered for another customer.',
                    'errors' => [
                        'hp_no' => ['This phone number is already registered for another customer.'],
                    ],
                ], 422);
            }

            throw ValidationException::withMessages([
                'hp_no' => 'This phone number is already registered for another customer.',
            ]);
        }

        $rawCategory = $validated['category'] ?? 'customer';
        $category = match ($rawCategory) {
            'prospect' => CustomerCategory::Prospect,
            'lead' => CustomerCategory::Lead,
            default => CustomerCategory::Customer,
        };

        $isVip = (bool) ($validated['is_vip'] ?? false) || $rawCategory === 'vip';
        $boName = $bo?->applicant_name ?: $user->name;
        $nickName = $validated['contact_person'] ?? $validated['nick_name'] ?? $boName;

        // Generate customer_no
        $customerNo = DB::transaction(function (): string {
            $seq = IdentifierSequence::firstOrCreate(
                ['key' => 'customer_no'],
                ['prefix' => 'CUST', 'next_number' => 1, 'padding' => 6]
            );
            $no = $seq->prefix.str_pad((string) $seq->next_number, $seq->padding, '0', STR_PAD_LEFT);
            $seq->increment('next_number');

            return $no;
        });

        $customer = Customer::create([
            'business_owner_id' => $bo?->id,
            'partner_id' => $bo?->partner_id,
            'customer_no' => $customerNo,
            'category' => $category,
            'is_vip' => $isVip,
            'name' => $validated['name'],
            'nick_name' => $nickName,
            'hp_no' => $validated['hp_no'],
            'kp_no' => $validated['kp_no'] ?? null,
            'email' => $validated['email'] ?? null,
            'age' => $validated['age'] ?? null,
            'marital_status' => $validated['marital_status'] ?? null,
            'occupation' => $validated['occupation'] ?? null,
            'abo_no' => $validated['abo_no'] ?? null,
            'dd_name' => $validated['dd_name'] ?? null,
            'dd_hp_no' => $validated['dd_hp_no'] ?? null,
            'address' => $validated['address'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'address_line_3' => $validated['address_line_3'] ?? null,
            'bandar' => $validated['bandar'] ?? null,
            'negeri' => $validated['negeri'] ?? null,
            'poskod' => $validated['poskod'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'status' => RecordStatus::Active,
            'created_by' => $user->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully.',
                'customer' => $customer,
            ]);
        }

        return redirect()->route('business.customers.index')
            ->with('success', "Customer {$customer->name} ({$customer->customer_no}) registered successfully.");
    }

    public function update(Request $request, Customer $customer): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Scope check
        if (! $user->isAdmin() && $customer->business_owner_id !== $user->businessOwner?->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nick_name' => ['nullable', 'string', 'max:255'],
            'hp_no' => ['required', 'string', 'max:30', new MalaysianMobile],
            'kp_no' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'category' => ['required', 'string'],
            'is_vip' => ['nullable', 'boolean'],
            'abo_no' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ]);

        $category = match ($validated['category']) {
            'prospect' => CustomerCategory::Prospect,
            'lead' => CustomerCategory::Lead,
            default => CustomerCategory::Customer,
        };

        $isVip = (bool) ($validated['is_vip'] ?? false) || $validated['category'] === 'vip';

        $customer->update([
            'name' => $validated['name'],
            'nick_name' => $validated['nick_name'] ?? $customer->nick_name,
            'hp_no' => $validated['hp_no'],
            'kp_no' => $validated['kp_no'] ?? $customer->kp_no,
            'email' => $validated['email'] ?? $customer->email,
            'category' => $category,
            'is_vip' => $isVip,
            'abo_no' => $validated['abo_no'] ?? $customer->abo_no,
            'address' => $validated['address'] ?? $customer->address,
            'remarks' => $validated['remarks'] ?? $customer->remarks,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully.',
                'customer' => $customer,
            ]);
        }

        return redirect()->route('business.customers.index')
            ->with('success', 'Customer details updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isAdmin() && $customer->business_owner_id !== $user->businessOwner?->id) {
            abort(403);
        }

        $customer->delete();

        return redirect()->route('business.customers.index')
            ->with('success', 'Customer removed successfully.');
    }
}
