<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateTemplateController extends Controller
{
    public function show()
    {
        $template = CertificateTemplate::first();
        return inertia('Admin/CertificateTemplate/Show', ['template' => $template]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'kop'                   => 'nullable|image|max:2048',
            'kop_logo2'             => 'nullable|image|max:2048',
            'ttd'                   => 'nullable|image|max:2048',
            'bg_sertifikat'         => 'nullable|image|max:5120',
            'sk_body'               => 'nullable|string',
            'nama_penandatangan'    => 'nullable|string|max:255',
            'jabatan_penandatangan' => 'nullable|string|max:255',
            'kota'                  => 'nullable|string|max:100',
        ]);

        $template = CertificateTemplate::firstOrNew([]);
        $data = [];

        foreach (['kop', 'ttd', 'bg_sertifikat'] as $field) {
            if ($request->hasFile($field)) {
                $old = $template->{$field . '_path'};
                if ($old) Storage::disk('public')->delete($old);
                $data[$field . '_path'] = $request->file($field)->store('templates', 'public');
            }
        }

        // Logo2 (IAF/KAN) stored as kop_logo2_path
        if ($request->hasFile('kop_logo2')) {
            $old = $template->kop_logo2_path;
            if ($old) Storage::disk('public')->delete($old);
            $data['kop_logo2_path'] = $request->file('kop_logo2')->store('templates', 'public');
        }

        $data['sk_body']               = $request->sk_body;
        $data['nama_penandatangan']    = $request->nama_penandatangan;
        $data['jabatan_penandatangan'] = $request->jabatan_penandatangan;
        $data['kota']                  = $request->kota ?? 'Semarang';

        $template->fill($data)->save();

        return redirect()->back()->with('success', 'Template berhasil disimpan.');
    }
}
