<?php

namespace App\Http\Requests\Management\Gen\Product;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreAqiqahProduct extends FormRequest
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
            'photo_product_id' => ['required', 'array', 'min:1', 'max:5'],
            'photo_product_id.*' => ['required', 'mimes:jpg,jpeg,png', 'max:10240'],
            'name' => ['required'],
            'description' => ['required'],
            'price' => ['required', 'integer', 'min:1'],
            'portion_count' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_id.required' => 'Hewan aqiqah tidak boleh kosong.',
            'animal_id.exists' => 'Hewan aqiqah tersebut tidak valid.',
            'photo_product_id.required' => 'Dokumen produk aqiqah tidak boleh kosong.',
            'photo_product_id.min' => 'Minimal dokumen yang diunggah adalah 1 dokumen.',
            'photo_product_id.max' => 'Maksimal dokumen yang diunggah adalah 5 dokumen.',
            'photo_product_id.*.mimes' => 'Dokumen hanya boleh berupa JPG, JPEG, dan PNG.',
            'photo_product_id.*.max' => 'Ukuran dokumen maksimal 10MB.',
            'name.required' => 'Nama produk hewan aqiqah tidak boleh kosong.',
            'description.required' => 'Deskripsi produk hewan aqiqah tidak boleh kosong.',
            'price.required' => 'Harga produk hewan aqiqah tidak boleh kosong.',
            'price.integer' => 'Harga produk hewan aqiqah harus berupa angka.',
            'price.min' => 'Harga produk hewan aqiqah minimal 1.',
            'portion_count.required' => 'Berat rata-rata hewan aqiqah tidak boleh kosong.',
            'portion_count.integer' => 'Berat rata-rata hewan aqiqah harus berupa angka.',
            'portion_count.min' => 'Berat rata-rata hewan aqiqah minimal 1 Kg.',
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
