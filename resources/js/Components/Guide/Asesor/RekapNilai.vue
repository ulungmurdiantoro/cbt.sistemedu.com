<template>
    <p class="mb-2">
        Rekap nilai (hanya untuk dilihat) peserta yang ditugaskan kepada Anda di sesi ini: nilai PG, Esai, Wawancara,
        Nilai Akhir, keputusan, dan rekomendasi K/BK Anda dari Laporan Asesmen.
    </p>
    <ul class="mb-2 ps-3">
        <li>Tulisan <span class="badge bg-warning text-gray-800">belum dinilai</span> menandai esai atau wawancara yang masih menunggu
            nilai dari Anda. Isi lewat tombol <strong>Esai</strong> / <strong>Wawancara</strong> di dashboard.</li>
        <li><strong>Nilai Akhir</strong> = rata-rata berbobot sesuai bobot skema. Selama ada komponen yang belum dinilai,
            nilai akhir dihitung dari komponen yang ada dan masih bisa berubah.</li>
        <li><StatusBadge tone="secondary">Lulus (sementara)</StatusBadge> dihitung otomatis dari nilai kelulusan, belum keputusan
            resmi. Setelah Pengambil Keputusan memfinalisasi, berubah menjadi <StatusBadge tone="success">Lulus</StatusBadge> atau
            <StatusBadge tone="danger">Tidak Lulus</StatusBadge>.</li>
        <li>Kolom <strong>K/BK</strong> = rekomendasi Kompeten / Belum Kompeten Anda di Laporan Asesmen. “—” berarti Anda belum mengisinya.</li>
    </ul>
    <p class="mb-0 text-muted">Membuka halaman ini tidak mengubah nilai apa pun.</p>
</template>

<script>
import StatusBadge from '../../StatusBadge.vue';

export default {
    components: { StatusBadge },
}
</script>
