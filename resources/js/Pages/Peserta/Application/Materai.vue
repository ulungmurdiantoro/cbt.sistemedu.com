<template>
    <Head>
        <title>Materai Elektronik - FR.AK.01</title>
    </Head>

    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><Link href="/peserta/dashboard">Dashboard</Link></li>
                    <li class="breadcrumb-item"><Link :href="`/peserta/aplikasi/${application.id}/pakta`">FR.AK.01</Link></li>
                    <li class="breadcrumb-item active">Materai Elektronik</li>
                </ol>
            </nav>
            <h5 class="fw-bold">Materai Elektronik FR.AK.01</h5>
            <p class="text-muted small">
                Materai elektronik resmi (Peruri e-Meterai) dibubuhkan otomatis pada bagian tanda tangan Anda
                di dokumen FR.AK.01, tanpa biaya.
            </p>
        </div>
    </div>

    <div v-if="$page.props.session.success" class="alert alert-success border-0 shadow mb-3">
        {{ $page.props.session.success }}
    </div>

    <div class="card border-0 shadow">
        <div class="card-body p-4 text-center">

            <!-- Sudah selesai (stamped) -->
            <template v-if="application.materai_status === 'stamped'">
                <i class="fa fa-check-circle text-success" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Materai Sudah Dibubuhkan</h6>
                <p class="text-muted small mb-0">
                    Dibubuhkan pada {{ formatDate(application.materai_stamped_at) }}.
                </p>
            </template>

            <!-- Gagal -->
            <template v-else-if="application.materai_status === 'failed'">
                <i class="fa fa-exclamation-triangle text-danger" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Pembubuhan Materai Gagal</h6>
                <p class="text-muted small mb-3">{{ application.materai_failure_reason }}</p>
                <button class="btn btn-primary px-4" :disabled="retrying" @click="retry">
                    <span v-if="retrying"><span class="spinner-border spinner-border-sm me-1"></span>Memproses...</span>
                    <span v-else><i class="fa fa-redo me-1"></i>Coba Lagi</span>
                </button>
            </template>

            <!-- Sedang diproses (default/none/pending_payment) -->
            <template v-else>
                <i class="fa fa-hourglass-half text-warning" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Materai Sedang Diproses</h6>
                <p class="text-muted small mb-0">
                    Pembubuhan materai elektronik dilakukan otomatis di latar belakang. Silakan cek kembali beberapa saat lagi.
                </p>
                <button class="btn btn-sm btn-light border mt-3" @click="reload">
                    <i class="fa fa-sync me-1"></i> Muat Ulang Status
                </button>
            </template>

        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        application: Object,
    },

    setup(props) {
        const retrying = ref(false);

        const retry = () => {
            retrying.value = true;
            router.post(`/peserta/aplikasi/${props.application.id}/materai/retry`, {}, {
                onFinish: () => { retrying.value = false; },
            });
        };

        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        const reload = () => router.reload({ preserveScroll: true });

        return { retrying, retry, formatDate, reload };
    },
}
</script>
