@component('mail::message')
# Permohonan Anda Disetujui 🎉

Halo **{{ $application->participant->name }}**,

Selamat! Permohonan sertifikasi Anda telah **disetujui**. Akun ujian Anda sudah siap digunakan.

@component('mail::panel')
**No. Peserta Ujian: {{ $application->student->no_participant ?? '-' }}**

Gunakan nomor ini untuk login di halaman ujian.
@endcomponent

@if($application->student?->requiresTugas())
@component('mail::panel')
**Wajib Upload Tugas Sebelum Ujian**

Sebelum dapat mengerjakan ujian, Anda wajib mengunggah tugas. Tugas ini akan diperiksa asesor pada saat ujian wawancara.
Unggah melalui kotak **Upload Tugas** di Dashboard Peserta, atau setelah login ujian dengan No. Peserta di atas.
@endcomponent
@endif

@component('mail::table')
| Informasi | Detail |
|:----------|:-------|
| Kode Permohonan | {{ $application->code }} |
| Skema Sertifikasi | {{ $application->classroom->title }} |
| Sesi Ujian | {{ $application->examSession->title ?? '-' }} |
| Tempat Ujian | {{ $application->tempat_ujian }} |
@endcomponent

@component('mail::button', ['url' => config('app.url') . '/peserta/dashboard', 'color' => 'green'])
{{ $application->student?->requiresTugas() ? 'Upload Tugas di Dashboard' : 'Lihat Dashboard' }}
@endcomponent

Salam,<br>
**Tim LSP Edukasi Global Cendekia**
@endcomponent
