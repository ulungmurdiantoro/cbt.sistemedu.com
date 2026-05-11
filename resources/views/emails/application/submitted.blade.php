@component('mail::message')
# Permohonan Anda Telah Diterima

Halo **{{ $application->participant->name }}**,

Permohonan sertifikasi Anda telah berhasil disubmit dan sedang dalam proses review oleh tim admin.

@component('mail::table')
| Informasi | Detail |
|:----------|:-------|
| Kode Permohonan | {{ $application->code }} |
| Skema Sertifikasi | {{ $application->classroom->title }} |
| Sesi Ujian | {{ $application->examSession->title ?? '-' }} |
| Tempat Ujian | {{ $application->tempat_ujian }} |
| Kode Batch | {{ $application->kode_batch }} |
@endcomponent

Anda akan menerima email notifikasi setelah admin memverifikasi permohonan Anda.

@component('mail::button', ['url' => config('app.url') . '/peserta/dashboard', 'color' => 'green'])
Lihat Dashboard
@endcomponent

Salam,<br>
**Tim LSP Edukasi Global Cendekia**
@endcomponent
