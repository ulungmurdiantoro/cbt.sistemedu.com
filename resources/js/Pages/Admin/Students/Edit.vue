<template>
    <Head>
        <title>Edit Peserta - Aplikasi Ujian Online</title>
    </Head>
    <div class="container-fluid mb-5 mt-5">
        <div class="row">
            <div class="col-md-12">
                <Link href="/admin/students" class="btn btn-md btn-primary border-0 shadow mb-3" type="button"><i class="fa fa-long-arrow-alt-left me-2"></i> Kembali</Link>
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <h5><i class="fa fa-user"></i> Edit Peserta</h5>
                        <hr>
                        <form @submit.prevent="submit">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>No. Peserta</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan No. Peserta" v-model="form.no_participant">
                                        <div v-if="errors.no_participant" class="alert alert-danger mt-2">
                                            {{ errors.no_participant }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Nama Lengkap</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan Nama Peserta" v-model="form.name">
                                        <div v-if="errors.name" class="alert alert-danger mt-2">
                                            {{ errors.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Skema</label> 
                                        <select class="form-select" v-model="form.classroom_id">
                                            <option v-for="(classroom, index) in classrooms" :key="index" :value="classroom.id">{{ classroom.title }}</option>
                                        </select>
                                        <div v-if="errors.classroom_id" class="alert alert-danger mt-2">
                                            {{ errors.classroom_id }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Jenis Kelamin</label> 
                                        <select class="form-select" v-model="form.gender">
                                            <option value="L">Laki - Laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        <div v-if="errors.gender" class="alert alert-danger mt-2">
                                            {{ errors.gender }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Jabatan</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan Jabatan" v-model="form.position">
                                        <div v-if="errors.position" class="alert alert-danger mt-2">
                                            {{ errors.position }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label>Asal Institusi</label> 
                                        <input type="text" class="form-control" placeholder="Masukkan Asal Institusi" v-model="form.institution">
                                        <div v-if="errors.institution" class="alert alert-danger mt-2">
                                            {{ errors.institution }}
                                        </div>
                                    </div>
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
            classrooms: Array,
            student: Object
        },

        //inisialisasi composition API
        setup(props) {

            //define form with reactive
            const form = reactive({
                no_participant : props.student.no_participant ,
                name: props.student.name,
                classroom_id: props.student.classroom_id,
                gender: props.student.gender,
                position: props.student.position,
                institution: props.student.institution
            });

            //method "submit"
            const submit = () => {

                //send data to server
                router.put(`/admin/students/${props.student.id}`, {
                    //data
                    no_participant: form.no_participant,
                    name: form.name,
                    classroom_id: form.classroom_id,
                    gender: form.gender,
                    position: form.position,
                    institution: form.institution
                }, {
                    onSuccess: () => {
                        //show success alert
                        Swal.fire({
                            title: 'Success!',
                            text: 'Peserta Berhasil Diupdate!.',
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
            };

        }

    }

</script>

<style>

</style>