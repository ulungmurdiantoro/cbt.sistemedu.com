<template>
    <p class="mb-2">
        Semua sesi ujian yang pesertanya ditugaskan kepada Anda muncul di sini, dipisah tab
        <span class="badge bg-success">Sesi Aktif</span> dan <span class="badge bg-secondary">Sesi Selesai</span>.
        Kartu di tab Sesi Selesai tetap bisa dibuka. Tiap kartu punya empat tombol:
    </p>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr><td style="width:130px"><span class="badge bg-primary">Esai</span></td><td>Beri nilai jawaban esai peserta di sesi itu.</td></tr>
                <tr><td><span class="badge bg-success">Wawancara</span></td><td>Isi nilai empat kriteria wawancara per peserta.</td></tr>
                <tr><td><span class="badge bg-dark">Laporan</span></td><td>Simpan TTD Anda, isi rekomendasi Kompeten/Belum Kompeten, lengkapi catatan, lalu unduh FR.AK.05.</td></tr>
                <tr><td><span class="badge bg-warning text-dark">TTD AK.01</span></td><td>Tandatangani FR.AK.01 untuk tiap peserta yang ditugaskan kepada Anda.</td></tr>
            </tbody>
        </table>
    </div>
    <p class="mb-0 text-muted">
        Belum punya tanda tangan tersimpan? Buat dulu di halaman <strong>Laporan</strong>. TTD itu dipakai otomatis di
        FR.AK.05 dan saat menandatangani AK.01.
    </p>
</template>
