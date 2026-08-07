<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address' => ['required', 'array'],
            'shipping_address.full_name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:50'],
            'shipping_address.address_line1' => ['required', 'string', 'max:255'],
            'shipping_address.address_line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.region' => ['nullable', 'string', 'max:255'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['sometimes', 'nullable', 'array'],
            'payment_method' => ['sometimes', 'nullable', 'string', 'in:tmoney,flooz,cash_on_delivery'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'loyalty_points_to_redeem' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_address.required' => 'L\'adresse de livraison est requise.',
            'shipping_address.full_name.required' => 'Le nom complet est requis.',
            'shipping_address.phone.required' => 'Le numéro de téléphone est requis.',
            'shipping_address.address_line1.required' => 'L\'adresse est requise.',
            'shipping_address.city.required' => 'La ville est requise.',
            'shipping_address.country.required' => 'Le pays est requis.',
        ];
    }
}
