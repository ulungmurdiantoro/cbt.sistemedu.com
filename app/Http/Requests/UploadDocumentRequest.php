<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'requirement_id' => 'required|exists:classroom_document_requirements,id',
            'file'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function attributes(): array
    {
        return [
            'requirement_id' => 'persyaratan dokumen',
            'file'           => 'file dokumen',
        ];
    }
}
