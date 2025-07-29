<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autenticazione già gestita via middleware
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:stripe,paypal,fake',
            'saved_method_id' => 'nullable|exists:payment_methods,id',
            'save_method' => 'boolean',
            'gateway_data' => 'nullable|array',
        ];
    }
}
