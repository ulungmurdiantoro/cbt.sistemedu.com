<template>
    <TinjauSertifikasi :exam_session="exam_session" :rows="rows" :pengambil_keputusan="pengambil_keputusan"
        stage="ms" base="/manager-sertifikasi">
        <template #guide>
            <PageGuide storage-key="cert_manager.sertifikasi"><GuideSertifikasi /></PageGuide>
        </template>
    </TinjauSertifikasi>
</template>

<script>
import LayoutCertificationManager from '../../../Layouts/CertificationManager.vue';
import TinjauSertifikasi from '../../../Components/Sertifikasi/TinjauSertifikasi.vue';
import PageGuide from '../../../Components/PageGuide.vue';
import GuideSertifikasi from '../../../Components/Guide/CertificationManager/Sertifikasi.vue';

export default {
    layout: LayoutCertificationManager,
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
