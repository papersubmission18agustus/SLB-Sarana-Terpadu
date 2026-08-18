<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'nama_orang_tua_wali' => ['required', 'string', 'max:150'],
            'pendamping_email' => ['nullable', 'email', 'required_without:pendamping_phone'],
            'pendamping_phone' => ['nullable', 'string', 'max:30', 'required_without:pendamping_email'],
        ];
    }
}
