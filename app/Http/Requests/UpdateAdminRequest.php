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
            'role'     => ['sometimes', 'string', 'exists:m_jabatan,name'],
            'nidn'     => ['nullable', 'string', 'max:20'],
            'npm'      => ['nullable', 'string', 'max:20'],
            'roles'    => ['sometimes', 'array'],
            'roles.*'  => ['required', 'string', 'exists:m_jabatan,name'],
            'unit_id'  => ['nullable', 'integer', 'exists:m_unit,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.email'    => 'Format email tidak valid.',
            'password.min'   => 'Password minimal 8 karakter.',
            'role.exists'    => 'Role/Jabatan yang dipilih tidak ditemukan.',
            'roles.array'    => 'Format jabatan tidak valid.',
            'roles.*.exists' => 'Jabatan yang dipilih tidak ditemukan.',
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
