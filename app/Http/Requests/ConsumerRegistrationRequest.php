<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumerRegistrationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address'      => ['nullable', 'string'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'full_name.required'    => 'Full name is required.',
            'email.email'           => 'Please enter a valid email address.',
            'email.dns'             => 'Please use a valid email domain that can receive mail.',
            'email.unique'          => 'This email is already registered.',
            'phone_number.required' => 'Phone number is required.',
            'password.confirmed'    => 'Passwords do not match.',
            'password.min'          => 'Password must be at least 8 characters.',
        ];
    }
}
