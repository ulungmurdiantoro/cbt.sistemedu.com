<template>
    <Head>
        <title>Tambah Soal Ujian - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link :href="`/admin/exams/${exam.id}`" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-question-circle"></i> Tambah Soal Ujian</h5>
                        <hr>
                        <div class="card border-0 shadow mb-4">
                </div>
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
                                                        menubar: false,
                                                        plugins: 'lists link image emoticons',
                                                        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image emoticons'
                                                    }"
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width:20%" class="fw-bold">Jawaban Benar</td>
                                            <td>
                                                <Editor
                                                    api-key="zb77mx2rawyupaiawfej86t2m3vg29holy1q2nycvi8kx4t6"
                                                    v-model="form.answer"
                                                    :init="{
                                                        height: 130,
                                                        menubar: false,
                                                        plugins: 'lists link image emoticons',
                                                        toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image emoticons'
                                                    }"
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Simpan</button>
                            <button type="reset" class="btn btn-md btn-warning border-0 shadow">Reset</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAdmin from '../../../Layouts/Admin.vue';

    //import Heade and Link from Inertia
    import {
        Head,
        Link,
        router
    } from '@inertiajs/vue3';

    //import reactive from vue
    import { reactive } from 'vue';

    //import sweet alert2
    import Swal from 'sweetalert2';

    //import tinyMCE
    import Editor from '@tinymce/tinymce-vue';

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Link,
            Editor,
        },

        //props
        props: {
            errors: Object,
            exam: Object,
        },

        //inisialisasi composition API
        setup(props) {

            //define form with reactive
            const form = reactive({
                question: '',
                answer: '',
            });

            //method "submit"
            const submit = () => {

                //send data to server
                router.post(`/admin/exams/${props.exam.id}/essays/store`, {
                    //data
                    question: form.question,
                    answer: form.answer,
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Soal Ujian Berhasil Disimpan!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                });

            }

            //return
            return {
                form,
                submit,
            };

        }

    }

</script>

<style>

</style>
