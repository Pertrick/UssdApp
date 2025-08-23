<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\BusinessType;

class StoreCACRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'cacNumber' => 'required|string|max:255',
            'registrationDate' => 'required|date',
            'businessType' => 'required|string|in:' . implode(',', BusinessType::toArray()),
            'cacDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // max 10MB
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
            'cacNumber.required' => 'CAC registration number is required',
            'registrationDate.required' => 'Registration date is required',
            'registrationDate.date' => 'Please enter a valid date',
            'businessType.required' => 'Business type is required',
            'businessType.in' => 'Please select a valid business type',
            'cacDocument.required' => 'CAC document is required',
            'cacDocument.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file',
            'cacDocument.max' => 'Document size cannot exceed 10MB',
        ];
    }
}
