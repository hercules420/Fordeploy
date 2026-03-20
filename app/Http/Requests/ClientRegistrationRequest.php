<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegistrationRequest extends FormRequest
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
            'owner_name'      => ['required', 'string', 'max:255'],
            'farm_name'       => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email:rfc,dns', 'max:255', 'unique:client_requests,email', 'unique:users,email'],
            'farm_location'   => ['required', 'string'],
            'valid_id'        => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'business_permit' => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'owner_name.required'      => 'Owner name is required.',
            'farm_name.required'       => 'Farm name is required.',
            'email.email'              => 'Please enter a valid email address.',
            'email.dns'                => 'Please use a valid email domain that can receive mail.',
            'email.unique'             => 'This email is already registered.',
            'valid_id.required'        => 'Please upload a valid ID.',
            'valid_id.image'           => 'Valid ID must be an image.',
            'business_permit.required' => 'Business permit is required.',
            'password.confirmed'       => 'Passwords do not match.',
            'password.min'             => 'Password must be at least 8 characters.',
        ];
    }
}
