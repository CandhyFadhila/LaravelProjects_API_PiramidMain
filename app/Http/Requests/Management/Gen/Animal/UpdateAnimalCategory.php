<?php

namespace App\Http\Requests\Management\Gen\Animal;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateAnimalCategory extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'icon_id' => ['nullable', 'array', 'min:1', 'max:5'],
            'icon_id.*' => ['nullable', 'mimes:jpg,jpeg,png', 'max:10240'],
            'label' => ['required', 'string', 'max:255'],
            'delete_document_ids' => ['nullable', 'array'],
            'delete_document_ids.*' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Nama kategori hewan tidak boleh kosong.',
            'label.string' => 'Nama kategori hewan harus berupa string.',
            'icon_id.required' => 'Icon kategori hewan qurban tidak boleh kosong.',
            'icon_id.min' => 'Minimal icon yang diunggah adalah 1 icon.',
            'icon_id.max' => 'Maksimal icon yang diunggah adalah 5 icon.',
            'icon_id.*.mimes' => 'Icon hanya boleh berupa JPG, JPEG, dan PNG.',
            'icon_id.*.max' => 'Ukuran icon maksimal 10MB.',
            'delete_document_ids.array' => 'Format icon yang dihapus harus berupa array.',
            'delete_document_ids.*.integer' => 'ID icon yang dihapus harus berupa angka.',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $messages = implode(' ', $validator->errors()->all());
        $response = new WithoutDataResource(
            Response::HTTP_BAD_REQUEST,
            'FAILED_VALIDATION',
            'Format Data Tidak Sesuai Ketentuan',
            $messages
        );

        throw new HttpResponseException(response()->json($response, Response::HTTP_BAD_REQUEST));
    }
}
