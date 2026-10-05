<template>
    <TugasPreview :exam-session="exam_session" :student="student" :tugas="tugas"
        :base-url="`/admin/results/${exam_session.id}/tugas/${student.id}`" />
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import TugasPreview from '../../../Components/TugasPreview.vue';

export default {
    layout: LayoutAdmin,
    components: { TugasPreview },

    props: {
        exam_session: Object,
        student: Object,
        tugas: Object,
    },
}
</script>
