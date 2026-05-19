<template>
    <Head>
        <title>Edit Skema - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/classrooms" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-clone"></i> Edit Skema</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label>Kode Skema</label>
                                    <input type="text" class="form-control" placeholder="contoh: EDUKIA-ToT-2024-004" v-model="form.kode_skema">
                                    <div v-if="errors.kode_skema" class="alert alert-danger mt-2">{{ errors.kode_skema }}</div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label>Nama Skema</label>
                                    <input type="text" class="form-control" placeholder="Masukkan Nama Skema" v-model="form.title">
                                    <div v-if="errors.title" class="alert alert-danger mt-2">{{ errors.title }}</div>
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label>Nama Skema (Bahasa Inggris)</label>
                                    <input type="text" class="form-control" placeholder="Enter Scheme Name in English" v-model="form.title_en">
                                    <div v-if="errors.title_en" class="alert alert-danger mt-2">{{ errors.title_en }}</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label>Gelar</label>
                                    <input type="text" class="form-control" placeholder="contoh: A.Md.Kom" v-model="form.gelar">
                                    <div v-if="errors.gelar" class="alert alert-danger mt-2">{{ errors.gelar }}</div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-md btn-primary border-0 shadow me-2">Update</button>
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

    export default {

        //layout
        layout: LayoutAdmin,

        //register components
        components: {
            Head,
            Link
        },

        //props
        props: {
            errors: Object,
            classroom: Object
        },

        //inisialisasi composition API
        setup(props) {

            //define form with reactive
            const form = reactive({
                kode_skema: props.classroom.kode_skema ?? '',
                title:      props.classroom.title      ?? '',
                title_en:   props.classroom.title_en   ?? '',
                gelar:      props.classroom.gelar      ?? '',
            });

            //method "submit"
            const submit = () => {

                //send data to server
                router.put(`/admin/classrooms/${props.classroom.id}`, {
                    kode_skema: form.kode_skema,
                    title:      form.title,
                    title_en:   form.title_en,
                    gelar:      form.gelar,
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Skema Berhasil Diupdate!.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    },
                });

            }

            return {
                form,
                submit,
            }

        }

    }

</script>

<style>

</style>