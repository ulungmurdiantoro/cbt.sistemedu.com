<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status'         => 'required|in:verified,rejected',
            'reviewer_notes' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        return [
            'status'         => 'status verifikasi',
            'reviewer_notes' => 'catatan reviewer',
        ];
    }
}
