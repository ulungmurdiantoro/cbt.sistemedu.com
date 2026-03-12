<template>
    <Head>
        <title>Tambah Soal Ujian - Aplikasi Ujian Online</title>
    </Head>

    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link
                    :href="`/admin/exams/${exam.id}`"
                    class="btn btn-md btn-primary border-0 shadow mb-3"
                    type="button"
                >
                    <i class="fa fa-long-arrow-alt-left me-2"></i> Kembali
                </Link>

                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-question-circle"></i> Tambah Soal Ujian</h5>
                        <hr>

                        <form @submit.prevent="submit">
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-centered table-nowrap mb-0 rounded">
                                    <tbody>
                                        <tr>
                                            <td style="width:20%" class="fw-bold">Soal</td>
                                            <td>
                                                <Editor
                                                    api-key="zb77mx2rawyupaiawfej86t2m3vg29holy1q2nycvi8kx4t6"
                                                    v-model="form.question"
                                                    :init="{
                                                        height: 250,
                                                        menubar: false,
                                                        plugins: 'lists link image emoticons',
                                                        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image emoticons'
                                                    }"
                                                />
                                                <div v-if="errors.question" class="alert alert-danger mt-2 mb-0">
                                                    {{ errors.question }}
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold">Tipe Soal</td>
                                            <td>
                                                <div class="form-check">
                                                    <input
                                                        class="form-check-input"
                                                        type="checkbox"
                                                        id="is_essay"
                                                        v-model="form.is_essay"
                                                        :true-value="1"
                                                        :false-value="0"
                                                    >
                                                    <label class="form-check-label" for="is_essay">
                                                        Centang jika soal berbentuk uraian
                                                    </label>
                                                </div>

                                                <small class="text-muted d-block mt-2">
                                                    Jika dicentang, soal akan ditandai sebagai soal uraian/essay.
                                                </small>

                                                <div v-if="errors.is_essay" class="alert alert-danger mt-2 mb-0">
                                                    {{ errors.is_essay }}
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="width:20%" class="fw-bold">
                                                {{ form.is_essay ? 'Poin Jawaban / Kisi Jawaban' : 'Jawaban Benar' }}
                                            </td>
                                            <td>
                                                <Editor
                                                    api-key="zb77mx2rawyupaiawfej86t2m3vg29holy1q2nycvi8kx4t6"
                                                    v-model="form.answer"
                                                    :init="{
                                                        height: 180,
                                                        menubar: false,
                                                        plugins: 'lists link image emoticons',
                                                        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image emoticons'
                                                    }"
                                                />

                                                <small v-if="form.is_essay" class="text-muted d-block mt-2">
                                                    Isikan poin-poin jawaban atau kata kunci penilaian untuk soal uraian.
                                                </small>

                                                <div v-if="errors.answer" class="alert alert-danger mt-2 mb-0">
                                                    {{ errors.answer }}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">
                                Simpan
                            </button>
                            <button type="button" @click="resetForm" class="btn btn-md btn-warning border-0 shadow">
                                Reset
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import Swal from 'sweetalert2'
import Editor from '@tinymce/tinymce-vue'

export default {
    layout: LayoutAdmin,

    components: {
        Head,
        Link,
        Editor,
    },

    props: {
        errors: Object,
        exam: Object,
    },

    setup(props) {
        const form = reactive({
            question: '',
            answer: '',
            is_essay: 0,
        })

        const submit = () => {
            router.post(`/admin/exams/${props.exam.id}/essays/store`, {
                question: form.question,
                answer: form.answer,
                is_essay: form.is_essay,
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Soal Ujian Berhasil Disimpan!.',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    })

                    form.question = ''
                    form.answer = ''
                    form.is_essay = 0
                },
            })
        }

        const resetForm = () => {
            form.question = ''
            form.answer = ''
            form.is_essay = 0
        }

        return {
            form,
            submit,
            resetForm,
        }
    }
}
</script>

<style scoped>
.table td {
    vertical-align: middle;
}
</style>