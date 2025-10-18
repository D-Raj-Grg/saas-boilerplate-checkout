<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // eSewa v1 parameters (legacy)
            'oid' => ['sometimes', 'string'], // eSewa order ID
            'refId' => ['sometimes', 'string'], // eSewa reference ID
            'amt' => ['sometimes', 'numeric'], // eSewa amount

            // eSewa v2 parameters
            'data' => ['sometimes', 'string'], // eSewa v2 base64 encoded data
            'transaction_uuid' => ['sometimes', 'string'], // eSewa v2 transaction UUID
            'total_amount' => ['sometimes', 'numeric'], // eSewa v2 total amount

            // Khalti parameters
            'pidx' => ['sometimes', 'string'], // Khalti payment index
            'transaction_id' => ['sometimes', 'string'], // Khalti transaction ID
            'status' => ['sometimes', 'string'], // Khalti status

            // Generic parameters
            'q' => ['sometimes', 'string'], // Query parameter (su/fu for success/failure)
            'payment_uuid' => ['sometimes', 'string'], // Our payment UUID
        ];
    }
}
