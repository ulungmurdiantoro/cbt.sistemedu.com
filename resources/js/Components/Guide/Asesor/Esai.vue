<template>
    <ol class="mb-2 ps-3">
        <li>Klik baris peserta (atau tombol <span class="badge bg-primary">Nilai</span>). Panel penilaian terbuka dari kanan
            berisi tiap soal, jawaban peserta, dan kolom nilai.</li>
        <li>Isi nilai <strong>0–100</strong> untuk tiap soal. <strong>Total</strong> = rata-rata nilai soal yang sudah diisi;
            nilai 0 ikut dihitung, soal yang dikosongkan tidak.</li>
        <li>Pindah peserta dengan <span class="badge bg-light text-dark border">Sebelumnya</span> /
            <span class="badge bg-light text-dark border">Berikutnya</span> di bawah panel. Nilai yang sudah diketik tetap ada.</li>
        <li>Klik <span class="badge bg-success">Simpan</span> di panel atau <span class="badge bg-success">Simpan Semua Nilai</span>
            di atas. Keduanya menyimpan nilai <strong>semua</strong> peserta sekaligus.</li>
    </ol>
    <div class="alert alert-warning py-2 mb-2">
        Nilai baru tersimpan setelah tombol Simpan diklik. Kalau halaman ditinggalkan atau di-refresh sebelum itu, ketikan hilang.
    </div>
    <p class="mb-0 text-muted">
        Kolom <strong>Progress</strong> = jumlah soal yang sudah dinilai dari total soal. Badge
        <span class="badge bg-warning text-dark">Remidi</span> menandai peserta yang mengikuti ujian ulang.
    </p>
</template>
