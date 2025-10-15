<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User can update their own profile
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'old_password' => 'sometimes|string|required_with:new_password',
            'new_password' => 'sometimes|string|min:8|confirmed|required_with:old_password',
            'new_password_confirmation' => 'sometimes|string|required_with:new_password',
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
            'first_name.string' => 'First name must be a valid string.',
            'first_name.max' => 'First name cannot exceed 255 characters.',
            'last_name.string' => 'Last name must be a valid string.',
            'last_name.max' => 'Last name cannot exceed 255 characters.',
            'old_password.required_with' => 'Current password is required when changing password.',
            'new_password.min' => 'New password must be at least 8 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
            'new_password.required_with' => 'New password is required when changing password.',
            'new_password_confirmation.required_with' => 'New password confirmation is required when changing password.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // If user is trying to change password, verify old password
            if ($this->has('old_password') && $this->has('new_password')) {
                $user = $this->user();
                if ($user && $user->password && ! Hash::check($this->old_password, $user->password)) {
                    $validator->errors()->add('old_password', 'The current password is incorrect.');
                }
            }
        });
    }
}
