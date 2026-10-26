<template>
    <p class="mb-2">
        Daftar semua sesi ujian, aktif maupun selesai, beserta Pengambil Keputusan yang ditunjuk admin untuk tiap sesi. Klik
        <span class="badge bg-primary">Tinjau</span> untuk membuka halaman peninjauan &amp; finalisasi sesi itu.
    </p>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr><td style="width:170px" class="fw-bolder">Verifikasi PK</td><td>Berapa peserta yang sudah dicentang Pengambil Keputusan (tahap 1). Hanya peserta ini yang bisa Anda verifikasi.</td></tr>
                <tr><td class="fw-bolder">Verifikasi kesesuaian</td><td>Berapa peserta yang sudah Anda centang (tahap 2) atau sudah Final.</td></tr>
                <tr><td class="fw-bolder">Pengambil Keputusan</td><td><span class="text-danger fst-italic">Belum ditunjuk</span> = admin belum menunjuk PK untuk sesi itu; tidak ada yang bisa memverifikasi tahap 1 sampai ditunjuk.</td></tr>
            </tbody>
        </table>
    </div>
    <p class="mb-0 text-muted">
        Kolom <strong>Peserta</strong> hanya menghitung akun peserta yang aktif.
    </p>
</template>
