<template>
    <!--
      Sisa kuota e-meterai Peruri. Dimuat terpisah dari /admin/materai/saldo (cache 5 menit di
      server; "Cek ulang" memaksa cek baru) supaya halaman tidak menunggu Peruri. Hanya membaca.
      compact = kartu ringkasan kecil (halaman TTD AK.01); default = kartu lebar (Log Materai).
    -->
    <div v-if="compact" class="card border-0 shadow text-center py-2 h-100">
        <div v-if="loading && !data" class="fs-4 fw-bold text-muted"><i class="fa fa-spinner fa-spin"></i></div>
        <div v-else-if="error" class="small text-danger px-2 py-1" :title="error">Kuota tidak dapat dicek</div>
        <div v-else-if="data" class="fs-4 fw-bold num" :class="low ? 'text-danger' : 'text-gray-800'">{{ fmtNum(data.saldo) }}</div>
        <div class="small text-muted">
            Sisa Kuota Materai
            <a href="#" class="ms-1 text-muted" title="Cek ulang ke Peruri" @click.prevent="load(true)">
                <i class="fa fa-sync" :class="{ 'fa-spin': loading }"></i>
            </a>
        </div>
    </div>

    <div v-else class="card border-0 shadow">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <i class="fa fa-wallet fa-2x text-primary"></i>
            <div class="me-auto">
                <div class="small text-muted">Sisa kuota e-meterai Peruri</div>
                <div v-if="loading && !data" class="text-muted">
                    <i class="fa fa-spinner fa-spin me-1"></i> Mengecek ke Peruri...
                </div>
                <div v-else-if="error" class="small text-danger">{{ error }}</div>
                <template v-else-if="data">
                    <div class="h3 mb-0 num" :class="{ 'text-danger': low }">
                        {{ fmtNum(data.saldo) }} <span class="h6 text-muted">meterai</span>
                    </div>
                    <div class="small text-muted">
                        Nomor seri belum dibubuhkan: <span class="num">{{ fmtNum(data.notstamp) }}</span>
                        · dicek {{ fmtTime(data.checked_at) }}
                        <span v-if="low" class="text-danger fw-bolder ms-1">— kuota hampir habis</span>
                    </div>
                </template>
            </div>
            <button type="button" class="btn btn-sm btn-gray-800 border-0 shadow" :disabled="loading" @click="load(true)">
                <i class="fa fa-sync me-1" :class="{ 'fa-spin': loading }"></i> Cek ulang
            </button>
        </div>
    </div>
</template>

<script>
// Kuota ≤ angka ini ditandai merah ("hampir habis").
const LOW_QUOTA = 20;

export default {
    name: 'MateraiSaldo',
    props: {
        compact: { type: Boolean, default: false },
    },

    data() {
        return { loading: false, data: null, error: null };
    },

    computed: {
        low() {
            return this.data && this.data.saldo <= LOW_QUOTA;
        },
    },

    mounted() {
        this.load();
    },

    methods: {
        async load(refresh = false) {
            this.loading = true;
            try {
                const res  = await fetch('/admin/materai/saldo' + (refresh ? '?refresh=1' : ''), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const json = await res.json();
                this.error = json.error ?? null;
                this.data  = json.error ? null : json;
            } catch (e) {
                this.error = 'Gagal mengambil saldo dari server.';
            } finally {
                this.loading = false;
            }
        },
        fmtNum(n) {
            return Number(n ?? 0).toLocaleString('id-ID');
        },
        fmtTime(t) {
            return t ? new Date(t).toLocaleString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—';
        },
    },
}
</script>
