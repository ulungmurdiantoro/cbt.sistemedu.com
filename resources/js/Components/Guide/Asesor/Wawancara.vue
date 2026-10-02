<template>
    <p class="mb-2">Satu baris per peserta. Isi empat kriteria dengan skala <strong>0–100</strong>:</p>
    <div class="table-responsive mb-2">
        <table class="table table-sm table-bordered mb-0">
            <tbody>
                <tr><td style="width:40px" class="fw-bold text-center">A</td><td>Gaya Wawancara</td></tr>
                <tr><td class="fw-bold text-center">B</td><td>Penguasaan Materi</td></tr>
                <tr><td class="fw-bold text-center">C</td><td>Kemampuan Menghadapi Pertanyaan</td></tr>
                <tr><td class="fw-bold text-center">D</td><td>Hasil Pengerjaan Worksheet Ujian Keterampilan</td></tr>
            </tbody>
        </table>
    </div>
    <ul class="mb-0 ps-3">
        <li><strong>Total</strong> = rata-rata kriteria yang terisi. Isi keempatnya; kriteria yang kosong tidak ikut dihitung.
            Bobot wawancara baru diterapkan saat Nilai Akhir dihitung, bukan di tabel ini.</li>
        <li>Kolom <strong>Tugas</strong> (tugas yang diupload peserta sebelum ujian): tombol <i class="fa fa-eye"></i>
            membuka halaman pratinjau di tab baru (PDF, DOCX, JPG dan PNG bisa langsung dilihat), tombol
            <i class="fa fa-download"></i> mengunduhnya.
            Tanda “-” berarti peserta belum mengupload.</li>
        <li><strong>Catatan</strong> boleh dikosongkan.</li>
        <li>Klik <span class="badge bg-success">Simpan Semua Nilai</span> untuk menyimpan seluruh tabel sekaligus.</li>
    </ul>
</template>
