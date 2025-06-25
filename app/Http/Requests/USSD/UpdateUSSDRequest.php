<?php

namespace App\Http\Requests\USSD;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUSSDRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get the USSD ID from the route parameter
        $ussdId = $this->route('ussd')->id;
        
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'pattern' => 'required|string|max:50|unique:ussds,pattern,' . $ussdId,
            'business_id' => 'required|exists:businesses,id'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if the business belongs to the authenticated user
            $businessId = $this->input('business_id');
            if ($businessId) {
                $business = auth()->user()->businesses()->find($businessId);
                if (!$business) {
                    $validator->errors()->add('business_id', 'The selected business does not belong to you.');
                }
            }
        });
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'USSD name is required.',
            'name.max' => 'USSD name cannot exceed 255 characters.',
            'description.required' => 'USSD description is required.',
            'description.max' => 'USSD description cannot exceed 1000 characters.',
            'pattern.required' => 'USSD pattern is required.',
            'pattern.max' => 'USSD pattern cannot exceed 50 characters.',
            'pattern.unique' => 'This USSD pattern is already in use.',
            'business_id.required' => 'Please select a business.',
            'business_id.exists' => 'The selected business is invalid.',
        ];
    }
} 