<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\User|null $user */
            $user = $this->user();

            if (! $user) {
                return;
            }

            // If plan_id is not provided or is the free plan, check the limit
            $planId = $this->input('plan_id');
            $isFreeOrDefault = false;

            if (! $planId) {
                // No plan specified, will default to free
                $isFreeOrDefault = true;
            } else {
                $plan = \App\Models\Plan::find($planId);
                if ($plan instanceof \App\Models\Plan && $plan->slug === 'free') {
                    $isFreeOrDefault = true;
                }
            }

            if ($isFreeOrDefault) {
                // Check if user already has a free organization
                $freeOrgCount = $user->ownedOrganizations()
                    ->whereHas('plans', function ($query) {
                        $query->where('slug', 'free')
                            ->where('status', 'active')
                            ->where('is_revoked', false);
                    })
                    ->count();

                if ($freeOrgCount >= 1) {
                    $validator->errors()->add(
                        'plan_id',
                        'You already have a free organization. Please upgrade your existing organization or choose a paid plan.'
                    );
                }
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:organizations,slug',
            'description' => 'nullable|string|max:1000',
            'workspace_name' => 'nullable|string|max:255',
            'workspace_slug' => 'nullable|string|max:255',
            'workspace_description' => 'nullable|string|max:1000',
            'plan_id' => 'nullable|exists:plans,id',
            'settings' => 'nullable|array',
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
            'workspace_name.max' => 'Workspace name must not exceed 255 characters',
            'workspace_description.max' => 'Workspace description must not exceed 1000 characters',
            'plan_id.exists' => 'Selected plan does not exist',
        ];
    }
}
