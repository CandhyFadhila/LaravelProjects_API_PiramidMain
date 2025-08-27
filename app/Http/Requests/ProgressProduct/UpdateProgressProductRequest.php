<?php

namespace App\Http\Requests\ProgressProduct;

use App\Http\Resources\Templates\WithoutDataResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateProgressProductRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                'lowercase', // jaga konsistensi dengan nilai enum di DB
                Rule::in([
                    'in_queued',     // Dalam Antrian
                    'in_scheduling', // Dalam Penjadwalan
                    'in_progress',   // Dalam Proses
                    'completed',     // Selesai
                    'delivered',     // Terkirim
                    'in_shipping',   // Dalam Pengiriman
                ]),
            ],
            'photo_progress_id' => ['nullable', 'array', 'min:1', 'max:5'],
            'photo_progress_id.*' => ['nullable', 'mimes:jpg,jpeg,png', 'max:10240'],
            'description' => ['required'],
            'delete_document_ids' => ['nullable', 'array'],
            'delete_document_ids.*' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'         => 'Status tidak boleh kosong.',
            'status.in'               => 'Status yang dipilih tidak valid.',
            'status.lowercase'        => 'Status harus menggunakan huruf kecil.',
            'description.required' => 'Deskripsi tahapan produk tidak boleh kosong.',
            'description.string' => 'Deskripsi tahapan produk harus berupa string.',
            'photo_progress_id.min' => 'Minimal foto yang diunggah adalah 1 foto.',
            'photo_progress_id.max' => 'Maksimal foto yang diunggah adalah 5 foto.',
            'photo_progress_id.*.mimes' => 'Foto hanya boleh berupa JPG, JPEG, dan PNG.',
            'photo_progress_id.*.max' => 'Ukuran foto maksimal 10MB.',
            'delete_document_ids.array' => 'Format foto yang dihapus harus berupa array.',
            'delete_document_ids.*.integer' => 'ID foto yang dihapus harus berupa angka.',
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
