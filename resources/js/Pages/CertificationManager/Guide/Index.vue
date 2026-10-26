<template>
    <Head><title>Panduan Manager Sertifikasi</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Manager Sertifikasi</h5>
                        <p class="text-muted mb-0 small">
                            Tahap terakhir sebelum kelulusan terkunci: memeriksa ulang peserta yang sudah diverifikasi
                            Pengambil Keputusan, lalu memfinalisasi — menerbitkan nomor SK, SP, Sertifikat, dan Keputusan Sertifikasi.
                            Panduan yang sama juga tampil di bagian atas tiap halaman portal.
                        </p>
                    </div>
                </div>

                <div class="accordion" id="panduanCertManager">
                    <div v-for="(section, i) in sections" :key="section.id" class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" :class="{ collapsed: i > 0 }" type="button"
                                data-bs-toggle="collapse" :data-bs-target="`#${section.id}`">
                                <span class="badge bg-primary me-2">{{ i + 1 }}</span> {{ section.title }}
                            </button>
                        </h2>
                        <div :id="section.id" class="accordion-collapse collapse" :class="{ show: i === 0 }" data-bs-parent="#panduanCertManager">
                            <div class="accordion-body small">
                                <component :is="section.component" />
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted small text-center mt-4">
                    Setiap keputusan yang sudah difinalisasi tercatat permanen — perubahan lebih lanjut memerlukan penanganan khusus di luar alur normal ini.
                </p>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutCertificationManager from '../../../Layouts/CertificationManager.vue';
import guideLayout from '../../../Layouts/guideLayout';
import { Head } from '@inertiajs/vue3';
import { markRaw } from 'vue';
import GuideDashboard from '../../../Components/Guide/CertificationManager/Dashboard.vue';
import GuideSertifikasi from '../../../Components/Guide/CertificationManager/Sertifikasi.vue';
import GuideMaterai from '../../../Components/Guide/Manager/Materai.vue';

// Isi tiap bagian = komponen yang sama dengan panduan di atas halaman terkait (PageGuide).
// Materai sama dengan panduan Pengambil Keputusan — tidak terkait satu halaman tertentu.
export default {
    layout: guideLayout(LayoutCertificationManager),
    components: { Head },

    data() {
        return {
            sections: [
                { id: 'cm1', title: 'Dashboard sesi',                  component: markRaw(GuideDashboard) },
                { id: 'cm2', title: 'Tinjau, verifikasi & finalisasi', component: markRaw(GuideSertifikasi) },
                { id: 'cm3', title: 'Materai elektronik peserta',      component: markRaw(GuideMaterai) },
            ],
        };
    },
}
</script>

<style scoped>
/* Sama dengan Pages/Manager/Guide/Index.vue: build Bootstrap ini tidak mendefinisikan
   --bs-accordion-bg, jadi tiap bagian dijadikan kartu putih solid sendiri. */
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
