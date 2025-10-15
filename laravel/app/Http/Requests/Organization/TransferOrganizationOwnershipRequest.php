<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class TransferOrganizationOwnershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_owner_id' => 'required|exists:users,id',
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
            'new_owner_id.required' => 'New owner ID is required.',
            'new_owner_id.exists' => 'The specified user does not exist.',
        ];
    }
}
