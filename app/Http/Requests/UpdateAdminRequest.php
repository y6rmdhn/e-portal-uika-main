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
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'unique:users,email,' . $this->route('id') . ',public_id'],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8'],
            'roles'     => ['sometimes', 'array'],
            'roles.*'   => ['required', 'string', 'exists:m_jabatan,name'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'location'  => ['nullable', 'string', 'max:255'],
            'about_me'  => ['nullable', 'string'],
            'nidn'      => ['nullable', 'string', 'max:20'],
            'nip'       => ['nullable', 'string', 'max:20'],
            'npm'       => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'in:true,false,1,0'],
            'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'unit_id'   => ['nullable', 'integer', 'exists:m_unit,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Email sudah digunakan.',
            'password.min'       => 'Password minimal 8 karakter.',
            'roles.array'        => 'Format role tidak valid.',
            'roles.*.exists'     => 'Role yang dipilih tidak ditemukan.',
            'image.image'        => 'File harus berupa gambar.',
            'image.max'          => 'Ukuran gambar maksimal 2MB.',
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
