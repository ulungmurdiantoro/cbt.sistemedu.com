<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'admin_notes' => 'required|string|max:1000',
        ];
    }

    public function attributes(): array
    {
        return [
            'admin_notes' => 'alasan penolakan',
        ];
    }
}
