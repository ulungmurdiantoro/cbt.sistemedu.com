<template>
    <!--
      Navigasi antar-halaman satu sesi ujian. Tiap tab tetap halaman tersendiri
      (Peserta & Asesor, Permohonan, Verifikasi TUK, TTD AK.01); komponen ini hanya
      menyambungkannya supaya admin tidak memilih sesi yang sama berulang kali dari
      menu yang berbeda. Rekap Hasil sengaja tidak jadi tab — tetap lewat menu Hasil Penilaian.
    -->
    <div class="card border-0 shadow mb-4">
        <div class="card-body pb-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <i class="fa fa-stopwatch text-gray-500"></i>
                <strong>{{ session.title }}</strong>
                <span v-if="session.kode_batch" class="text-muted small">Batch {{ session.kode_batch }}</span>
            </div>
            <ul class="nav session-nav flex-nowrap">
                <li v-for="tab in tabs" :key="tab.key" class="nav-item">
                    <Link :href="tab.href" class="nav-link" :class="{ active: tab.key === active }">
                        <i :class="tab.icon" class="me-1"></i>{{ tab.label }}
                    </Link>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    name: 'SessionNav',
    components: { Link },
    props: {
        // Sesi ujian: minimal { id, title, kode_batch, verifikasi_tuk }
        session: {
            type: Object,
            required: true,
        },
        // peserta | permohonan | tuk | dokumen
        active: {
            type: String,
            default: '',
        },
    },
    computed: {
        tabs() {
            const id = this.session.id;

            return [
                { key: 'peserta',    label: 'Peserta & Asesor', icon: 'fa fa-users',           href: `/admin/exam_sessions/${id}` },
                { key: 'permohonan', label: 'Permohonan',       icon: 'fa fa-file-alt',        href: `/admin/applications?exam_session_id=${id}` },
                // Hanya untuk sesi yang saklar Verifikasi TUK-nya aktif
                this.session.verifikasi_tuk
                    ? { key: 'tuk',  label: 'Verifikasi TUK',   icon: 'fa fa-clipboard-check', href: `/admin/penilaian/${id}/verifikasi-tuk` }
                    : null,
                { key: 'dokumen',    label: 'TTD AK.01',        icon: 'fa fa-signature',       href: `/admin/penilaian/${id}/dokumen` },
            ].filter(Boolean);
        },
    },
}
</script>

<style scoped>
.session-nav {
    overflow-x: auto;
    border-bottom: 2px solid #E5E7EB;
}
.session-nav .nav-link {
    color: #6B7280;
    font-weight: 600;
    white-space: nowrap;
    padding: 0.6rem 1rem;
    margin-bottom: -2px;
    border-bottom: 3px solid transparent;
}
.session-nav .nav-link:hover {
    color: #1F2937;
}
.session-nav .nav-link.active {
    color: #1c4ea3;
    border-bottom-color: #2361ce;
}
</style>
