<template>
    <Head>
        <title>Ujian Dengan Nomor Soal : {{ page }} - Aplikasi Ujian Online</title>
    </Head>
    <div class="row mb-5">
        <div class="col-md-7">
            <div class="card border-0 shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-0">Soal No. <strong class="fw-bold">{{ page }}</strong></h5>
                        </div>
                        <div>
                            <VueCountdown :time="duration" @progress="handleChangeDuration" @end="showModalEndTimeExam = true" v-slot="{ hours, minutes, seconds }">
                                <span class="badge bg-info p-2"> <i class="fa fa-clock"></i> {{ hours }} jam,
                                    {{ minutes }} menit, {{ seconds }} detik.</span>
                            </VueCountdown>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="essay_active !== null">
                        <div>
                            <p v-html="essay_active.essay.question" style="user-select: none;"></p>
                        </div>

                        <table>
                            <tbody>
                                <QuillEditor
                                    ref="editor"
                                    v-model:content="form.answer"
                                    contentType="html"
                                    theme="snow"
                                    :options="{
                                        modules: {
                                            toolbar: [
                                                ['bold', 'italic', 'underline'],
                                                [{ 'align': [] }],
                                                [{ 'header': [1, 2, 3, false] }],
                                                [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                                            ]
                                        },
                                        placeholder: 'Ketik jawaban Anda di sini...',
                                    }"
                                    style="min-height: 300px; width: 550px;" 
                                    @update:content="val => form.answer = val"
                                    @ready="disablePaste"
                                />

                                <button @click="submitAnswer(essay_active.essay.exam.id, essay_active.essay_id, form.answer)"
                                    class="btn btn-md btn-info border-0 shadow mt-2 text-white">
                                    Simpan Jawaban
                                </button>

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div class="text-start">
                            <button v-if="page > 1" @click.prevent="prevPage" type="button" class="btn btn-gray-400 btn-sm btn-block mb-2">Sebelumnya</button>
                        </div>
                        <div class="text-end">
                            <button v-if="page < Object.keys(all_essays).length" @click.prevent="nextPage" type="button" class="btn btn-gray-400 btn-sm">Selanjutnya</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow">
                <div class="card-header text-center">
                    <div class="badge bg-success p-2"> {{ essay_answered }} dikerjakan</div>
                </div>
                <div class="card-body" style="height: 330px;overflow-y: auto">

                    <div v-for="(essay, index) in all_essays" :key="index">
                        <div width="20%" style="width: 20%; float: left;">
                            <div style="padding: 5px;">

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 == page" class="btn btn-gray-400 btn-sm w-100">{{ index + 1 }}</button>

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 != page && essay.answer == 0" class="btn btn-outline-info btn-sm w-100">{{ index + 1 }}</button>

                                <button @click.prevent="clickQuestion(index)" v-if="index+1 != page && essay.answer != 0" class="btn btn-info btn-sm w-100">{{ index + 1 }}</button>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button @click="showModalEndExam = true" class="btn btn-danger btn-md border-0 shadow w-100">Akhiri Ujian</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal akhiri ujian -->
    <div v-if="showModalEndExam" class="modal fade" :class="{ 'show': showModalEndExam }" tabindex="-1" aria-hidden="true" style="display:block;" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Akhiri Ujian ?</h5>
                </div>
                <div class="modal-body">
                    Setelah mengakhiri ujian, Anda tidak dapat kembali ke ujian ini lagi. Yakin akan mengakhiri ujian?
                </div>
                <div class="modal-footer">
                    <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya, Akhiri</button>
                    <button @click.prevent="showModalEndExam = false" type="button" class="btn btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- modal waktu ujian berakhir -->
    <div v-if="showModalEndTimeExam" class="modal fade" :class="{ 'show': showModalEndTimeExam }" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true" style="display:block;" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Waktu Habis !</h5>
                </div>
                <div class="modal-body">
                    Waktu ujian sudah berakhir!. Klik <strong class="fw-bold">Ya</strong> untuk mengakhiri ujian.
                </div>
                <div class="modal-footer">
                    <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    import LayoutStudent from '../../../Layouts/Student.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { reactive, ref } from 'vue';
    import VueCountdown from '@chenfengyuan/vue-countdown';
    import axios from 'axios';
    import Swal from 'sweetalert2';
    import { QuillEditor } from '@vueup/vue-quill';
    import '@vueup/vue-quill/dist/vue-quill.snow.css';

    export default {
        layout: LayoutStudent,
        components: {
            Head,
            Link,
            VueCountdown,
            QuillEditor
        },
        props: {
            id: Number,
            page: Number,
            exam_group: Object,
            all_essays: Array,
            essay_answered: Number,
            essay_active: Object,
            answer_order: Array,
            duration: Object,
        },
        setup(props) {
            const form = reactive({
                answer: props.essay_active?.answer || '', 
            });
            const counter = ref(0);
            const duration = ref(props.duration.duration);

            const handleChangeDuration = (() => {
                duration.value = duration.value - 1000;
                counter.value = counter.value + 1;

                if (duration.value > 0 && counter.value % 10 == 1) {
                    axios.put(`/student/essay-duration/update/${props.duration.id}`, {
                        duration: duration.value
                    });
                }
            });

            const prevPage = (() => {
                axios.put(`/student/essay-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });
                router.get(`/student/essay/${props.id}/${props.page - 1}`);
            });

            const nextPage = (() => {
                axios.put(`/student/essay-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });
                router.get(`/student/essay/${props.id}/${props.page + 1}`);
            });

            const clickQuestion = ((index) => {
                axios.put(`/student/essay-duration/update/${props.duration.id}`, {
                    duration: duration.value
                });
                router.get(`/student/essay/${props.id}/${index + 1}`);
            });

            const submitAnswer = ((exam_id, essay_id, answer) => {
                if (!form.answer || form.answer.trim() === '') {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Jawaban tidak boleh kosong',
                        icon: 'error',
                        showConfirmButton: true
                    });
                    return;
                }

                router.post(
                    '/student/essay-answer',
                    {
                        exam_id: exam_id,
                        exam_session_id: props.exam_group.exam_session.id,
                        essay_id: essay_id,
                        answer: form.answer,
                        duration: duration.value
                    },
                    {
                        onSuccess: () => {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Jawaban Berhasil Disimpan.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        },
                        onError: (errors) => {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Gagal menyimpan jawaban',
                                icon: 'error',
                                showConfirmButton: true
                            });
                        }
                    }
                );
            });

            const showModalEndExam = ref(false);
            const showModalEndTimeExam = ref(false);

            const endExam = (() => {
                router.post('/student/essay-end', {
                    exam_group_id: props.exam_group.id,
                    exam_id: props.exam_group.exam.id,
                    exam_session_id: props.exam_group.exam_session.id,
                });
                Swal.fire({
                    title: 'Success!',
                    text: 'Ujian Selesai!.',
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 4000
                });
            });

            const disablePaste = (quill) => {
                quill.root.addEventListener('paste', (e) => {
                    e.preventDefault();
                    alert('Maaf, fitur paste dinonaktifkan untuk jawaban ini.');
                });
            };

            return {
                duration,
                handleChangeDuration,
                prevPage,
                nextPage,
                clickQuestion,
                submitAnswer,
                showModalEndExam,
                showModalEndTimeExam,
                endExam,
                form,
                disablePaste,
            }
        }
    }
</script>

<style>

</style>