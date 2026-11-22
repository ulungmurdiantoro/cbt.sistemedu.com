<template>
    <Head>
        <title>Konfirmasi Ujian - Aplikasi Ujian Online</title>
    </Head>
    <ExamConfirmation :exam_group="exam_group" :grade="grade" :lock="lock" :start-url="`/student/essay-migas-start/${exam_group.id}`" />
</template>

<script>
    //import layout student
    import LayoutStudent from '../../../Layouts/Student.vue';

    import { Head } from '@inertiajs/vue3';
    import ExamConfirmation from '../../../Components/Student/ExamConfirmation.vue';

    export default {
        //layout
        layout: LayoutStudent,

        components: {
            Head,
            ExamConfirmation,
        },

        props: {
            exam_group: Object,
            grade: Object,
            // { blocked: pesan bila ujian lain sedang berjalan, closes: [bagian praktik yang ditutup bila ujian ini dimulai] }
            lock: { type: Object, default: () => ({ blocked: null, closes: [] }) },
        },
    }
</script>