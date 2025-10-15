<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class GetCheckoutUrlRequest extends FormRequest
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
            'plan_slug' => 'required|string|max:255',
            'source' => 'sometimes|string|max:255',
            'coupon' => 'sometimes|nullable|string|max:255',
            'aff' => 'sometimes|nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_slug.required' => 'Plan slug is required',
            'plan_slug.string' => 'Plan slug must be a string',
        ];
    }
}
