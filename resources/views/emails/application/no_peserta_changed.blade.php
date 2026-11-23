@component('mail::message')
# Perubahan No. Peserta Ujian

Halo **{{ $application->participant->name }}**,

No. Peserta ujian Anda **telah diperbarui** karena penyesuaian kode batch sesi. Mulai sekarang, gunakan nomor baru di bawah
ini untuk login di halaman ujian. Nomor lama sudah tidak dapat dipakai.

@component('mail::panel')
**No. Peserta baru: {{ $newNumber }}**

No. Peserta lama: {{ $oldNumber }}
@endcomponent

@component('mail::table')
| Informasi | Detail |
|:----------|:-------|
| Kode Permohonan | {{ $application->code }} |
| Skema Sertifikasi | {{ $application->classroom->title }} |
| Sesi Ujian | {{ $application->examSession->title ?? '-' }} |
| Tempat Ujian | {{ $application->tempat_ujian }} |
@endcomponent

Akun, permohonan, dan pekerjaan ujian Anda yang sudah tersimpan tidak berubah — hanya nomor login ujiannya.

@component('mail::button', ['url' => config('app.url'), 'color' => 'green'])
Login Ujian
@endcomponent

Salam,<br>
**Tim LSP Edukasi Global Cendekia**
@endcomponent
