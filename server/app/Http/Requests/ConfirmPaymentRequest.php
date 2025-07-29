<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => 'required|exists:payments,id',
            'payment_method' => 'required|in:stripe,paypal,fake',
            'gateway_data' => 'nullable|array', // utile per future conferme con Stripe
        ];
    }
}
