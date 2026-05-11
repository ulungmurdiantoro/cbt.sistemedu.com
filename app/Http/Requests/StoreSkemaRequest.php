<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkemaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'exam_session_id' => 'required|exists:exam_sessions,id',
            'tujuan_asesmen'  => 'required|in:Sertifikasi,Sertifikasi Ulang',
        ];
    }

    public function attributes(): array
    {
        return [
            'exam_session_id' => 'sesi ujian',
            'tujuan_asesmen'  => 'tujuan asesmen',
        ];
    }
}
