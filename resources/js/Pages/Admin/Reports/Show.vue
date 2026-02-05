<template>
    <Head>
        <title>Detail Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">

                <Link href="/admin/reports" class="btn btn-md btn-primary border-0 shadow mb-3" type="button">
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow mb-4">
                    <div class="card-body">
                        <h5> <i class="fa fa-edit"></i> Detail Ujian Peserta</h5>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">Nama Peserta</td>
                                        <td>{{ grade.student.name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Jabatan</td>
                                        <td>{{ grade.student.position }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Asal Institusi</td>
                                        <td>{{ grade.student.institution }}</td>
                                    </tr>
                                    <tr>
                                        <td style="width:30%" class="fw-bold">Nama Ujian</td>
                                        <td>{{ grade.exam.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Sesi</td>
                                        <td>{{ grade.exam_session.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Skema</td>
                                        <td>{{ grade.exam.classroom.title }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Tipe Ujian</td>
                                        <td>{{ grade.exam.type }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Nilai</td>
                                        <td>{{ grade.grade }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <!-- =========================
                     PILIHAN GANDA
                ========================== -->
                <div v-if="grade.exam.type == 'Pilihan Ganda'">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card border-0 shadow">
                                <div class="card-body">
                                    <h5> <i class="fa fa-question-circle"></i> Detail Jawaban Peserta</h5>
                                    <hr>
                                    <div class="table-responsive mt-3 mb-4">
                                        <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                            <thead class="thead-dark">
                                                <tr class="border-0">
                                                    <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                    <th class="border-0 rounded-end">Soal</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr v-for="(question, index) in grade.questions.data" :key="index">
                                                    <td class="fw-bold text-center">
                                                        {{ ++index + (grade.questions.current_page - 1) * grade.questions.per_page }}
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold" v-html="question.question"></div>
                                                        <hr>
                                                        <ol type="A">
                                                            <li v-html="question.option_1" :class="{ 'text-success fw-bold': question.answer == '1' }"></li>
                                                            <li v-html="question.option_2" :class="{ 'text-success fw-bold': question.answer == '2' }"></li>
                                                            <li v-html="question.option_3" :class="{ 'text-success fw-bold': question.answer == '3' }"></li>
                                                            <li v-html="question.option_4" :class="{ 'text-success fw-bold': question.answer == '4' }"></li>
                                                            <li v-html="question.option_5" :class="{ 'text-success fw-bold': question.answer == '5' }"></li>
                                                        </ol>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <Pagination :links="grade.questions.links" align="end" />
                                </div>
                            </div>
                        </div>

                        <div style="position: absolute; overflow: visible; height: 100%; right: 0;" class="col-md-3-5">
                            <div class="sticky-top">
                                <div class="card border-0 shadow">
                                    <div class="card-body">
                                        <div class="table-responsive mt-3 mb-4">
                                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                                <thead class="thead-dark">
                                                    <tr class="border-0">
                                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                        <th class="border-0 rounded-end">Jawaban Peserta</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr v-for="(answer, index) in grade.answers.data" :key="index">
                                                        <td>{{ ++index + (grade.questions.current_page - 1) * grade.questions.per_page }}</td>
                                                        <td v-if="grade.student.id == answer.student_id">
                                                            <p v-if="answer.answer == 1"><b>A</b></p>
                                                            <p v-else-if="answer.answer == 2"><b>B</b></p>
                                                            <p v-else-if="answer.answer == 3"><b>C</b></p>
                                                            <p v-else-if="answer.answer == 4"><b>D</b></p>
                                                            <p v-else-if="answer.answer == 5"><b>E</b></p>
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
                </div>

                <!-- =========================
                     ESSAY MIGAS
                     - kolom kanan: tampilkan 1 jawaban saja
                     - berupa LINK download file dari /storage/<path dari DB>
                ========================== -->
                <div v-else-if="grade.exam.type == 'Essay Migas'">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card border-0 shadow">
                                <div class="card-body">
                                    <h5> <i class="fa fa-question-circle"></i> Detail Jawaban Peserta</h5>
                                    <hr>
                                    <div class="table-responsive mt-3 mb-4">
                                        <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                            <thead class="thead-dark">
                                                <tr class="border-0">
                                                    <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                    <th class="border-0 rounded-end">Soal</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr v-for="(essay, index) in grade.essays.data" :key="index">
                                                    <td class="fw-bold text-center">
                                                        {{ ++index + (grade.essays.current_page - 1) * grade.questions.per_page }}
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold" v-html="essay.question"></div>
                                                        <hr>
                                                        <ol type="A">
                                                            <li v-html="essay.answer" :class="'text-success fw-bold'"></li>
                                                        </ol>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <Pagination :links="grade.questions.links" align="end" />
                                </div>
                            </div>
                        </div>

                        <div style="position: absolute; overflow: visible; height: 100%; right: 0;" class="col-md-3-5">
                            <div class="sticky-top">
                                <div class="card border-0 shadow">
                                    <div class="card-body">
                                        <div class="table-responsive mt-3 mb-4">
                                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                                <thead class="thead-dark">
                                                    <tr class="border-0">
                                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                        <th class="border-0 rounded-end">Jawaban Peserta</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <!-- tampilkan 1 baris saja -->
                                                    <tr>
                                                        <td>1</td>
                                                        <td>
                                                            <div v-if="essayMigasPath">
                                                                <a
                                                                    :href="essayMigasDownloadUrl"
                                                                    class="text-primary fw-bold"
                                                                    target="_blank"
                                                                    rel="noopener"
                                                                    download
                                                                >
                                                                    {{ essayMigasFileName }}
                                                                </a>
                                                                <div class="small text-muted mt-1">
                                                                    Klik untuk download.
                                                                </div>
                                                            </div>
                                                            <div v-else class="text-muted">
                                                                File jawaban belum tersedia.
                                                            </div>
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
                </div>

                <!-- =========================
                     ESSAY (LAINNYA)
                ========================== -->
                <div v-else>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card border-0 shadow">
                                <div class="card-body">
                                    <h5> <i class="fa fa-question-circle"></i> Detail Jawaban Peserta</h5>
                                    <hr>
                                    <div class="table-responsive mt-3 mb-4">
                                        <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                            <thead class="thead-dark">
                                                <tr class="border-0">
                                                    <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                    <th class="border-0 rounded-end">Soal</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                <tr v-for="(essay, index) in grade.essays.data" :key="index">
                                                    <td class="fw-bold text-center">
                                                        {{ ++index + (grade.essays.current_page - 1) * grade.questions.per_page }}
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold" v-html="essay.question"></div>
                                                        <hr>
                                                        <ol type="A">
                                                            <li v-html="essay.answer" :class="'text-success fw-bold'"></li>
                                                        </ol>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <Pagination :links="grade.questions.links" align="end" />
                                </div>
                            </div>
                        </div>

                        <div style="position: absolute; overflow: visible; height: 100%; right: 0;" class="col-md-3-5">
                            <div class="sticky-top">
                                <div class="card border-0 shadow">
                                    <div class="card-body">
                                        <div class="table-responsive mt-3 mb-4">
                                            <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                                <thead class="thead-dark">
                                                    <tr class="border-0">
                                                        <th class="border-0 rounded-start" style="width:5%">No.</th>
                                                        <th class="border-0 rounded-end">Jawaban Peserta</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    <tr v-for="(essayanswer, index) in grade.essaysanswers.data" :key="index">
                                                        <td>{{ ++index + (grade.questions.current_page - 1) * grade.questions.per_page }}</td>
                                                        <td v-if="grade.student.id == essayanswer.student_id" v-html="essayanswer.answer">
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
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAdmin from '../../../Layouts/Admin.vue';

    //import component pagination
    import Pagination from '../../../Components/Pagination.vue';

    //import Head and Link from Inertia
    import { Head, Link, router } from '@inertiajs/vue3';

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Link,
            Pagination
        },

        //props
        props: {
            errors: Object,
            grade: Object,
        },

        computed: {
            /**
             * essayMigasPath: path lengkap dari DB.
             * Contoh DB:
             * "essay_migas_answers/27/77/484/simulasi-...pdf"
             */
            essayMigasPath() {
                const list = this.grade?.essaysanswers?.data || [];
                const found = list.find(x => x.student_id == this.grade?.student?.id) || list[0];

                if (!found || !found.answer) return "";

                let p = String(found.answer).trim().replace(/\\/g, "/");
                if (p.startsWith("/")) p = p.slice(1); // hilangkan leading slash
                return p;
            },

            essayMigasFileName() {
                if (!this.essayMigasPath) return "";
                return this.essayMigasPath.split("/").pop();
            },

            /**
             * URL publik: /storage/<path dari DB>
             * Jadi: /storage/essay_migas_answers/27/77/484/xxx.pdf
             */
            essayMigasDownloadUrl() {
                if (!this.essayMigasPath) return "#";
                return `/storage/${encodeURI(this.essayMigasPath)}`;
            },
        },

        data() {
            return {};
        },

    }
</script>

<style scoped>
    .sticky-element {
        position: -webkit-sticky; /* For Safari */
        position: sticky; /* For other modern browsers */
        top: 0; /* Required for sticky positioning */
        right: 0; /* Define the right value */
        z-index: 5; /* Optional: to ensure the element stacks above others */
        overflow: hidden; /* Apply overflow hidden */
    }
</style>
