<template>
    <Head><title>Panduan Asesor</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Asesor</h5>
                        <p class="text-muted mb-0 small">
                            Tugas Anda ada di tiga sisi: memberi nilai (esai &amp; wawancara), menandatangani AK.01 untuk
                            peserta yang ditugaskan, lalu menyatakan rekomendasi Kompeten/Belum Kompeten lewat Laporan Asesmen —
                            sebelum diteruskan ke Pengambil Keputusan untuk keputusan akhir. Verifikasi kelengkapan dokumen
                            persyaratan (FR.APL.01) bukan tugas Anda — itu ditangani admin di alur pendaftaran.
                            Panduan yang sama juga tampil di bagian atas tiap halaman portal.
                        </p>
                    </div>
                </div>

                <div class="accordion" id="panduanAsesor">
                    <div v-for="(section, i) in sections" :key="section.id" class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" :class="{ collapsed: i > 0 }" type="button"
                                data-bs-toggle="collapse" :data-bs-target="`#${section.id}`">
                                <span class="badge bg-primary me-2">{{ i + 1 }}</span> {{ section.title }}
                            </button>
                        </h2>
                        <div :id="section.id" class="accordion-collapse collapse" :class="{ show: i === 0 }" data-bs-parent="#panduanAsesor">
                            <div class="accordion-body small">
                                <component :is="section.component" />
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small text-center mt-4">
                    Selesai sampai di sini, berkas peserta di sesi tersebut siap ditinjau oleh Pengambil Keputusan.
                </p>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAsesor from '../../../Layouts/Asesor.vue';
import guideLayout from '../../../Layouts/guideLayout';
import { Head } from '@inertiajs/vue3';
import { markRaw } from 'vue';
import GuideDashboard from '../../../Components/Guide/Asesor/Dashboard.vue';
import GuideEsai from '../../../Components/Guide/Asesor/Esai.vue';
import GuideWawancara from '../../../Components/Guide/Asesor/Wawancara.vue';
import GuideLaporan from '../../../Components/Guide/Asesor/LaporanAsesmen.vue';
import GuideTtdAk01 from '../../../Components/Guide/Asesor/TtdAk01.vue';
import GuideCv from '../../../Components/Guide/Asesor/Cv.vue';

// Isi tiap bagian = komponen yang sama dengan panduan di atas halaman terkait (PageGuide).
export default {
    layout: guideLayout(LayoutAsesor),
    components: { Head },

    data() {
        return {
            sections: [
                { id: 's1', title: 'Dashboard tugas',                           component: markRaw(GuideDashboard) },
                { id: 's2', title: 'Menilai esai',                              component: markRaw(GuideEsai) },
                { id: 's3', title: 'Menilai wawancara',                         component: markRaw(GuideWawancara) },
                { id: 's4', title: 'Laporan Asesmen (FR.AK.05) & tanda tangan', component: markRaw(GuideLaporan) },
                { id: 's5', title: 'TTD AK.01',                                 component: markRaw(GuideTtdAk01) },
                { id: 's6', title: 'CV Saya',                                   component: markRaw(GuideCv) },
            ],
        };
    },
}
</script>

<style scoped>
/* Bootstrap build ini tidak mendefinisikan --bs-accordion-bg / bg-light / text-dark,
   jadi tiap bagian dijadikan kartu putih solid sendiri-sendiri (senada kartu "Panduan"
   di atas) — tidak ada elemen yang transparan lagi. */
.accordion-item {
    background-color: #ffffff;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06);
    margin-bottom: 14px;
    overflow: hidden;
}
.accordion-item:last-child { margin-bottom: 0; }
.accordion-button {
    background-color: #ffffff;
    color: #1f2937;
    font-weight: 600;
    box-shadow: none;
}
.accordion-button:not(.collapsed) { background-color: #1f2937; color: #fff; }
.accordion-button:not(.collapsed)::after { filter: invert(1) brightness(2); }
.accordion-button:focus { box-shadow: none; }
.accordion-body { background-color: #ffffff; color: #374151; }
</style>
