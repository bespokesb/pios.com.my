<?php

namespace App\Http\Requests;

use App\Rules\MalaysianMobile;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32', new MalaysianMobile],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'address_line_3' => ['nullable', 'string', 'max:255'],
            'poskod' => ['nullable', 'string', 'max:16'],
            'bandar' => ['nullable', 'string', 'max:255'],
            'negeri' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            // Honeypot: real users never see this field, so it must stay empty.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('pios.public.form.name'),
            'phone' => __('pios.public.form.phone'),
            'quantity' => __('pios.public.form.quantity'),
            'address' => __('pios.public.form.address'),
            'address_line_2' => 'Address line 2',
            'address_line_3' => 'Address line 3',
            'poskod' => 'Postcode',
            'bandar' => 'City',
            'negeri' => 'State',
            'note' => __('pios.public.form.note'),
        ];
    }
}
