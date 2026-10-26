<template>
    <TinjauSertifikasi :exam_session="exam_session" :rows="rows" :pengambil_keputusan="pengambil_keputusan"
        stage="pk" base="/manager">
        <template #guide>
            <PageGuide storage-key="manager.sertifikasi"><GuideSertifikasi /></PageGuide>
        </template>
    </TinjauSertifikasi>
</template>

<script>
import LayoutManager from '../../../Layouts/Manager.vue';
import TinjauSertifikasi from '../../../Components/Sertifikasi/TinjauSertifikasi.vue';
import PageGuide from '../../../Components/PageGuide.vue';
import GuideSertifikasi from '../../../Components/Guide/Manager/Sertifikasi.vue';

export default {
    layout: LayoutManager,
    components: { TinjauSertifikasi, PageGuide, GuideSertifikasi },
    // Prop Inertia lain (auth, errors, ...) jangan diteruskan ke komponen bersama.
    inheritAttrs: false,
    props: {
        exam_session:        Object,
        rows:                Array,
        pengambil_keputusan: Object,
    },
}
</script>
