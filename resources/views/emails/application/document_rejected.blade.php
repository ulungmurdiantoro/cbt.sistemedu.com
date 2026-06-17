@component('mail::message')
# Dokumen Permohonan Ditolak

Halo **{{ $application->participant->name }}**,

Salah satu dokumen pada permohonan sertifikasi Anda **ditolak** dan perlu diperbaiki atau diunggah ulang.

@component('mail::panel')
**Dokumen:** {{ $document->requirement->label ?? 'Dokumen persyaratan' }}

**Alasan penolakan:**

{{ $document->reviewer_notes ?? 'Tidak ada keterangan tambahan.' }}
@endcomponent

@component('mail::table')
| Informasi | Detail |
|:----------|:-------|
| Kode Permohonan | {{ $application->code }} |
| Skema Sertifikasi | {{ $application->classroom->title ?? '-' }} |
@endcomponent

Silakan masuk ke portal peserta untuk mengunggah ulang dokumen tersebut.

@component('mail::button', ['url' => config('app.url') . '/peserta/dashboard', 'color' => 'red'])
Perbaiki Dokumen
@endcomponent

Salam,<br>
**Tim LSP Edukasi Global Cendekia**
@endcomponent
