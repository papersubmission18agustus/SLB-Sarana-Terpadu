<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['nullable', 'string'],
            'username' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $token = trim((string) $this->input('token'));
            $username = trim((string) $this->input('username'));
            $email = trim((string) $this->input('email'));

            if ($token === '' && $username === '' && $email === '') {
                $validator->errors()->add('token', 'Token atau username/email wajib diisi.');
            }
        });
    }
}
