<template>
    <Head><title>Penugasan Asesor</title></Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5><i class="fa fa-user-tie me-2"></i>Penugasan Asesor</h5>
                        <hr>
                        <p class="text-muted mb-0">Pilih sesi ujian untuk mengatur penugasan asesor ke peserta.</p>
                    </div>
                </div>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:5%">No</th>
                                        <th>Sesi Ujian</th>
                                        <th>Ujian</th>
                                        <th>Skema</th>
                                        <th>Tipe</th>
                                        <th class="text-center" style="width:12%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="exam_sessions.length === 0">
                                        <td colspan="6" class="text-center text-muted">Belum ada sesi ujian.</td>
                                    </tr>
                                    <tr v-for="(session, i) in exam_sessions" :key="session.id">
                                        <td>{{ i + 1 }}</td>
                                        <td>{{ session.title }}</td>
                                        <td>
                                            <div v-if="session.examPg" class="small">{{ session.examPg.title }}</div>
                                            <div v-if="session.examEsai" class="small">{{ session.examEsai.title }}</div>
                                            <div v-if="!session.examPg && !session.examEsai" class="text-muted small">—</div>
                                        </td>
                                        <td>{{ (session.examPg ?? session.examEsai)?.classroom?.title ?? '—' }}</td>
                                        <td>
                                            <span v-if="session.examPg" class="badge bg-info me-1">{{ session.examPg.type }}</span>
                                            <span v-if="session.examEsai" class="badge bg-warning text-dark me-1">{{ session.examEsai.type }}</span>
                                            <span v-if="session.has_wawancara" class="badge bg-secondary">Wawancara</span>
                                        </td>
                                        <td class="text-center">
                                            <Link :href="`/admin/penilaian/${session.id}`"
                                                class="btn btn-sm btn-primary border-0 shadow">
                                                <i class="fa fa-users-cog me-1"></i> Atur Penugasan
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, Link } from '@inertiajs/vue3';

export default {
    layout: LayoutAdmin,
    components: { Head, Link },
    props: {
        exam_sessions: Array,
        asesors: Array,
    },
}
</script>
