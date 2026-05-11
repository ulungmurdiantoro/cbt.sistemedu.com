<template>

    <Head>
        <title>Login Administrator - Aplikasi Ujian Online</title>
    </Head>
    <div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:#f5f8fb">
        <div style="width:100%;max-width:420px" class="px-3">
            <div class="text-center mb-4">
                <img src="/assets/images/logo.png" alt="Logo" style="max-height:70px;object-fit:contain">
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <h5 class="fw-bold text-center mb-4">Login Administrator</h5>

                    <div v-if="errors.email" class="alert alert-danger py-2 small border-0">{{ errors.email }}</div>
                    <div v-if="errors.password" class="alert alert-danger py-2 small border-0">{{ errors.password }}</div>

                    <form @submit.prevent="submit">
                        <div class="mb-3">
                            <label class="fw-semibold small">Email</label>
                            <div class="input-group mt-1">
                                <span class="input-group-text"><i class="fa fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control" v-model="form.email" placeholder="Email Address">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="fw-semibold small">Password</label>
                            <div class="input-group mt-1">
                                <span class="input-group-text"><i class="fa fa-lock text-muted"></i></span>
                                <input type="password" class="form-control" v-model="form.password" placeholder="Password">
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-4">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-gray-800">LOGIN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    //import layout
    import LayoutAuth from '../../Layouts/Auth.vue';

    //import Inertia
    import {
        Head,
        router
    } from '@inertiajs/vue3';

    //import reactive
    import {
        reactive
    } from 'vue';

    export default {

        //layout
        layout: LayoutAuth,

        //register component
        components: {
            Head
        },

        //props
        props: {
            errors: Object,
            session: Object
        },

        //define composition API
        setup() {

            //define form state
            const form = reactive({
                email: '',
                password: '',
            });

            //submit method
            const submit = () => {

                //send data to server
                router.post('/login', {

                    //data
                    email: form.email,
                    password: form.password,
                });
            }

            //return form state and submit method
            return {
                form,
                submit,
            };

        }

    }

</script>