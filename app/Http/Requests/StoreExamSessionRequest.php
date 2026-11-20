<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamSessionRequest extends FormRequest
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
            // Ujian Praktik/Keterampilan (panduan + template). Kosong = peserta hanya Upload Tugas.
            'exam_id_praktik' => ['nullable', \Illuminate\Validation\Rule::exists('exams', 'id')->where('type', \App\Models\Exam::TYPE_PRAKTIK)],
            // Bagian 2 (opsional, mis. Lifting: Gambar setelah Calculation) — waktu & unggahan sendiri.
            'exam_id_praktik_2' => ['nullable', 'different:exam_id_praktik', \Illuminate\Validation\Rule::exists('exams', 'id')->where('type', \App\Models\Exam::TYPE_PRAKTIK)],
            'has_wawancara'   => 'boolean',
            'verifikasi_tuk'  => 'boolean',
            'start_time'      => 'required',
            'end_time'        => 'required',
            'konteks_asesmen' => 'required|string|max:255',
            'tempat_ujian'    => 'required|string|max:255',
            'kode_batch'      => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'exam_id_praktik_2.different' => 'Ujian Praktik/Keterampilan bagian 2 harus berbeda dengan bagian 1.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->exam_id_pg && !$this->exam_id_esai && !$this->exam_id_praktik && !$this->has_wawancara) {
                $validator->errors()->add('exam_id_pg', 'Pilih minimal satu jenis ujian.');
            }
            if ($this->exam_id_praktik_2 && !$this->exam_id_praktik) {
                $validator->errors()->add('exam_id_praktik_2', 'Pilih Ujian Praktik/Keterampilan bagian 1 terlebih dahulu.');
            }
        });
    }
}
