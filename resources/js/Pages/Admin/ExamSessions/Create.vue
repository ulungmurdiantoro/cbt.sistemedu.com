<template>
    <Head>
        <title>Tambah Sesi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/exam_sessions" class="btn btn-md btn-primary border-0 shadow mb-3" type="button">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fas fa-stopwatch"></i> Tambah Sesi</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label>Nama Sesi</label>
                                    <input type="text" class="form-control" placeholder="Masukkan Nama Sesi" v-model="form.title">
                                    <div v-if="errors.title" class="alert alert-danger mt-2">{{ errors.title }}</div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label>Waktu Mulai</label>
                                    <Datepicker v-model="form.start_time" />
                                    <div v-if="errors.start_time" class="alert alert-danger mt-2">{{ errors.start_time }}</div>
                                </div>
                                <div class="col-md-3 mb-4">
                                    <label>Waktu Selesai</label>
                                    <Datepicker v-model="form.end_time" />
                                    <div v-if="errors.end_time" class="alert alert-danger mt-2">{{ errors.end_time }}</div>
                                </div>
                            </div>

                            <!-- Jenis Ujian -->
                            <div class="card border mb-4">
                                <div class="card-header fw-semibold bg-light">Jenis Ujian</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="fw-semibold small">Ujian Pilihan Ganda</label>
                                            <select class="form-select" v-model="form.exam_id_pg">
                                                <option value="">— Tidak ada —</option>
                                                <option v-for="e in examsPG" :key="e.id" :value="e.id">{{ e.title }}</option>
                                            </select>
                                            <div v-if="errors.exam_id_pg" class="alert alert-danger mt-2">{{ errors.exam_id_pg }}</div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="fw-semibold small">Ujian Esai</label>
                                            <select class="form-select" v-model="form.exam_id_esai">
                                                <option value="">— Tidak ada —</option>
                                                <option v-for="e in examsEsai" :key="e.id" :value="e.id">{{ e.title }}</option>
                                            </select>
                                            <div v-if="errors.exam_id_esai" class="alert alert-danger mt-2">{{ errors.exam_id_esai }}</div>
                                        </div>
                                        <div class="col-md-4 mb-3 d-flex align-items-end">
                                            <div class="form-check form-switch mb-2">
                                                <input class="form-check-input" type="checkbox" id="has_wawancara" v-model="form.has_wawancara">
                                                <label class="form-check-label fw-semibold" for="has_wawancara">Ujian Wawancara</label>
                                                <div class="text-muted small">Penilaian oleh asesor dengan kriteria tetap</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <label>Konteks Asesmen</label>
                                    <input type="text" class="form-control" v-model="form.konteks_asesmen" placeholder="Sertifikasi Person">
                                    <div v-if="errors.konteks_asesmen" class="alert alert-danger mt-2">{{ errors.konteks_asesmen }}</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label>Tempat Ujian</label>
                                    <input type="text" class="form-control" v-model="form.tempat_ujian" placeholder="Online (Zoom Meeting)">
                                    <div v-if="errors.tempat_ujian" class="alert alert-danger mt-2">{{ errors.tempat_ujian }}</div>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label>Kode Batch</label>
                                    <input type="text" class="form-control" v-model="form.kode_batch" placeholder="BATCH-2025-01">
                                    <div v-if="errors.kode_batch" class="alert alert-danger mt-2">{{ errors.kode_batch }}</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Simpan</button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import Swal from 'sweetalert2';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {
    layout: LayoutAdmin,
    components: { Head, Link, Datepicker },
    props: {
        errors: Object,
        exams:  Array,
    },

    setup(props) {
        const form = reactive({
            title:           '',
            exam_id_pg:      '',
            exam_id_esai:    '',
            has_wawancara:   false,
            start_time:      '',
            end_time:        '',
            konteks_asesmen: 'Sertifikasi Person',
            tempat_ujian:    'Online (Zoom Meeting)',
            kode_batch:      '',
        });

        const examsPG   = computed(() => props.exams.filter(e => e.type === 'Pilihan Ganda'));
        const examsEsai = computed(() => props.exams.filter(e => e.type === 'Essay' || e.type === 'Essay Migas'));

        const submit = () => {
            router.post('/admin/exam_sessions', {
                title:           form.title,
                exam_id_pg:      form.exam_id_pg      || null,
                exam_id_esai:    form.exam_id_esai    || null,
                has_wawancara:   form.has_wawancara,
                start_time:      form.start_time,
                end_time:        form.end_time,
                konteks_asesmen: form.konteks_asesmen,
                tempat_ujian:    form.tempat_ujian,
                kode_batch:      form.kode_batch,
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Sesi Ujian Berhasil Disimpan!',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
            });
        };

        return { form, examsPG, examsEsai, submit };
    },
}
</script>
