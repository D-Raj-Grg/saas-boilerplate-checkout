<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
            'gateway' => ['required', 'string', Rule::in(['esewa', 'khalti', 'stripe', 'mock'])],
            'guest_first_name' => ['nullable', 'string', 'max:255'],
            'guest_last_name' => ['nullable', 'string', 'max:255'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'guest_password' => ['nullable', 'string', 'min:8'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_slug.required' => 'Plan selection is required',
            'plan_slug.exists' => 'Selected plan does not exist',
            'gateway.required' => 'Payment gateway selection is required',
            'gateway.in' => 'Invalid payment gateway selected',
        ];
    }
}
