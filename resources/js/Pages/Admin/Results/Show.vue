<template>
    <Head><title>Rekap Hasil - {{ exam_session.title }}</title></Head>

    <div class="container-fluid mb-5 mt-5">

        <BackBar href="/admin/results">
            <!-- Eks menu Laporan Nilai -->
            <div class="dropdown">
                <button class="btn btn-outline-primary border shadow fw-bolder dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fa fa-download me-1"></i> Laporan Nilai
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="font-size:0.85rem;">
                    <li>
                        <a class="dropdown-item" :href="`/admin/reports/export?exam_session_id=${exam_session.id}`" target="_blank">
                            <i class="fa fa-file-excel text-success me-2"></i>Excel (nilai per ujian)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" :href="`/admin/reports/export-pdf?exam_session_id=${exam_session.id}&layout=ringkas`" target="_blank">
                            <i class="fa fa-file-pdf text-danger me-2"></i>PDF Ringkas (A4)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" :href="`/admin/reports/export-pdf?exam_session_id=${exam_session.id}&layout=lebar`" target="_blank">
                            <i class="fa fa-file-pdf text-danger me-2"></i>PDF Lebar (A0)
                        </a>
                    </li>
                </ul>
            </div>
            <button class="btn btn-outline-success border shadow fw-bolder" @click="distributeSp" :disabled="!hasFinalized">
                <i class="fa fa-paper-plane me-1"></i> 1. Kirim SP
            </button>
            <button class="btn btn-success border-0 shadow text-white fw-bolder" @click="distribute" :disabled="!hasFinalized">
                <i class="fa fa-paper-plane me-1"></i> 2. Kirim SK &amp; Sertifikat
            </button>
        </BackBar>

        <!-- Scheme info -->
        <div v-if="scheme" class="alert alert-info py-2 small border-0 mb-3">
            <i class="fa fa-info-circle me-1"></i>
            Komposisi: PG <strong>{{ scheme.bobot_pg }}%</strong> · Esai <strong>{{ scheme.bobot_esai }}%</strong> · Wawancara <strong>{{ scheme.bobot_wawancara }}%</strong>
            <template v-if="(scheme.bobot_praktik_1 || 0) + (scheme.bobot_praktik_2 || 0) > 0"> · Praktik/Keterampilan <strong>{{ (scheme.bobot_praktik_1 || 0) + (scheme.bobot_praktik_2 || 0) }}%</strong></template>
            · KKM <strong>{{ scheme.nilai_kelulusan }}</strong>
            <span v-if="scheme_frozen" class="ms-1">— <strong>komposisi khusus sesi ini</strong> (sesi lama, tidak ikut perubahan Komposisi Nilai skema)</span>
        </div>
        <div v-else class="alert alert-warning py-2 small border-0 mb-3">
            <i class="fa fa-exclamation-triangle me-1"></i>
            Komposisi nilai belum diatur untuk skema ini. Menggunakan default (PG 40%, Esai 35%, Wawancara 25%).
        </div>

        <div class="alert alert-secondary py-2 small border-0 mb-3">
            <i class="fa fa-lock me-1"></i>
            Peserta diverifikasi <strong>Pengambil Keputusan</strong> sesi, lalu diverifikasi &amp; difinalisasi
            <strong>Manajer Sertifikasi</strong> di portal masing-masing.
            Halaman ini hanya untuk memantau &amp; mengirim dokumen yang sudah difinalisasi.
            <div class="mt-1">
                <i class="fa fa-user-tie me-1"></i>
                Pengambil Keputusan sesi ini:
                <strong v-if="exam_session.pengambil_keputusan">{{ exam_session.pengambil_keputusan.name }}</strong>
                <span v-else>belum ditunjuk (atur di detail sesi, menu Sesi Ujian)</span>
            </div>
            <div v-if="decisionMakers.length" class="mt-1">
                <i class="fa fa-user-check me-1"></i>
                Difinalisasi oleh:
                <template v-for="(name, i) in decisionMakers" :key="name"><template v-if="i">, </template><strong>{{ name }}</strong></template>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.session?.success" class="alert alert-success py-2 small border-0 mb-3">
            {{ $page.props.session.success }}
        </div>

        <!-- Ringkasan progres: klik tahap → tabel hanya menampilkan peserta yang belum menyelesaikan tahap itu -->
        <SectionCard v-if="rows.length" title="Progres Peserta" icon="fa-stream">
            <template #meta>Klik satu tahap untuk menampilkan peserta yang belum menyelesaikannya.</template>
            <div class="p-3 d-flex flex-wrap gap-2">
                <button v-for="s in summary" :key="s.key" type="button" class="summary-step"
                    :class="{ 'is-done': s.done >= s.total, 'is-active': filter.step === s.key }"
                    :title="`${steps[s.key].title}: ${s.done} dari ${s.total} peserta`"
                    @click="toggleStepFilter(s.key)">
                    <div class="summary-label">{{ steps[s.key].label }}</div>
                    <div class="summary-count num">{{ s.done }}<span class="text-muted">/{{ s.total }}</span></div>
                    <div class="progress summary-bar">
                        <div class="progress-bar" :class="s.done >= s.total ? 'bg-success' : 'bg-primary'" :style="{ width: pct(s.done, s.total) + '%' }"></div>
                    </div>
                </button>
                <button v-if="absentCount" type="button" class="summary-step is-warning"
                    :class="{ 'is-active': filter.stage === 'absent' }"
                    title="Peserta yang tidak mengerjakan ujian" @click="filter.stage = filter.stage === 'absent' ? '' : 'absent'; filter.step = ''">
                    <div class="summary-label">Tidak ikut</div>
                    <div class="summary-count num">{{ absentCount }}</div>
                    <div class="small text-muted">peserta</div>
                </button>
            </div>
        </SectionCard>

        <!-- Table -->
        <SectionCard :title="exam_session.title" icon="fa-poll">
            <template #meta>
                Kode Batch: {{ exam_session.kode_batch }} &bull; {{ exam_session.start_time }} – {{ exam_session.end_time }}
                <div>
                    SP terkirim: {{ spSentCount }}/{{ finalizedCount }} &bull; SK/Sertifikat terkirim: {{ finalSentCount }}/{{ finalizedCount }}
                    <template v-if="frAk14Rows.length">
                        &bull;
                        <template v-if="fr_ak_14_requires_materai">FR.AK.14 bermaterai: {{ frAk14StampedCount }}/{{ frAk14Rows.length }}</template>
                        <template v-else>FR.AK.14 ditandatangani: {{ frAk14SignedCount }}/{{ frAk14Rows.length }} (sesi ini tanpa materai)</template>
                    </template>
                </div>
            </template>

            <!-- Cari + filter tahap -->
            <div v-if="rows.length" class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3">
                <input type="text" class="form-control form-control-sm filter-search" v-model="filter.q" placeholder="Cari No. Peserta atau nama...">
                <select class="form-select form-select-sm filter-stage" v-model="filter.stage" @change="filter.step = ''">
                    <option value="">Semua peserta ({{ rows.length }})</option>
                    <option v-for="o in stageOptions" :key="o.value" :value="o.value">{{ o.label }} ({{ o.count }})</option>
                </select>
                <span v-if="filter.step" class="badge bg-gray-200 text-gray-800 border">
                    Belum {{ steps[filter.step].title }}
                    <a href="#" class="ms-1 text-gray-800" title="Hapus filter" @click.prevent="filter.step = ''"><i class="fa fa-times"></i></a>
                </span>
                <button v-if="isFiltered" type="button" class="btn btn-sm btn-gray-100 border" @click="resetFilter">Reset</button>
                <span class="small text-muted ms-auto">Menampilkan {{ filteredRows.length }} dari {{ rows.length }} peserta</span>
            </div>

                <div class="table-responsive p-3">
                    <table class="table table-bordered table-centered table-list mb-0 rounded">
                        <thead class="thead-dark">
                            <tr>
                                <th class="border-0" style="width:3%">No.</th>
                                <th class="border-0">Peserta</th>
                                <th class="border-0 text-center" v-if="show_tugas">Tugas</th>
                                <th class="border-0 text-center" v-if="exam_session.exam_id_pg">PG</th>
                                <th class="border-0 text-center" v-if="exam_session.exam_id_esai">Esai</th>
                                <th class="border-0 text-center" v-if="exam_session.has_wawancara">Wawancara</th>
                                <th class="border-0 text-center" v-if="praktik_parts.length">Praktik/Keterampilan</th>
                                <th class="border-0 text-center">Nilai Akhir</th>
                                <th class="border-0 text-center">Keputusan</th>
                                <th class="border-0">Progres</th>
                                <th class="border-0 text-center" v-if="frAk14Rows.length">FR.AK.14</th>
                                <th class="border-0 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in filteredRows" :key="row.student_id">
                                <td class="text-center">{{ rowNumber[row.student_id] }}</td>
                                <td class="name-cell">
                                    <div class="small text-muted">{{ row.no_participant }}</div>
                                    <a href="#" class="text-gray-800 fw-bolder participant-name" title="Lihat detail peserta" @click.prevent="openDetail(row)">{{ row.name }}</a>
                                    <span v-if="row.attempt > 1" class="badge bg-warning text-gray-800 ms-1 small">Remidi</span>
                                    <!-- Asesor yang ditugaskan (tab Peserta & Asesor di detail sesi) -->
                                    <div class="small mt-1">
                                        <span v-if="row.asesor_name" class="text-muted"><i class="fa fa-user-tie me-1"></i>{{ row.asesor_name }}</span>
                                        <span v-else class="text-muted fst-italic">Asesor belum ditugaskan</span>
                                    </div>
                                </td>
                                <!-- Tugas / Ujian Praktik/Keterampilan yang diunggah peserta -->
                                <td class="text-center text-nowrap" v-if="show_tugas">
                                    <!-- Satu tombol per unggahan; Ujian Praktik/Keterampilan dua bagian → tombol bernomor bagian -->
                                    <template v-if="tugas[row.student_id]">
                                        <a v-for="t in tugas[row.student_id]" :key="t.bagian"
                                           :href="`/admin/results/${exam_session.id}/tugas/${row.student_id}` + (t.bagian > 1 ? `?bagian=${t.bagian}` : '')"
                                           target="_blank"
                                           :title="tugas_parts[t.bagian] ? `Bagian ${t.bagian} (${tugas_parts[t.bagian]}): ${t.original_filename}` : `Lihat ${t.original_filename}`"
                                           class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fa fa-eye"></i><span v-if="Object.keys(tugas_parts).length > 1" class="ms-1">{{ t.bagian }}</span>
                                        </a>
                                    </template>
                                    <span v-else class="text-muted" title="Belum diunggah">—</span>
                                </td>
                                <!-- Angka PG / Esai → detail jawaban peserta (eks Laporan Nilai) -->
                                <td class="text-center num" v-if="exam_session.exam_id_pg">
                                    <span v-if="isAbsent(row)" class="score-note">Tidak ikut</span>
                                    <Link v-else-if="row.grade_id_pg" :href="`/admin/reports/${row.grade_id_pg}`" class="text-primary text-nowrap" title="Detail jawaban PG">
                                        {{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}<i class="fa fa-search-plus small ms-1"></i>
                                    </Link>
                                    <template v-else>{{ row.nilai_pg !== null ? fmt(row.nilai_pg) : '—' }}</template>
                                </td>
                                <td class="text-center num" v-if="exam_session.exam_id_esai">
                                    <span v-if="isAbsent(row)" class="score-note">Tidak ikut</span>
                                    <template v-else>
                                        <Link v-if="row.grade_id_esai" :href="`/admin/reports/${row.grade_id_esai}`" class="text-primary text-nowrap" title="Detail jawaban esai">
                                            {{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}<i class="fa fa-search-plus small ms-1"></i>
                                        </Link>
                                        <template v-else>{{ row.nilai_esai !== null ? fmt(row.nilai_esai) : '—' }}</template>
                                        <div v-if="awaitsScore(row, 'esai')" class="mt-1"><StatusBadge tone="secondary" label="Belum dinilai" /></div>
                                    </template>
                                </td>
                                <td class="text-center num" v-if="exam_session.has_wawancara">
                                    <span v-if="isAbsent(row)" class="score-note">Tidak ikut</span>
                                    <a v-else-if="row.wawancara" href="#" class="text-primary text-nowrap" title="Rincian nilai wawancara"
                                       @click.prevent="showWawancara(row)">
                                        {{ row.nilai_wawancara !== null ? fmt(row.nilai_wawancara) : '—' }}<i class="fa fa-search-plus small ms-1"></i>
                                    </a>
                                    <StatusBadge v-else-if="awaitsScore(row, 'wawancara')" tone="secondary" label="Belum dinilai" />
                                    <template v-else>{{ row.nilai_wawancara !== null ? fmt(row.nilai_wawancara) : '—' }}</template>
                                </td>
                                <!-- Ujian Praktik/Keterampilan dinilai terpisah (mis. Lifting): nilai gabungan, rincian per bagian di tooltip -->
                                <td class="text-center num" v-if="praktik_parts.length" :title="praktikTooltip(row)">
                                    <span v-if="isAbsent(row)" class="score-note">Tidak ikut</span>
                                    <StatusBadge v-else-if="row.nilai_praktik === null && awaitsScore(row, 'praktik')" tone="secondary" label="Belum dinilai" />
                                    <template v-else>
                                        {{ row.nilai_praktik !== null ? fmt(row.nilai_praktik) : '—' }}
                                        <div v-if="row.nilai_praktik !== null && awaitsScore(row, 'praktik')" class="mt-1"><StatusBadge tone="secondary" label="Belum lengkap" /></div>
                                    </template>
                                </td>
                                <td class="text-center fw-bold num">
                                    <span v-if="row.nilai_akhir !== null" :class="nilaiColor(row)">
                                        {{ fmt(row.nilai_akhir) }}
                                    </span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <StatusBadge v-if="row.keputusan === 'LULUS'" tone="success" :label="row.is_finalized ? 'LULUS' : 'LULUS (sementara)'" />
                                    <StatusBadge v-else-if="row.keputusan === 'TIDAK_LULUS'" tone="danger" :label="row.is_finalized ? 'TIDAK LULUS' : 'TIDAK LULUS (sementara)'" />
                                    <span v-else class="text-muted small">—</span>
                                </td>
                                <!-- Progres per tahap: titik = tahap (hijau selesai, biru sedang ditunggu, kuning tidak tercatat) -->
                                <td class="progress-cell">
                                    <div v-if="row.progress" class="step-dots">
                                        <span v-for="(st, key) in visibleSteps(row)" :key="key" class="step-dot" :class="`is-${st.state}`"
                                            :title="stepTooltip(key, st)">{{ steps[key].label }}</span>
                                    </div>
                                    <div v-if="row.progress" class="small mt-1 waiting-text" :class="row.progress.stage === 'selesai' ? 'text-success' : 'text-gray-800'">
                                        <i class="fa me-1" :class="row.progress.stage === 'selesai' ? 'fa-check-circle' : 'fa-hourglass-half text-primary'"></i>{{ row.progress.waiting }}
                                        <span v-if="row.progress.actor" class="text-muted">({{ row.progress.actor }})</span>
                                    </div>
                                </td>
                                <!-- FR.AK.14 (peserta LULUS): status materai + buka PDF (bermaterai kalau sudah dibubuhi) -->
                                <td class="text-center small" v-if="frAk14Rows.length" style="min-width:120px;">
                                    <template v-if="row.fr_ak_14">
                                        <div class="d-flex gap-1 justify-content-center align-items-center">
                                            <StatusBadge :tone="frAk14Badge[row.fr_ak_14.state].tone" :label="frAk14Badge[row.fr_ak_14.state].label" />
                                            <a v-if="row.fr_ak_14.state !== 'unsigned'"
                                               :href="`/admin/results/${exam_session.id}/fr-ak-14/${row.student_id}`"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary"
                                               :title="row.fr_ak_14.state === 'stamped' ? 'Lihat FR.AK.14 bermaterai' : 'Lihat FR.AK.14 (tanpa materai)'">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </div>
                                        <div v-if="row.fr_ak_14.state === 'stamped'" class="text-muted mt-1">{{ formatDate(row.fr_ak_14.stamped_at) }}</div>
                                        <div v-else-if="row.fr_ak_14.state === 'failed'" class="text-danger mt-1 failure-reason" :title="row.fr_ak_14.failure_reason">
                                            {{ row.fr_ak_14.failure_reason || 'Tanpa keterangan' }}
                                        </div>
                                    </template>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary border-0" title="Detail peserta: tahap, dokumen, riwayat" @click="openDetail(row)">
                                        <i class="fa fa-folder-open"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="20" class="text-center text-muted py-4">Belum ada peserta terdaftar di sesi ini.</td>
                            </tr>
                            <tr v-else-if="filteredRows.length === 0">
                                <td colspan="20" class="text-center text-muted py-4">
                                    Tidak ada peserta yang cocok dengan filter.
                                    <a href="#" @click.prevent="resetFilter">Tampilkan semua</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        </SectionCard>

    </div>

    <!-- Panel detail peserta: nilai, tahap, dokumen, riwayat -->
    <transition name="fade">
        <div v-if="detail" class="detail-backdrop" @click="closeDetail"></div>
    </transition>
    <transition name="slide">
        <aside v-if="detail" class="detail-panel shadow-lg" role="dialog" aria-label="Detail peserta">
            <div class="detail-header bg-white border-bottom">
                <div class="d-flex align-items-start gap-2">
                    <div class="flex-grow-1" style="min-width:0">
                        <h5 class="mb-1 fw-bold text-gray-800">
                            {{ detail.name }}
                            <span v-if="detail.attempt > 1" class="badge bg-warning text-gray-800 ms-1 small">Remidi</span>
                        </h5>
                        <div class="small text-muted">{{ detail.no_participant }} · Asesor: {{ detail.asesor_name || 'belum ditugaskan' }}</div>
                    </div>
                    <button type="button" class="btn btn-gray-100 border" title="Tutup" @click="closeDetail"><i class="fa fa-times"></i></button>
                </div>
                <div v-if="detail.progress" class="mt-2 small" :class="detail.progress.stage === 'selesai' ? 'text-success' : 'text-gray-800'">
                    <i class="fa me-1" :class="detail.progress.stage === 'selesai' ? 'fa-check-circle' : 'fa-hourglass-half text-primary'"></i>
                    <strong>{{ detail.progress.waiting }}</strong>
                    <span v-if="detail.progress.actor" class="text-muted"> ({{ detail.progress.actor }})</span>
                </div>
            </div>

            <div class="detail-body">
                <!-- Nilai -->
                <h6 class="detail-section">Nilai</h6>
                <table class="table table-sm table-bordered mb-3">
                    <tbody>
                        <tr v-if="exam_session.exam_id_pg">
                            <td class="w-50">Pilihan Ganda</td>
                            <td class="num">
                                <Link v-if="detail.grade_id_pg" :href="`/admin/reports/${detail.grade_id_pg}`">{{ fmt(detail.nilai_pg) }} <i class="fa fa-search-plus small"></i></Link>
                                <template v-else>{{ fmt(detail.nilai_pg) }}</template>
                            </td>
                        </tr>
                        <tr v-if="exam_session.exam_id_esai">
                            <td>Esai</td>
                            <td class="num">
                                <Link v-if="detail.grade_id_esai" :href="`/admin/reports/${detail.grade_id_esai}`">{{ fmt(detail.nilai_esai) }} <i class="fa fa-search-plus small"></i></Link>
                                <template v-else>{{ fmt(detail.nilai_esai) }}</template>
                            </td>
                        </tr>
                        <tr v-if="exam_session.has_wawancara">
                            <td>Wawancara</td>
                            <td class="num">
                                <a v-if="detail.wawancara" href="#" @click.prevent="showWawancara(detail)">{{ fmt(detail.nilai_wawancara) }} <i class="fa fa-search-plus small"></i></a>
                                <template v-else>{{ fmt(detail.nilai_wawancara) }}</template>
                            </td>
                        </tr>
                        <tr v-if="praktik_parts.length">
                            <td>Praktik/Keterampilan
                                <div v-for="p in praktik_parts" :key="p.bagian" class="small text-muted">
                                    {{ p.title }} ({{ p.bobot }}%): {{ fmt(detail.praktik?.[p.bagian] ?? null) }}
                                </div>
                            </td>
                            <td class="num">{{ fmt(detail.nilai_praktik) }}</td>
                        </tr>
                        <tr class="fw-bolder">
                            <td>Nilai Akhir</td>
                            <td class="num"><span :class="nilaiColor(detail)">{{ fmt(detail.nilai_akhir) }}</span></td>
                        </tr>
                        <tr>
                            <td>Keputusan</td>
                            <td>
                                <StatusBadge v-if="detail.keputusan === 'LULUS'" tone="success" :label="detail.is_finalized ? 'LULUS' : 'LULUS (sementara)'" />
                                <StatusBadge v-else-if="detail.keputusan === 'TIDAK_LULUS'" tone="danger" :label="detail.is_finalized ? 'TIDAK LULUS' : 'TIDAK LULUS (sementara)'" />
                                <span v-else class="text-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Tahap -->
                <h6 class="detail-section">Tahap</h6>
                <ul v-if="detail.progress" class="list-unstyled step-list mb-3">
                    <li v-for="(st, key) in visibleSteps(detail)" :key="key" class="d-flex gap-2">
                        <span class="step-icon" :class="`is-${st.state}`"><i class="fa" :class="stateIcon(st.state)"></i></span>
                        <div class="flex-grow-1">
                            <div class="fw-bolder text-gray-800">{{ steps[key].title }}</div>
                            <div class="small text-muted">
                                {{ stateText(st.state) }}<template v-if="st.note"> · {{ st.note }}</template><template v-if="st.by"> · {{ st.by }}</template><template v-if="st.at"> · {{ formatDate(st.at) }}</template>
                            </div>
                        </div>
                    </li>
                </ul>

                <!-- Dokumen -->
                <h6 class="detail-section">Dokumen</h6>
                <table class="table table-sm table-bordered mb-3 doc-table">
                    <tbody>
                        <tr>
                            <td class="w-50">Permohonan (FR.APL.01)</td>
                            <td>
                                <Link v-if="detail.progress?.docs.application_id" :href="`/admin/applications/${detail.progress.docs.application_id}?sesi=${exam_session.id}`" class="btn btn-sm btn-gray-100 border">
                                    <i class="fa fa-external-link-alt me-1"></i>Buka
                                </Link>
                                <span v-else class="text-muted small">Tidak ada permohonan</span>
                            </td>
                        </tr>
                        <tr>
                            <td>FR.AK.01</td>
                            <td>
                                <template v-if="detail.progress?.docs.application_id">
                                    <a :href="`/admin/applications/${detail.progress.docs.application_id}/fr-ak-01`" target="_blank" class="btn btn-sm btn-gray-100 border"><i class="fa fa-file-pdf me-1"></i>PDF</a>
                                    <span v-if="!detail.progress.docs.ak01_signed" class="small text-muted ms-1">belum TTD asesor</span>
                                </template>
                                <span v-else class="text-muted small">—</span>
                            </td>
                        </tr>
                        <tr v-if="exam_session.verifikasi_tuk">
                            <td>FR.TUK.06</td>
                            <td>
                                <a v-if="detail.progress?.docs.tuk" :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${detail.student_id}/pdf`" target="_blank" class="btn btn-sm btn-gray-100 border"><i class="fa fa-file-pdf me-1"></i>PDF</a>
                                <Link v-else :href="`/admin/penilaian/${exam_session.id}/verifikasi-tuk/${detail.student_id}`" class="small">Isi checklist</Link>
                            </td>
                        </tr>
                        <tr>
                            <td>FR.AK.05 (Laporan Asesmen)</td>
                            <td>
                                <a v-if="detail.progress?.docs.ak05_ready" :href="`/dokumen/laporan-asesmen/${exam_session.id}/${detail.progress.docs.asesor_id}/download`" target="_blank" class="btn btn-sm btn-gray-100 border"><i class="fa fa-file-pdf me-1"></i>PDF</a>
                                <span v-else-if="detail.progress?.docs.asesor_id" class="small text-muted fst-italic">Belum diisi oleh asesor</span>
                                <span v-else class="text-muted small">Asesor belum ditugaskan</span>
                            </td>
                        </tr>
                        <tr v-if="detail.fr_ak_14">
                            <td>FR.AK.14</td>
                            <td>
                                <StatusBadge :tone="frAk14Badge[detail.fr_ak_14.state].tone" :label="frAk14Badge[detail.fr_ak_14.state].label" />
                                <a v-if="detail.fr_ak_14.state !== 'unsigned'" :href="`/admin/results/${exam_session.id}/fr-ak-14/${detail.student_id}`" target="_blank" class="btn btn-sm btn-gray-100 border ms-1"><i class="fa fa-file-pdf me-1"></i>PDF</a>
                            </td>
                        </tr>
                        <template v-if="detail.is_finalized">
                            <tr>
                                <td>SP<div class="small text-muted text-break">{{ detail.sp_number || '—' }}</div></td>
                                <td>
                                    <a :href="`/admin/results/${exam_session.id}/download-sp/${detail.student_id}`" target="_blank" class="btn btn-sm btn-gray-100 border"><i class="fa fa-file-pdf me-1"></i>PDF</a>
                                    <span v-if="detail.sp_distributed_at" class="small text-success ms-1">terkirim</span>
                                </td>
                            </tr>
                            <tr>
                                <td>SK<div class="small text-muted text-break">{{ detail.sk_number || '—' }}</div></td>
                                <td>
                                    <a :href="`/admin/results/${exam_session.id}/download-sk/${detail.student_id}`" target="_blank" class="btn btn-sm btn-gray-100 border">Tanpa KAN</a>
                                    <a :href="`/admin/results/${exam_session.id}/download-sk/${detail.student_id}?kan=1`" target="_blank" class="btn btn-sm btn-gray-100 border ms-1">Dengan KAN</a>
                                </td>
                            </tr>
                            <tr v-if="detail.keputusan === 'LULUS'">
                                <td>Sertifikat<div class="small text-muted text-break">{{ detail.sertifikat_number || '—' }}</div></td>
                                <td>
                                    <a :href="`/admin/results/${exam_session.id}/download-sertifikat/${detail.student_id}`" target="_blank" class="btn btn-sm btn-gray-100 border">Tanpa KAN</a>
                                    <a :href="`/admin/results/${exam_session.id}/download-sertifikat/${detail.student_id}?kan=1`" target="_blank" class="btn btn-sm btn-gray-100 border ms-1">Dengan KAN</a>
                                    <div v-if="detail.distributed_at" class="small text-success mt-1">SK &amp; sertifikat terkirim</div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td>SP / SK / Sertifikat</td>
                            <td class="small text-muted">Tersedia setelah difinalisasi</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Riwayat -->
                <h6 class="detail-section">Riwayat</h6>
                <ol v-if="detail.progress?.timeline.length" class="timeline list-unstyled mb-0">
                    <li v-for="(e, i) in detail.progress.timeline" :key="i">
                        <div class="small text-muted num">{{ formatDate(e.at) }}</div>
                        <div class="fw-bolder text-gray-800">{{ e.title }}</div>
                        <div v-if="e.by || e.note" class="small text-muted">
                            <template v-if="e.by">{{ e.by }}</template><template v-if="e.by && e.note"> · </template><template v-if="e.note">{{ e.note }}</template>
                        </div>
                    </li>
                </ol>
                <p v-else class="small text-muted mb-0">Belum ada kejadian tercatat.</p>
            </div>
        </aside>
    </transition>
</template>

<script>
import LayoutAdmin from '../../../Layouts/Admin.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import BackBar from '../../../Components/Admin/BackBar.vue';
import SectionCard from '../../../Components/SectionCard.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref, onBeforeUnmount } from 'vue';
import Swal from 'sweetalert2';

// Teks & ikon per state tahap (App\Services\ResultProgressService)
const STATE_TEXT = {
    done: 'Selesai', pending: 'Belum', progress: 'Sedang berjalan', absent: 'Tidak ikut ujian',
    skipped: 'Tidak tercatat', na: 'Tidak berlaku',
};
const STATE_ICON = {
    done: 'fa-check', pending: 'fa-circle', progress: 'fa-hourglass-half', absent: 'fa-user-slash',
    skipped: 'fa-minus', na: 'fa-minus',
};

export default {
    layout: LayoutAdmin,
    components: { Head, Link, StatusBadge, BackBar, SectionCard },
    props: {
        exam_session: Object,
        rows:         Array,
        scheme:       Object,
        scheme_frozen: { type: Boolean, default: false },
        wawancara_tanpa_worksheet: { type: Boolean, default: false },
        // { [student_id]: [{ bagian, original_filename }] }
        tugas:        Object,
        show_tugas:   Boolean,
        // Judul bagian Ujian Praktik/Keterampilan { [bagian]: judul } — kosong bila hanya satu bagian
        tugas_parts:  { type: Object, default: () => ({}) },
        // Ujian Praktik/Keterampilan dinilai terpisah: [{ bagian, title, bobot }] — kosong = praktik dinilai lewat wawancara
        praktik_parts: { type: Array, default: () => [] },
        fr_ak_14_requires_materai: Boolean,
        // { key: { label, title, waiting } } urut tahap — ResultProgressService::STEPS
        steps:        { type: Object, default: () => ({}) },
    },

    setup(props) {
        const hasFinalized    = computed(() => props.rows.some(r => r.is_finalized));
        const finalizedCount  = computed(() => props.rows.filter(r => r.is_finalized).length);
        const spSentCount     = computed(() => props.rows.filter(r => r.sp_distributed_at).length);
        const finalSentCount  = computed(() => props.rows.filter(r => r.distributed_at).length);
        // Bisa lebih dari satu kalau peserta difinalisasi bertahap oleh orang berbeda
        const decisionMakers  = computed(() => [...new Set(props.rows.filter(r => r.is_finalized && r.finalized_by_name).map(r => r.finalized_by_name))]);

        // FR.AK.14 hanya ada untuk peserta LULUS yang sudah difinalisasi (row.fr_ak_14 bukan null)
        const frAk14Rows         = computed(() => props.rows.filter(r => r.fr_ak_14));
        const frAk14StampedCount = computed(() => frAk14Rows.value.filter(r => r.fr_ak_14.state === 'stamped').length);
        const frAk14SignedCount  = computed(() => frAk14Rows.value.filter(r => r.fr_ak_14.state !== 'unsigned').length);

        // State dari ParticipantResult::frAk14State()
        const frAk14Badge = {
            unsigned:   { tone: 'neutral',   label: 'Belum TTD' },
            no_materai: { tone: 'success',   label: 'Sudah TTD' },
            pending:    { tone: 'secondary', label: 'Belum Bermaterai' },
            processing: { tone: 'accent',    label: 'Diproses' },
            stamped:    { tone: 'success',   label: 'Bermaterai' },
            failed:     { tone: 'danger',    label: 'Gagal' },
        };

        const formatDate = (dt) => dt ? new Date(dt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

        const nilaiColor = (row) => {
            if (row.keputusan === 'LULUS') return 'text-success';
            if (row.keputusan === 'TIDAK_LULUS') return 'text-danger';
            return '';
        };

        // Format nilai dua desimal (Blueprint: 87.50), aman untuk null.
        const fmt = (v) => (v === null || v === undefined || v === '') ? '—' : Number(v).toFixed(2);
        const pct = (done, total) => total ? Math.min(100, Math.round(done / total * 100)) : 0;

        // ── Progres per peserta ────────────────────────────────────────────
        const stepKeys = computed(() => Object.keys(props.steps));
        const visibleSteps = (row) => Object.fromEntries(
            Object.entries(row.progress?.steps ?? {}).filter(([, st]) => st.state !== 'na'));
        const stateText = (state) => STATE_TEXT[state] ?? state;
        const stateIcon = (state) => STATE_ICON[state] ?? 'fa-circle';
        const stepTooltip = (key, st) => [
            `${props.steps[key]?.title}: ${stateText(st.state)}`,
            st.note, st.by, st.at ? formatDate(st.at) : null,
        ].filter(Boolean).join(' · ');
        const isAbsent = (row) => !!row.progress?.absent;
        // Rincian nilai praktik/keterampilan per bagian (tooltip kolom Praktik)
        const praktikTooltip = (row) => props.praktik_parts
            .map(p => `${p.title} (${p.bobot}%): ${row.praktik?.[p.bagian] ?? '—'}`)
            .join('\n');
        // "Belum dinilai" di kolom Esai/Wawancara: peserta ikut ujian tapi asesor belum menilai bagian itu
        const awaitsScore = (row, part) => {
            const st = row.progress?.steps?.nilai;
            return !!st && st.state === 'pending' && (st.note ?? '').includes(part);
        };

        // Ringkasan: per tahap, jumlah peserta selesai dari peserta yang tahapnya berlaku
        const summary = computed(() => stepKeys.value.map(key => {
            const applicable = props.rows.filter(r => r.progress && r.progress.steps[key].state !== 'na');
            return { key, total: applicable.length, done: applicable.filter(r => r.progress.steps[key].state === 'done').length };
        }).filter(s => s.total > 0));
        const absentCount = computed(() => props.rows.filter(isAbsent).length);

        // ── Filter ─────────────────────────────────────────────────────────
        const filter = reactive({ q: '', stage: '', step: '' });
        const rowNumber = computed(() => Object.fromEntries(props.rows.map((r, i) => [r.student_id, i + 1])));
        const stageOptions = computed(() => {
            const counts = {};
            props.rows.forEach(r => {
                const key = r.progress?.stage;
                if (key) counts[key] = (counts[key] ?? 0) + 1;
            });
            const options = [...stepKeys.value, 'selesai']
                .filter(key => counts[key])
                .map(key => ({ value: key, label: key === 'selesai' ? 'Selesai' : props.steps[key].waiting, count: counts[key] }));
            const extra = [
                ['absent', 'Tidak ikut ujian', props.rows.filter(isAbsent).length],
                ['tidak_lulus', 'Tidak lulus', props.rows.filter(r => r.keputusan === 'TIDAK_LULUS').length],
                ['remidi', 'Remidi', props.rows.filter(r => r.attempt > 1).length],
            ].filter(([, , n]) => n).map(([value, label, count]) => ({ value, label, count }));
            return [...options, ...extra];
        });
        const matchesStage = (r) => {
            if (!filter.stage) return true;
            if (filter.stage === 'absent') return isAbsent(r);
            if (filter.stage === 'tidak_lulus') return r.keputusan === 'TIDAK_LULUS';
            if (filter.stage === 'remidi') return r.attempt > 1;
            return r.progress?.stage === filter.stage;
        };
        const filteredRows = computed(() => {
            const q = filter.q.trim().toLowerCase();
            return props.rows.filter(r =>
                (!q || `${r.no_participant ?? ''} ${r.name ?? ''}`.toLowerCase().includes(q))
                && matchesStage(r)
                && (!filter.step || !['done', 'na'].includes(r.progress?.steps[filter.step]?.state)));
        });
        const isFiltered = computed(() => !!(filter.q || filter.stage || filter.step));
        const resetFilter = () => { filter.q = ''; filter.stage = ''; filter.step = ''; };
        const toggleStepFilter = (key) => {
            filter.step = filter.step === key ? '' : key;
            filter.stage = '';
        };

        // ── Panel detail ───────────────────────────────────────────────────
        const detailId = ref(null);
        const detail = computed(() => props.rows.find(r => r.student_id === detailId.value) ?? null);
        const onKey = (e) => { if (e.key === 'Escape') closeDetail(); };
        const openDetail = (row) => {
            detailId.value = row.student_id;
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', onKey);
        };
        const closeDetail = () => {
            detailId.value = null;
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        };
        onBeforeUnmount(closeDetail);

        // Rincian nilai wawancara: 4 kriteria (skala 0–100), Total = rata-ratanya — sama dengan portal asesor.
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const showWawancara = (row) => {
            const w = row.wawancara;
            const kriteria = [
                ['A', 'Gaya Wawancara', w.gaya_wawancara],
                ['B', 'Penguasaan Materi', w.penguasaan_materi],
                ['C', 'Kemampuan Menghadapi Pertanyaan', w.kemampuan_hadapi_pertanyaan],
                // Sesi dengan Ujian Praktik/Keterampilan: keterampilan dinilai lewat praktik, bukan (D) Worksheet
                ...(props.wawancara_tanpa_worksheet ? [] : [['D', 'Hasil Pengerjaan Worksheet Ujian Keterampilan', w.hasil_worksheet]]),
            ];
            const bobot = props.scheme?.bobot_wawancara;
            Swal.fire({
                title: 'Rincian Nilai Wawancara',
                width: 640,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#1f2937',
                html: `
                    <div class="text-start small mb-2">
                        <div><strong>${esc(row.no_participant)}</strong> — ${esc(row.name)}</div>
                        <div class="text-muted">Asesor: ${esc(w.asesor_name || '—')} · diperbarui ${esc(formatDate(w.updated_at))}</div>
                    </div>
                    <table class="table table-sm table-bordered text-start mb-2" style="font-size:0.85rem">
                        <thead><tr><th style="width:2.5rem">#</th><th>Kriteria</th><th class="text-end" style="width:6rem">Nilai</th></tr></thead>
                        <tbody>
                            ${kriteria.map(([k, label, v]) => `<tr><td>${k}</td><td style="white-space:normal">${label}</td><td class="text-end num">${fmt(v)}</td></tr>`).join('')}
                            <tr class="fw-bolder"><td></td><td>Total (rata-rata A–${props.wawancara_tanpa_worksheet ? 'C' : 'D'})</td><td class="text-end num">${fmt(w.total_nilai)}</td></tr>
                        </tbody>
                    </table>
                    ${bobot != null ? `<div class="text-start text-muted small mb-2">Bobot wawancara: ${esc(Number(bobot))}% dari nilai akhir.</div>` : ''}
                    <div class="text-start small"><strong>Catatan asesor:</strong>
                        <div style="white-space:pre-wrap">${w.catatan ? esc(w.catatan) : '<span class="text-muted">—</span>'}</div>
                    </div>`,
            });
        };

        const distributeSp = () => {
            Swal.fire({
                title: 'Kirim SP ke Peserta?',
                html: 'Surat Pernyataan (SP) akan dikirim ke email peserta yang sudah difinalisasi, supaya mereka bisa memeriksa & mengajukan revisi (mis. typo nama) sebelum SK &amp; Sertifikat resmi diterbitkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonText: 'Batal',
                confirmButtonText: 'Ya, Kirim SP',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/distribute-sp`);
                }
            });
        };

        const distribute = () => {
            const belumSp = finalizedCount.value - spSentCount.value;
            Swal.fire({
                title: 'Kirim SK & Sertifikat ke Peserta?',
                html: 'SK dan Sertifikat akan dikirim ke email dan dashboard peserta yang sudah difinalisasi.'
                    + (belumSp > 0 ? `<br><br><span class="text-warning"><i class="fa fa-exclamation-triangle"></i> ${belumSp} peserta belum dikirimi SP — pastikan sudah diberi kesempatan mengajukan revisi sebelum lanjut.</span>` : ''),
                icon: 'question',
                input: 'checkbox',
                inputValue: 1,
                inputPlaceholder: 'Sertakan logo KAN pada SK & Sertifikat',
                showCancelButton: true,
                confirmButtonColor: '#1f2937',
                cancelButtonText: 'Batal',
                confirmButtonText: 'Ya, Kirim',
            }).then(result => {
                if (result.isConfirmed) {
                    router.post(`/admin/results/${props.exam_session.id}/distribute`, {
                        kan: !!result.value,
                    });
                }
            });
        };

        return {
            hasFinalized, finalizedCount, spSentCount, finalSentCount, decisionMakers,
            frAk14Rows, frAk14StampedCount, frAk14SignedCount, frAk14Badge, formatDate,
            nilaiColor, fmt, pct, showWawancara, distributeSp, distribute,
            visibleSteps, stateText, stateIcon, stepTooltip, isAbsent, awaitsScore, praktikTooltip, summary, absentCount,
            filter, rowNumber, stageOptions, filteredRows, isFiltered, resetFilter, toggleStepFilter,
            detail, openDetail, closeDetail,
        };
    },
}
</script>

