<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
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
        $organizationUuid = $this->route('organization');

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:organizations,slug,'.$organizationUuid.',uuid',
            'description' => 'sometimes|nullable|string|max:1000',
            'settings' => 'sometimes|nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required',
            'name.max' => 'Organization name must not exceed 255 characters',
            'slug.unique' => 'This organization slug is already taken',
            'description.max' => 'Description must not exceed 1000 characters',
        ];
    }
}
