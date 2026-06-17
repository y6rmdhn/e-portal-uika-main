<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAdminRequest extends FormRequest
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
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'in:Mahasiswa,Dosen,Admin'],
            'nidn'     => ['nullable', 'string', 'max:20'],
            'npm'      => ['nullable', 'string', 'max:20'],
            'roles'    => ['required', 'array'],
            'roles.*'  => ['required', 'string', 'exists:m_jabatan,name'],
            'unit_id'  => ['nullable', 'integer', 'exists:m_unit,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
            'role.required'     => 'Role institusi wajib dipilih.',
            'role.in'           => 'Role tidak valid. Pilih: Mahasiswa, Dosen, atau Admin.',
            'roles.required'    => 'Jabatan wajib dipilih.',
            'roles.array'       => 'Format jabatan tidak valid.',
            'roles.*.exists'    => 'Jabatan yang dipilih tidak ditemukan.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => "Validasi gagal : " . $validator->errors()->first(),
        ], 422));
    }
}
