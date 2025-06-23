<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class StoreDirectorRequest extends FormRequest
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
            'directorName' => 'required|string|max:255',
            'directorEmail' => 'required|email|max:255',
            'directorPhone' => 'required|string|max:20',
            'idType' => 'required|string|in:national_id,drivers_license,international_passport',
            'idNumber' => 'required|string|max:255',
            'idDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // max 10MB
            'cacData' => 'required|string', // JSON string from frontend
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
            'directorName.required' => 'Director name is required',
            'directorEmail.required' => 'Director email is required',
            'directorEmail.email' => 'Please enter a valid email address',
            'directorPhone.required' => 'Director phone number is required',
            'idType.required' => 'ID type is required',
            'idType.in' => 'Please select a valid ID type',
            'idNumber.required' => 'ID number is required',
            'idDocument.required' => 'ID document is required',
            'idDocument.mimes' => 'Document must be a PDF, JPG, JPEG, or PNG file',
            'idDocument.max' => 'Document size cannot exceed 10MB',
            'cacData.required' => 'CAC data is required',
        ];
    }
}
