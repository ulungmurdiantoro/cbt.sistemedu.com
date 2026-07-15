<template>
    <Head>
        <title>Pilih Skema - Portal Peserta Sertifikasi</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item active">Pilih Skema</li>
                </ol>
            </nav>
            <h5 class="fw-bold">Pilih Skema Sertifikasi</h5>
        </div>
    </div>

    <div v-if="$page.props.session.error" class="alert alert-danger border-0 shadow mb-3">
        {{ $page.props.session.error }}
    </div>

    <div v-if="skema_list.length === 0" class="alert alert-warning border-0 shadow">
        <i class="fa fa-exclamation-triangle me-1"></i> Tidak ada skema yang aktif saat ini.
    </div>

    <div class="row" v-else>
        <!-- Daftar skema (1 card per skema) -->
        <div class="col-md-7">
            <div v-for="skema in skema_list" :key="skema.classroom_id"
                class="card mb-3 shadow"
                :class="selectedClassroomId === skema.classroom_id
                    ? 'border border-primary border-2'
                    : 'border-0'"
                :style="allEnrolled(skema) ? '' : 'cursor:pointer'"
                @click="selectSkema(skema)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small mb-1" v-if="skema.kode_skema">
                                {{ skema.kode_skema }}
                            </div>
                            <h6 class="fw-bold mb-0">{{ skema.title }}</h6>
                        </div>
                        <span v-if="allEnrolled(skema)" class="badge bg-success">Sudah Daftar</span>
                        <span v-else-if="selectedClassroomId === skema.classroom_id" class="badge bg-primary">Dipilih</span>
                        <span v-else class="badge bg-light text-dark border">Pilih</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel detail & pendaftaran -->
        <div class="col-md-5" v-if="selectedSkema">
            <div class="card border-0 shadow">
                <div class="card-header bg-gray-800 text-white fw-semibold">
                    Konfirmasi Pendaftaran
                </div>
                <div class="card-body">
                    <p class="small fw-semibold mb-1">{{ selectedSkema.title }}</p>
                    <p class="text-muted small mb-3" v-if="selectedSkema.kode_skema">
                        Kode: {{ selectedSkema.kode_skema }}
                    </p>

                    <!-- Info sesi (otomatis dari sesi yang dipilih, read-only) -->
                    <table class="table table-sm mb-4 table-wrap">
                        <tr>
                            <td class="text-muted small">Waktu</td>
                            <td class="small">
                                {{ formatDate(selectedSession.start_time) }}<br>
                                <span class="text-muted">s/d {{ formatDate(selectedSession.end_time) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Konteks Asesmen</td>
                            <td class="small fw-semibold">{{ selectedSession.konteks_asesmen }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Tempat Ujian</td>
                            <td class="small fw-semibold">{{ selectedSession.tempat_ujian }}</td>
                        </tr>
                    </table>

                    <form @submit.prevent="submit">
                        <div class="form-group mb-4">
                            <label class="fw-semibold small">Tujuan Asesmen <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.tujuan_asesmen">
                                <option value="Sertifikasi">Sertifikasi</option>
                                <option value="Sertifikasi Ulang">Sertifikasi Ulang</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gray-800" :disabled="processing">
                                {{ processing ? 'Menyimpan...' : 'Daftar & Lanjutkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        skema_list: Array,
        errors:     Object,
    },

    setup() {
        const selectedClassroomId = ref(null);
        const selectedSkema       = ref(null);
        const selectedSession     = ref(null);
        const processing          = ref(false);

        const form = reactive({
            exam_session_id: null,
            tujuan_asesmen:  'Sertifikasi',
        });

        const allEnrolled = (skema) => skema.sessions.every(s => s.enrolled);

        const selectSkema = (skema) => {
            if (allEnrolled(skema)) return;

            // otomatis pilih sesi pertama yang belum didaftar
            const availableSession = skema.sessions.find(s => !s.enrolled);
            if (!availableSession) return;

            selectedClassroomId.value = skema.classroom_id;
            selectedSkema.value       = skema;
            selectedSession.value     = availableSession;
            form.exam_session_id      = availableSession.id;
        };

        const formatDate = (dt) =>
            new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

        const submit = () => {
            processing.value = true;
            router.post('/peserta/skema', form, {
                onFinish: () => { processing.value = false; },
            });
        };

        return {
            selectedClassroomId, selectedSkema, selectedSession,
            form, processing,
            allEnrolled, selectSkema, formatDate, submit,
        };
    },
}
</script>
