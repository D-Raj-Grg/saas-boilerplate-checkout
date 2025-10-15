<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'indisposable'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Please provide your first name',
            'last_name.required' => 'Please provide your last name',
            'email.unique' => 'This email is already registered',
            'email.indisposable' => 'Please use a valid, non-temporary email address to continue.',
            'password.min' => 'Password must be at least 8 characters',
        ];
    }
}
