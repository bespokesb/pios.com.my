<?php

namespace App\Http\Controllers;

use App\Enums\CustomerCategory;
use App\Enums\RecordStatus;
use App\Models\Customer;
use App\Models\FeedbackSubmission;
use App\Models\Partner;
use App\Services\IdentifierGenerator;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FeedbackController extends Controller
{
    public function show(): View
    {
        return view('auth.feedback');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'hp_no' => ['required', 'string', 'max:50'],
            'partner_id_input' => ['nullable', 'string', 'max:255'],
            'q1_income_growth' => ['required', 'in:yes,no,1,0'],
            'q2_seeking_way' => ['required', 'in:yes,no,1,0'],
            'q3_interested_tools' => ['required', 'in:yes,no,1,0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ], [
            'full_name.required' => 'Sila masukkan nama penuh anda.',
            'hp_no.required' => 'Sila masukkan nombor telefon (HP No) anda.',
            'q1_income_growth.required' => 'Sila pilih Yes atau No untuk Soalan 1.',
            'q2_seeking_way.required' => 'Sila pilih Yes atau No untuk Soalan 2.',
            'q3_interested_tools.required' => 'Sila pilih Yes atau No untuk Soalan 3.',
        ]);

        $partnerInput = trim((string) ($validated['partner_id_input'] ?? ''));
        $partner = null;

        if ($partnerInput !== '') {
            $partner = Partner::query()
                ->whereRaw('UPPER(partner_no) = ?', [strtoupper($partnerInput)])
                ->first();
        }

        $partnerId = $partner?->id;
        $assignedToOwner = $partner === null;

        $q1 = in_array($validated['q1_income_growth'], ['yes', '1'], true);
        $q2 = in_array($validated['q2_seeking_way'], ['yes', '1'], true);
        $q3 = in_array($validated['q3_interested_tools'], ['yes', '1'], true);

        $fullName = trim($validated['full_name']);
        $hpNo = trim($validated['hp_no']);
        $normalizedPhone = Phone::toWhatsApp($hpNo) ?: preg_replace('/[^0-9]/', '', $hpNo);

        $remarkNote = trim(implode("\n", array_filter([
            'Sumber: Questionnaire & Feedback',
            'Q1 (Pertumbuhan Pendapatan): '.($q1 ? 'Yes' : 'No'),
            'Q2 (Mencari Cara Terbaik): '.($q2 ? 'Yes' : 'No'),
            'Q3 (Minat Alatan Automatik): '.($q3 ? 'Yes' : 'No'),
            $partnerInput !== '' && $assignedToOwner ? "Rujukan/Nama Jurujual: {$partnerInput} (Owner)" : null,
            filled($validated['remarks'] ?? null) ? 'Komen: '.trim((string) $validated['remarks']) : null,
        ])));

        $existingCustomer = null;
        if (! empty($normalizedPhone)) {
            $customerQuery = Customer::query()
                ->whereNull('business_owner_id')
                ->where('hp_no_normalized', $normalizedPhone);

            if ($partnerId !== null) {
                $customerQuery->where('partner_id', $partnerId);
            } else {
                $customerQuery->whereNull('partner_id');
            }

            $existingCustomer = $customerQuery->first();
        }

        if ($existingCustomer) {
            $existingCustomer->update([
                'name' => $fullName,
                'category' => CustomerCategory::Prospect,
                'remarks' => trim(($existingCustomer->remarks ? $existingCustomer->remarks."\n---\n" : '').$remarkNote),
            ]);
            $customer = $existingCustomer;
        } else {
            $customer = Customer::create([
                'partner_id' => $partnerId,
                'business_owner_id' => null,
                'customer_no' => app(IdentifierGenerator::class)->generate('customer', Customer::class, 'customer_no'),
                'category' => CustomerCategory::Prospect,
                'is_vip' => false,
                'name' => $fullName,
                'hp_no' => $hpNo,
                'status' => RecordStatus::Active,
                'remarks' => $remarkNote,
            ]);
        }

        FeedbackSubmission::create([
            'customer_id' => $customer->id,
            'partner_id' => $partnerId,
            'full_name' => $fullName,
            'hp_no' => $hpNo,
            'partner_id_input' => $partnerInput !== '' ? $partnerInput : null,
            'q1_income_growth' => $q1,
            'q2_seeking_way' => $q2,
            'q3_interested_tools' => $q3,
            'remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
            'assigned_to_owner' => $assignedToOwner,
        ]);

        return redirect()
            ->route('feedback.show')
            ->with('success', 'Terima kasih! Maklum balas anda telah berjaya direkodkan sebagai Prospek.');
    }

    public function slide(): View|BinaryFileResponse
    {
        $publicPdf = public_path('assets/docs/bge-slide.pdf');
        if (file_exists($publicPdf)) {
            return response()->file($publicPdf, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        $storagePdf = storage_path('app/public/bge-slide.pdf');
        if (file_exists($storagePdf)) {
            return response()->file($storagePdf, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return view('auth.feedback-slide');
    }
}
