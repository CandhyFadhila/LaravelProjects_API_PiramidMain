<?php

namespace App\Http\Requests\Management\Gen\Product;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreSadaqahProduct extends FormRequest
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
            'animal_id' => ['nullable', 'array', 'min:1'],
            'animal_id.*' => ['integer', 'exists:animals,id'],
            'photo_product_id' => ['required', 'array', 'min:1'],
            'photo_product_id.*' => ['required', 'mimes:jpg,jpeg,png', 'max:10240'],
            'name' => ['required', 'string', 'max:255', 'unique:sadaqah_products,name'],
            'description' => ['required'],
            'price' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.array' => 'Produk hewan sadaqah harus berupa array.',
            'animal_id.min' => 'Minimal produk hewan sadaqah adalah 1 produk.',
            'animal_id.*.integer' => 'ID produk hewan sadaqah harus berupa angka.',
            'animal_id.*.exists' => 'ID produk hewan sadaqah tidak valid.',
            'photo_product_id.required' => 'Dokumen produk sadaqah tidak boleh kosong.',
            'photo_product_id.min' => 'Minimal dokumen yang diunggah adalah 1 dokumen.',
            'photo_product_id.*.mimes' => 'Dokumen hanya boleh berupa JPG, JPEG, dan PNG.',
            'photo_product_id.*.max' => 'Ukuran dokumen maksimal 10MB.',
            'name.required' => 'Nama produk sadaqah tidak boleh kosong.',
            'name.string' => 'Nama produk sadaqah harus berupa string.',
            'name.unique' => 'Nama produk sadaqah tersebut sudah pernah dibuat.',
            'description.required' => 'Deskripsi produk sadaqah tidak boleh kosong.',
            'price.required' => 'Harga produk sadaqah tidak boleh kosong.',
            'price.integer' => 'Harga produk sadaqah harus berupa angka.',
            'price.min' => 'Harga produk sadaqah minimal 1.',
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
