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
                Materai elektronik resmi (Peruri e-Meterai) dibubuhkan pada bagian tanda tangan Anda
                di dokumen FR.AK.01. Biaya materai ditanggung oleh peserta.
            </p>
        </div>
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

            <!-- Sudah dibayar, menunggu pembubuhan -->
            <template v-else-if="application.materai_status === 'paid'">
                <i class="fa fa-hourglass-half text-warning" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3">Pembayaran Berhasil</h6>
                <p class="text-muted small mb-0">
                    Materai sedang diproses untuk dibubuhkan ke dokumen. Silakan cek kembali beberapa saat lagi.
                </p>
            </template>

            <!-- Gagal -->
            <template v-else>
                <template v-if="application.materai_status === 'failed'">
                    <div class="alert alert-danger border-0 text-start mb-4">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Pembayaran sebelumnya tidak berhasil ({{ application.materai_failure_reason }}). Silakan coba lagi.
                    </div>
                </template>

                <i class="fa fa-stamp text-primary" style="font-size:3rem"></i>
                <h6 class="fw-bold mt-3 mb-1">Materai Belum Dibayar</h6>
                <p class="text-muted small">
                    Biaya materai elektronik: <strong>Rp {{ formatRupiah(price) }}</strong>
                </p>

                <button class="btn btn-primary px-4 mt-2" :disabled="paying" @click="pay">
                    <span v-if="paying"><span class="spinner-border spinner-border-sm me-1"></span>Menyiapkan pembayaran...</span>
                    <span v-else><i class="fa fa-credit-card me-1"></i>Bayar Materai</span>
                </button>
            </template>

        </div>
    </div>
</template>

<script>
import LayoutPeserta from '../../../Layouts/Peserta.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export default {
    layout: LayoutPeserta,
    components: { Head, Link },
    props: {
        application:    Object,
        client_key:     String,
        is_production:  Boolean,
        price:          [Number, String],
    },

    setup(props) {
        const paying = ref(false);
        let snapScript = null;

        onMounted(() => {
            snapScript = document.createElement('script');
            snapScript.src = props.is_production
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js';
            snapScript.setAttribute('data-client-key', props.client_key);
            document.head.appendChild(snapScript);
        });

        onUnmounted(() => {
            if (snapScript) document.head.removeChild(snapScript);
        });

        const pay = async () => {
            paying.value = true;
            try {
                const { data } = await axios.post(`/peserta/aplikasi/${props.application.id}/materai/bayar`);

                if (!window.snap) {
                    alert('Snap.js belum siap dimuat, coba muat ulang halaman.');
                    paying.value = false;
                    return;
                }

                window.snap.pay(data.snap_token, {
                    onSuccess: () => {
                        // Status resmi diperbarui lewat webhook Midtrans (async) —
                        // reload halaman ini untuk ambil status terbaru dari server.
                        router.reload({ preserveScroll: true, onFinish: () => { paying.value = false; } });
                    },
                    onPending: () => {
                        router.reload({ preserveScroll: true, onFinish: () => { paying.value = false; } });
                    },
                    onError: () => {
                        paying.value = false;
                        alert('Pembayaran gagal. Silakan coba lagi.');
                    },
                    onClose: () => {
                        paying.value = false;
                    },
                });
            } catch (e) {
                paying.value = false;
                alert(e.response?.data?.message ?? 'Gagal menyiapkan pembayaran, coba lagi.');
            }
        };

        const formatRupiah = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0);
        const formatDate = (dt) => dt
            ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
            : '—';

        return { paying, pay, formatRupiah, formatDate };
    },
}
</script>
