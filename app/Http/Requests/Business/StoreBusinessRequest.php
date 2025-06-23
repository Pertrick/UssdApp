<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
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
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            
            // Business fields
            'business_name' => 'required|string|max:255',
            'business_email' => 'required|email',
            'phone' => 'required|string|max:20',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string|max:500',
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
            'name.required' => 'Your name is required',
            'name.max' => 'Your name cannot exceed 255 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Password confirmation does not match',
            'business_name.required' => 'Business name is required',
            'business_name.max' => 'Business name cannot exceed 255 characters',
            'business_email.required' => 'Business email is required',
            'business_email.email' => 'Please enter a valid business email address',
            'phone.required' => 'Phone number is required',
            'state.required' => 'State is required',
            'city.required' => 'City is required',
            'address.required' => 'Address is required',
        ];
    }
}
