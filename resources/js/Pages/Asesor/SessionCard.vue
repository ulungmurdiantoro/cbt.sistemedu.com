<template>
    <div class="card border-0 shadow h-100" :class="variant === 'completed' ? 'opacity-75' : ''">
        <div class="card-body">
            <!-- Badge status -->
            <div class="mb-2">
                <StatusBadge v-if="variant === 'active'" tone="success">
                    <i class="fa fa-dot-circle me-1"></i>Aktif
                </StatusBadge>
                <StatusBadge v-else tone="neutral">
                    <i class="fa fa-check-circle me-1"></i>Selesai
                </StatusBadge>
            </div>

            <h6 class="fw-bold mb-1">{{ session.title }}</h6>

            <!-- Judul sesi = nama skema dan bisa sama antar batch — batch & jadwal yang membedakan. -->
            <p class="small mb-2">
                <span v-if="session.kode_batch" class="badge bg-gray-200 text-gray-800 border me-1">Batch {{ session.kode_batch }}</span>
                <span class="text-muted"><i class="fa fa-calendar-alt me-1"></i>{{ jadwal }}</span>
            </p>

            <p class="text-muted mb-1 small">
                <i class="fa fa-book me-1"></i>
                <span v-if="session.examPg">
                    {{ session.examPg.title }}
                    <span class="badge bg-info ms-1">{{ session.examPg.type }}</span>
                </span>
                <br v-if="session.examPg && session.examEsai">
                <span v-if="session.examEsai">
                    <i v-if="!session.examPg" class="fa fa-book me-1"></i>
                    {{ session.examEsai.title }}
                    <span class="badge bg-warning text-gray-800 ms-1">{{ session.examEsai.type }}</span>
                </span>
            </p>

            <p class="text-muted mb-3 small">
                <i class="fa fa-layer-group me-1"></i>
                {{ classroomTitle }}
                &nbsp;|&nbsp;
                <i class="fa fa-users me-1"></i>{{ session.student_count }} peserta
            </p>

            <div class="d-flex flex-wrap gap-2">
                <Link :href="'/asesor/penilaian/' + session.id + '/esai'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-primary' : 'btn btn-sm btn-primary border-0 shadow'">
                    <i class="fa fa-pen me-1"></i> Esai
                </Link>
                <Link :href="'/asesor/penilaian/' + session.id + '/wawancara'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-success' : 'btn btn-sm btn-success border-0 shadow'">
                    <i class="fa fa-comments me-1"></i> Wawancara
                </Link>
                <Link :href="'/asesor/penilaian/' + session.id + '/rekap'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-info' : 'btn btn-sm btn-info border-0 shadow'">
                    <i class="fa fa-chart-bar me-1"></i> Rekap Nilai
                </Link>
                <Link :href="'/asesor/penilaian/' + session.id + '/laporan-asesmen'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-dark' : 'btn btn-sm btn-gray-800 border-0 shadow'">
                    <i class="fa fa-file-alt me-1"></i> Laporan
                </Link>
                <Link :href="'/asesor/penilaian/' + session.id + '/ttd-ak01'"
                    :class="variant === 'completed' ? 'btn btn-sm btn-outline-warning' : 'btn btn-sm btn-warning border-0 shadow'">
                    <i class="fa fa-signature me-1"></i> TTD AK.01
                </Link>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';
import StatusBadge from '../../Components/StatusBadge.vue';

export default {
    components: { Link, StatusBadge },
    props: {
        session: { type: Object, required: true },
        variant: { type: String, default: 'active' }, // 'active' | 'completed'
    },
    computed: {
        classroomTitle() {
            const exam = this.session.examPg ?? this.session.examEsai;
            return exam?.classroom?.title ?? '—';
        },
        jadwal() {
            const start = this.parseLocal(this.session.start_time);
            const end   = this.parseLocal(this.session.end_time);
            if (!start || !end) return 'Jadwal belum diatur';

            const tgl = (d) => d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            const jam = (d) => d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

            return start.toDateString() === end.toDateString()
                ? `${tgl(start)} · ${jam(start)}–${jam(end)}`
                : `${tgl(start)}, ${jam(start)} – ${tgl(end)}, ${jam(end)}`;
        },
    },
    methods: {
        // Jam sesi disimpan sebagai jam lokal ("2026-06-18 08:30:00", tanpa zona) — bangun Date
        // lokal langsung, jangan lewat new Date(string) yang beda-beda antar browser.
        parseLocal(value) {
            const m = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
            return m ? new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]) : null;
        },
    },
}
</script>
