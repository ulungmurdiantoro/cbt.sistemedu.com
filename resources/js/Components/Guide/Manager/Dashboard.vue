<template>
    <p class="mb-2">
        Daftar semua sesi ujian, aktif maupun selesai. Semuanya bisa ditinjau tanpa batas waktu. Klik
        <span class="badge bg-primary">Tinjau</span> untuk membuka halaman peninjauan &amp; finalisasi sesi itu.
    </p>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr><td style="width:170px"><span class="badge bg-danger">Belum Ditinjau</span></td><td>Belum ada peserta yang dicentang Verifikasi.</td></tr>
                <tr><td><span class="badge bg-warning text-dark">Sebagian (x/y)</span></td><td>x dari y peserta sudah dicentang Verifikasi.</td></tr>
                <tr><td><span class="badge bg-success">Sudah Ditinjau</span></td><td>Semua peserta sesi sudah dicentang Verifikasi.</td></tr>
            </tbody>
        </table>
    </div>
    <p class="mb-0 text-muted">
        Kolom <strong>Peserta</strong> hanya menghitung akun peserta yang aktif. Sebelum finalisasi pertama, simpan tanda
        tangan Anda di menu <strong>Tanda Tangan Saya</strong>.
    </p>
</template>
