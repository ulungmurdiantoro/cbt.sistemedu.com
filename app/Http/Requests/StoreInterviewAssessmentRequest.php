<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessments'                               => 'required|array',
            'assessments.*.student_id'                  => 'required|exists:students,id',
            'assessments.*.gaya_wawancara'              => 'nullable|numeric|min:0|max:100',
            'assessments.*.penguasaan_materi'           => 'nullable|numeric|min:0|max:100',
            'assessments.*.kemampuan_hadapi_pertanyaan' => 'nullable|numeric|min:0|max:100',
            'assessments.*.hasil_worksheet'             => 'nullable|numeric|min:0|max:100',
            'assessments.*.catatan'                     => 'nullable|string|max:1000',
        ];
    }
}