<style scoped>
/* Angka nilai: tabular-nums agar dua-desimal sejajar antar baris (Blueprint §5). */
.num {
    font-variant-numeric: tabular-nums;
    font-feature-settings: "tnum";
}

/* Nama + asesor boleh turun baris (Volt: .table td nowrap), jangan melebarkan tabel. */
.name-cell {
    white-space: normal;
    overflow-wrap: anywhere;
    min-width: 160px;
    max-width: 220px;
    line-height: 1.3;
}

/* Alasan gagal materai bisa panjang (pesan Peruri) — potong 2 baris, lengkapnya di tooltip. */
.failure-reason {
    max-width: 220px;
    margin-inline: auto;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.participant-name { text-decoration: none; }
.participant-name:hover { text-decoration: underline; }
.score-note { font-size: .75rem; color: #6b7280; font-style: italic; white-space: nowrap; }

/* ── Ringkasan progres ─────────────────────────────────────────────── */
.summary-step {
    min-width: 92px; padding: .5rem .65rem; text-align: left;
    background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem;
}
.summary-step:hover { border-color: #93c5fd; }
.summary-step.is-active { border-color: #1f2937; box-shadow: 0 0 0 2px rgba(31, 41, 55, .15); }
.summary-step.is-done .summary-count { color: #059669; }
.summary-step.is-warning .summary-count { color: #b45309; }
.summary-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; color: #6b7280; }
.summary-count { font-size: 1.1rem; font-weight: 700; color: #1f2937; line-height: 1.2; }
.summary-bar { height: 4px; margin-top: .3rem; }

.filter-search { width: 260px; max-width: 100%; }
.filter-stage { width: 300px; max-width: 100%; }

/* ── Kolom Progres ─────────────────────────────────────────────────── */
.progress-cell { min-width: 195px; max-width: 250px; white-space: normal; }
.step-dots { display: flex; flex-wrap: wrap; gap: 3px; }
.step-dot {
    font-size: .62rem; font-weight: 600; line-height: 1; padding: .22rem .32rem; border-radius: .25rem;
    border: 1px solid #d1d5db; color: #9ca3af; background: #fff; cursor: default;
}
.step-dot.is-done     { background: #10b981; border-color: #10b981; color: #fff; }
.step-dot.is-pending  { border-color: #9ca3af; color: #6b7280; }
.step-dot.is-progress { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; }
.step-dot.is-absent   { background: #fee2e2; border-color: #f87171; color: #b91c1c; }
.step-dot.is-skipped  { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.waiting-text { white-space: normal; line-height: 1.3; }

/* ── Panel detail ──────────────────────────────────────────────────── */
.detail-backdrop { position: fixed; inset: 0; background: rgba(0, 0, 0, .4); z-index: 1040; }
.detail-panel {
    position: fixed; top: 0; right: 0; height: 100vh; width: 560px; max-width: 100vw;
    background: #fff; z-index: 1050; display: flex; flex-direction: column;
}
.detail-header { padding: 1rem 1.25rem; flex-shrink: 0; }
.detail-body { flex: 1 1 auto; overflow-y: auto; padding: 1rem 1.25rem 2rem; }
.detail-section {
    font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    color: #6b7280; margin: .5rem 0 .5rem;
}
.detail-body td { white-space: normal; vertical-align: middle; }
.doc-table td:first-child { font-weight: 600; color: #374151; }

.step-list li { padding: .3rem 0; }
.step-icon {
    width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; margin-top: 2px;
    display: inline-flex; align-items: center; justify-content: center; font-size: .65rem;
    border: 1px solid #d1d5db; color: #9ca3af; background: #fff;
}
.step-icon.is-done     { background: #10b981; border-color: #10b981; color: #fff; }
.step-icon.is-progress { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; }
.step-icon.is-absent   { background: #fee2e2; border-color: #f87171; color: #b91c1c; }
.step-icon.is-skipped  { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
.step-icon.is-pending .fa { font-size: .4rem; }

.timeline { border-left: 2px solid #e5e7eb; margin-left: .4rem; padding-left: 1rem; }
.timeline li { position: relative; padding-bottom: .75rem; }
.timeline li::before {
    content: ''; position: absolute; left: calc(-1rem - 6px); top: .35rem;
    width: 10px; height: 10px; border-radius: 50%; background: #3b82f6; border: 2px solid #fff;
}

.slide-enter-active, .slide-leave-active { transition: transform .25s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
.fade-enter-active, .fade-leave-active { transition: opacity .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
