<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['sometimes', 'email'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'role'     => ['sometimes', 'string', 'in:Mahasiswa,Dosen,Admin'],
            'nidn'     => ['nullable', 'string', 'max:10'],
            'npm'      => ['nullable', 'string', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'    => 'Format email tidak valid.',
            'password.min'   => 'Password minimal 8 karakter.',
            'role.in'        => 'Role tidak valid. Pilih: Mahasiswa, Dosen, atau Admin.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'Validasi gagal : ' . $validator->errors()->first(),
        ], 422));
    }
}
