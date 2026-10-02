<template>
    <Head><title>Panduan Pengambil Keputusan</title></Head>

    <div class="container-fluid mb-5 mt-4">
        <div class="row">
            <div class="col-lg-10 col-xl-9 mx-auto">

                <!-- Header -->
                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5 class="mb-1"><i class="fa fa-life-ring me-2 text-primary"></i>Panduan Pengambil Keputusan</h5>
                        <p class="text-muted mb-0 small">
                            Pemegang wewenang terakhir: meninjau kelengkapan dokumen, kelayakan awal, laporan asesmen, dan
                            nilai setiap peserta — lalu memutuskan kelulusan dan menerbitkan nomor SK, SP, dan Sertifikat.
                            Di sistem, portal ini masih beralamat <code>/manager/...</code> — nama teknis lama, perannya tetap Pengambil Keputusan.
                            Panduan yang sama juga tampil di bagian atas tiap halaman portal.
                        </p>
                    </div>
                </div>

                <div class="accordion" id="panduanManager">
                    <div v-for="(section, i) in sections" :key="section.id" class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" :class="{ collapsed: i > 0 }" type="button"
                                data-bs-toggle="collapse" :data-bs-target="`#${section.id}`">
                                <span class="badge bg-primary me-2">{{ i + 1 }}</span> {{ section.title }}
                            </button>
                        </h2>
                        <div :id="section.id" class="accordion-collapse collapse" :class="{ show: i === 0 }" data-bs-parent="#panduanManager">
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
import LayoutManager from '../../../Layouts/Manager.vue';
import guideLayout from '../../../Layouts/guideLayout';
import { Head } from '@inertiajs/vue3';
import { markRaw } from 'vue';
import GuideDashboard from '../../../Components/Guide/Manager/Dashboard.vue';
import GuideTandaTangan from '../../../Components/Guide/Manager/TandaTangan.vue';
import GuideSertifikasi from '../../../Components/Guide/Manager/Sertifikasi.vue';
import GuideMaterai from '../../../Components/Guide/Manager/Materai.vue';

// Isi tiap bagian = komponen yang sama dengan panduan di atas halaman terkait (PageGuide).
// Materai hanya ada di sini — tidak terkait satu halaman tertentu.
export default {
    layout: guideLayout(LayoutManager),
    components: { Head },

    data() {
        return {
            sections: [
                { id: 'm1', title: 'Dashboard sesi',                  component: markRaw(GuideDashboard) },
                { id: 'm2', title: 'Tanda Tangan Saya',               component: markRaw(GuideTandaTangan) },
                { id: 'm3', title: 'Tinjau, verifikasi & finalisasi', component: markRaw(GuideSertifikasi) },
                { id: 'm4', title: 'Materai elektronik peserta',      component: markRaw(GuideMaterai) },
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
