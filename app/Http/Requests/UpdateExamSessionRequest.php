<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'           => 'required|string|max:255',
            'exam_id_pg'      => 'nullable|exists:exams,id',
            'exam_id_esai'    => 'nullable|exists:exams,id',
            'has_wawancara'   => 'boolean',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'remidi_start_at' => 'nullable',
            'remidi_end_at'   => 'nullable',
            'konteks_asesmen' => 'required|string|max:255',
            'tempat_ujian'    => 'required|string|max:255',
            'kode_batch'      => 'required|string|max:100',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->exam_id_pg && !$this->exam_id_esai && !$this->has_wawancara) {
                $validator->errors()->add('exam_id_pg', 'Pilih minimal satu jenis ujian.');
            }
        });
    }
}
