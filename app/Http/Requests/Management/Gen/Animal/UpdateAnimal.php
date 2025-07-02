<?php

namespace App\Http\Requests\Management\Gen\Animal;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateAnimal extends FormRequest
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
            'animal_category_id' => ['required', 'exists:animal_categories,id'],
            'animal_breed_id' => ['required', 'exists:animal_breeds,id'],
            'average_weight' => ['required', 'integer', 'min:0'],
            'birth_date' => ['required', 'date'],
            'stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'animal_category_id.required' => 'Kategori hewan tidak boleh kosong.',
            'animal_category_id.exists' => 'Kategori hewan tersebut tidak valid.',
            'animal_breed_id.required' => 'Jenis ras hewan tidak boleh kosong.',
            'animal_breed_id.exists' => 'Jenis ras hewan tersebut tidak valid.',
            'average_weight.required' => 'Berat rata-rata hewan qurban tidak boleh kosong.',
            'average_weight.integer' => 'Berat rata-rata hewan qurban harus berupa angka.',
            'average_weight.min' => 'Berat rata-rata hewan qurban minimal 0 Kg.',
            'birth_date.required' => 'Tanggal lahir hewan qurban tidak boleh kosong.',
            'birth_date.date' => 'Tanggal lahir hewan qurban harus berupa tanggal valid.',
            'stock.required' => 'Stok hewan tidak boleh kosong.',
            'stock.integer' => 'Stok hewan harus berupa angka.',
            'stock.min' => 'Stok hewan minimal 0.',
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
