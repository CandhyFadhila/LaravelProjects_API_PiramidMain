<?php

namespace App\Http\Requests\Management\Gen\Product;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateQurbanProduct extends FormRequest
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
            'animal_id' => ['required', 'exists:animals,id'],
            'photo_product_id' => ['nullable', 'array', 'min:1', 'max:5'],
            'photo_product_id.*' => ['nullable', 'mimes:jpg,jpeg,png', 'max:10240'],
            'delete_document_ids' => ['nullable', 'array'],
            'delete_document_ids.*' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required'],
            'price' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'Hewan qurban tidak boleh kosong.',
            'animal_id.exists' => 'Hewan qurban tersebut tidak valid.',
            'photo_product_id.min' => 'Minimal dokumen yang diunggah adalah 1 dokumen.',
            'photo_product_id.max' => 'Maksimal dokumen yang diunggah adalah 5 dokumen.',
            'photo_product_id.*.mimes' => 'Dokumen hanya boleh berupa JPG, JPEG, dan PNG.',
            'photo_product_id.*.max' => 'Ukuran dokumen maksimal 10MB.',
            'delete_document_ids.array' => 'Format dokumen yang dihapus harus berupa array.',
            'delete_document_ids.*.integer' => 'ID dokumen yang dihapus harus berupa angka.',
            'name.required' => 'Nama produk hewan qurban tidak boleh kosong.',
            'name.string' => 'Nama produk hewan qurban harus berupa string.',
            'description.required' => 'Deskripsi produk hewan qurban tidak boleh kosong.',
            'price.required' => 'Harga produk hewan qurban tidak boleh kosong.',
            'price.integer' => 'Harga produk hewan qurban harus berupa angka.',
            'price.min' => 'Harga produk hewan qurban minimal 0.',
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
