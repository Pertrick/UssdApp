<?php

namespace App\Http\Requests\USSD;

use Illuminate\Foundation\Http\FormRequest;

class StoreUSSDRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize target_ussd_id: empty string or "null" string -> null
        $allocations = $this->input('allocations', []);
        if (is_array($allocations)) {
            foreach ($allocations as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $tid = $row['target_ussd_id'] ?? null;
                if ($tid === '' || $tid === 'null') {
                    $allocations[$i]['target_ussd_id'] = null;
                }
            }
            $this->merge(['allocations' => $allocations]);
        }
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
            'description' => 'required|string|max:1000',
            'pattern' => 'required|string|max:50|unique:ussds,pattern',
            'is_shared_gateway' => 'boolean',
            'allocations' => 'nullable|array',
            'allocations.*.option_value' => 'nullable|string|max:20',
            'allocations.*.target_ussd_id' => 'nullable|exists:ussds,id',
            'allocations.*.label' => 'nullable|string|max:100',
        ];
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
