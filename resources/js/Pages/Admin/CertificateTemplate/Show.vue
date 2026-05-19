<template>
    <Head><title>Template Dokumen - Aplikasi Ujian Online</title></Head>

    <div class="container-fluid mt-4 mb-5">
        <h5 class="fw-bold mb-4">Template Dokumen Sertifikasi</h5>

        <div v-if="$page.props.flash?.success" class="alert alert-success py-2 small border-0 mb-3">
            {{ $page.props.flash.success }}
        </div>

        <form @submit.prevent="submit" enctype="multipart/form-data">
            <div class="row g-4">

                <!-- Kolom kiri: Upload file -->
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fa fa-image me-2 text-muted"></i>Upload Gambar</h6>

                            <!-- Kop surat -->
                            <div class="mb-3">
                                <label class="fw-semibold small">Logo / Kop Surat <span class="text-muted">(kiri)</span></label>
                                <input type="file" class="form-control mt-1" accept="image/*" @change="onFile('kop', $event)">
                                <div v-if="previews.kop" class="mt-2">
                                    <img :src="previews.kop" class="img-fluid rounded border" style="max-height:80px">
                                </div>
                                <div v-else-if="template?.kop_path" class="mt-2">
                                    <img :src="`/storage/${template.kop_path}`" class="img-fluid rounded border" style="max-height:80px">
                                    <div class="form-text">Kop tersimpan</div>
                                </div>
                            </div>

                            <!-- Logo 2 (IAF/KAN) -->
                            <div class="mb-3">
                                <label class="fw-semibold small">Logo IAF/KAN <span class="text-muted">(kanan, opsional)</span></label>
                                <input type="file" class="form-control mt-1" accept="image/*" @change="onFile('kop_logo2', $event)">
                                <div v-if="previews.kop_logo2" class="mt-2">
                                    <img :src="previews.kop_logo2" class="img-fluid rounded border" style="max-height:80px">
                                </div>
                                <div v-else-if="template?.kop_logo2_path" class="mt-2">
                                    <img :src="`/storage/${template.kop_logo2_path}`" class="img-fluid rounded border" style="max-height:80px">
                                    <div class="form-text">Logo2 tersimpan</div>
                                </div>
                            </div>

                            <!-- TTD -->
                            <div class="mb-3">
                                <label class="fw-semibold small">Tanda Tangan</label>
                                <input type="file" class="form-control mt-1" accept="image/*" @change="onFile('ttd', $event)">
                                <div v-if="previews.ttd" class="mt-2">
                                    <img :src="previews.ttd" class="img-fluid rounded border" style="max-height:60px">
                                </div>
                                <div v-else-if="template?.ttd_path" class="mt-2">
                                    <img :src="`/storage/${template.ttd_path}`" class="img-fluid rounded border" style="max-height:60px">
                                    <div class="form-text">TTD tersimpan</div>
                                </div>
                            </div>

                            <!-- Background sertifikat -->
                            <div class="mb-3">
                                <label class="fw-semibold small">Background Sertifikat</label>
                                <input type="file" class="form-control mt-1" accept="image/*" @change="onFile('bg_sertifikat', $event)">
                                <div v-if="previews.bg_sertifikat" class="mt-2">
                                    <img :src="previews.bg_sertifikat" class="img-fluid rounded border" style="max-height:100px">
                                </div>
                                <div v-else-if="template?.bg_sertifikat_path" class="mt-2">
                                    <img :src="`/storage/${template.bg_sertifikat_path}`" class="img-fluid rounded border" style="max-height:100px">
                                    <div class="form-text">Background tersimpan</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penandatangan -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fa fa-user-tie me-2 text-muted"></i>Penandatangan</h6>
                            <div class="mb-3">
                                <label class="fw-semibold small">Nama Penandatangan</label>
                                <input type="text" class="form-control mt-1" v-model="form.nama_penandatangan"
                                    placeholder="Dr. Nama Lengkap, M.Pd.">
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold small">Jabatan</label>
                                <input type="text" class="form-control mt-1" v-model="form.jabatan_penandatangan"
                                    placeholder="Direktur LSP Edukasi Global Cendekia">
                            </div>
                            <div>
                                <label class="fw-semibold small">Kota Penetapan <span class="text-muted">(untuk SK)</span></label>
                                <input type="text" class="form-control mt-1" v-model="form.kota"
                                    placeholder="Semarang">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom kanan: Body SK -->
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold mb-0"><i class="fa fa-file-alt me-2 text-muted"></i>Template Body SK</h6>
                                <div class="dropdown">
                                    <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                        Placeholder
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end small">
                                        <li v-for="p in placeholders" :key="p.tag">
                                            <button type="button" class="dropdown-item"
                                                @click="insertPlaceholder(p.tag)">
                                                <code>{{ p.tag }}</code> — {{ p.label }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="form-text mb-2">
                                Tulis isi paragraf SK di sini. Gunakan HTML sederhana (&lt;p&gt;, &lt;strong&gt;, &lt;br&gt;).
                            </div>
                            <textarea class="form-control flex-grow-1" v-model="form.sk_body"
                                style="min-height:320px;font-family:monospace;font-size:0.82rem"
                                ref="skBodyRef"
                                placeholder="<p>Berdasarkan hasil asesmen yang dilaksanakan pada ...</p>"></textarea>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark px-4" :disabled="processing">
                    <i class="fa fa-save me-1"></i>
                    {{ processing ? 'Menyimpan...' : 'Simpan Template' }}
                </button>
            </div>
        </form>

    </div>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

export default {
    layout: LayoutAdmin,
    components: { Head },
    props: {
        template: Object,
        errors: { type: Object, default: () => ({}) },
    },

    setup(props) {
        const processing = ref(false);
        const skBodyRef  = ref(null);

        const form = reactive({
            nama_penandatangan:    props.template?.nama_penandatangan    ?? '',
            jabatan_penandatangan: props.template?.jabatan_penandatangan ?? '',
            sk_body:               props.template?.sk_body               ?? '',
            kota:                  props.template?.kota                  ?? 'Semarang',
        });

        const files    = reactive({ kop: null, kop_logo2: null, ttd: null, bg_sertifikat: null });
        const previews = reactive({ kop: null, kop_logo2: null, ttd: null, bg_sertifikat: null });

        const onFile = (key, e) => {
            const file = e.target.files[0];
            if (!file) return;
            files[key]    = file;
            previews[key] = URL.createObjectURL(file);
        };

        const placeholders = [
            { tag: '{{nama_peserta}}',     label: 'Nama peserta' },
            { tag: '{{no_peserta}}',       label: 'Nomor peserta' },
            { tag: '{{nama_skema}}',       label: 'Nama skema' },
            { tag: '{{kode_skema}}',       label: 'Kode skema' },
            { tag: '{{nomor_sk}}',         label: 'Nomor SK' },
            { tag: '{{tanggal_sk}}',       label: 'Tanggal terbit SK' },
            { tag: '{{keputusan}}',        label: 'LULUS / TIDAK LULUS' },
            { tag: '{{tempat_ujian}}',     label: 'Tempat ujian' },
            { tag: '{{konteks_asesmen}}',  label: 'Konteks asesmen' },
            { tag: '{{tanggal_ujian}}',    label: 'Tanggal ujian' },
            { tag: '{{nama_penandatangan}}', label: 'Nama penandatangan' },
            { tag: '{{jabatan_penandatangan}}', label: 'Jabatan penandatangan' },
        ];

        const insertPlaceholder = (tag) => {
            const el = skBodyRef.value;
            if (!el) { form.sk_body += tag; return; }
            const start = el.selectionStart;
            const end   = el.selectionEnd;
            form.sk_body = form.sk_body.slice(0, start) + tag + form.sk_body.slice(end);
            el.focus();
        };

        const submit = () => {
            processing.value = true;
            const fd = new FormData();
            if (files.kop)           fd.append('kop',          files.kop);
            if (files.kop_logo2)     fd.append('kop_logo2',    files.kop_logo2);
            if (files.ttd)           fd.append('ttd',          files.ttd);
            if (files.bg_sertifikat) fd.append('bg_sertifikat', files.bg_sertifikat);
            fd.append('nama_penandatangan',    form.nama_penandatangan);
            fd.append('jabatan_penandatangan', form.jabatan_penandatangan);
            fd.append('sk_body',               form.sk_body);
            fd.append('kota',                  form.kota);
            fd.append('_method', 'POST');

            router.post('/admin/certificate-template', fd, {
                forceFormData: true,
                onFinish: () => { processing.value = false; },
            });
        };

        return { form, files, previews, processing, placeholders, skBodyRef, onFile, insertPlaceholder, submit };
    },
}
</script>
