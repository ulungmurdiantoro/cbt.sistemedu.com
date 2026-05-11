@component('mail::message')
# Permohonan Anda Ditolak

Halo **{{ $application->participant->name }}**,

Mohon maaf, permohonan sertifikasi Anda **tidak dapat disetujui** pada saat ini.

@component('mail::panel')
**Alasan penolakan:**

{{ $application->admin_notes ?? 'Tidak ada keterangan tambahan.' }}
@endcomponent

@component('mail::table')
| Informasi | Detail |
|:----------|:-------|
| Kode Permohonan | {{ $application->code }} |
| Skema Sertifikasi | {{ $application->classroom->title }} |
@endcomponent

Anda dapat memperbaiki permohonan dan submit ulang melalui portal peserta.

@component('mail::button', ['url' => config('app.url') . '/peserta/dashboard', 'color' => 'red'])
Revisi Permohonan
@endcomponent

Salam,<br>
**Tim LSP Edukasi Global Cendekia**
@endcomponent
