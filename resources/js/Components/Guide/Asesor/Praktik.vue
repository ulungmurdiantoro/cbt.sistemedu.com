<template>
    <p class="mb-2">
        Halaman ini muncul untuk skema yang menilai <strong>Ujian Praktik / Keterampilan</strong> sebagai komponen sendiri
        (tombol <span class="badge bg-danger">Praktik</span> di kartu sesi), mis. Corporate Legal (Studi Kasus 2 — Surat Kuasa
        Khusus) atau Lifting Engineer (Praktek Calculation dan Praktek Gambar). Untuk skema ini kriteria
        <em>(D) Worksheet</em> di halaman Wawancara tidak dipakai.
    </p>
    <ul class="mb-0 ps-3">
        <li><strong>Daftar peserta</strong>: tombol <i class="fa fa-eye"></i> membuka hasil unggahan peserta (tab baru),
            kolom nilai menampilkan nilai bagian atau progres aspek (mis. 5/8 aspek). Klik <strong>Nilai</strong> untuk menilai.</li>
        <li><strong>Halaman nilai per peserta</strong>: satu kartu per aspek rubrik FR.IA.04 berisi bobot aspek dan kriteria tiap
            rentang skor (90–100 Sangat Baik … 10–29 Sangat Kurang). Klik rentang yang sesuai untuk mengisi nilai tertinggi
            rentang itu, lalu sesuaikan angkanya (0–100). Catatan per aspek boleh diisi.</li>
        <li><strong>Lifting</strong> (Lampiran Rubrik Penilaian Asesor): tiap aspek (B1 Lifting Calculation 45%, B2 Sequence 20%,
            B3 Lifting Method 20%, B4 LOP 15%; Gambar: C Lifting Plan Drawing) berisi butir. Pilih
            <em>Ada &amp; Benar / Ada tapi Salah / Tidak Ada</em> per butir; nilai aspek = jumlah butir (maks. 100). Klik pilihan
            yang sama lagi untuk membatalkan.</li>
        <li><strong>ESG</strong>: tiap Tugas 1–4 (bobot 25%) menampilkan sumber bukti, poin kunci dan <em>gerbang kritis</em>.
            Bila ada gerbang kritis yang gagal, centang "Ada gerbang kritis yang gagal" — nilai tugas itu maksimal 50.</li>
        <li><strong>Nilai bagian</strong> = Σ (nilai aspek × bobot aspek), dihitung otomatis setelah semua aspek terisi. Isian
            yang belum lengkap tetap tersimpan.</li>
        <li>Bar di kanan bawah: <span class="badge bg-success">Simpan</span>, <span class="badge bg-primary">Simpan &amp; Berikutnya</span>
            (urut No. Peserta) dan <span class="badge bg-gray-200 text-gray-800 border">Sebelumnya</span>. Bobot praktik baru
            diterapkan saat Nilai Akhir dihitung.</li>
    </ul>
</template>
