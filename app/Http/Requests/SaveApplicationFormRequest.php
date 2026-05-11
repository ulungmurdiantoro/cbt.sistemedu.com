<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveApplicationFormRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'nik'                    => 'required|string|max:20',
            'tempat_lahir'           => 'required|string|max:100',
            'tanggal_lahir'          => 'required|date',
            'jenis_kelamin'          => 'required|in:L,P',
            'kebangsaan'             => 'required|string|max:50',
            'alamat_rumah'           => 'required|string',
            'kode_pos_rumah'         => 'nullable|string|max:10',
            'telp_rumah'             => 'nullable|string|max:20',
            'hp'                     => 'required|string|max:20',
            'email_alt'              => 'nullable|email|max:255',
            'kualifikasi_pendidikan' => 'required|in:SD,SMP,SMA,D3,S1,S2,S3',
            'institusi'              => 'required|string|max:255',
            'jabatan'                => 'required|string|max:255',
            'alamat_kantor'          => 'nullable|string',
            'kode_pos_kantor'        => 'nullable|string|max:10',
            'telp_kantor'            => 'nullable|string|max:20',
            'fax_kantor'             => 'nullable|string|max:20',
            'email_kantor'           => 'nullable|email|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                   => 'nama lengkap',
            'nik'                    => 'NIK',
            'tempat_lahir'           => 'tempat lahir',
            'tanggal_lahir'          => 'tanggal lahir',
            'jenis_kelamin'          => 'jenis kelamin',
            'kebangsaan'             => 'kebangsaan',
            'alamat_rumah'           => 'alamat rumah',
            'hp'                     => 'nomor HP',
            'kualifikasi_pendidikan' => 'kualifikasi pendidikan',
            'institusi'              => 'institusi',
            'jabatan'                => 'jabatan',
        ];
    }
}
