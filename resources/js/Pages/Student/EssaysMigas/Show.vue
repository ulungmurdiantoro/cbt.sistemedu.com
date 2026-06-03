<template>
  <Head>
    <title>Ujian Essay Migas - Aplikasi Ujian Online</title>
  </Head>

  <div class="row mb-5">
    <div class="col-md-12">
      <div class="card border-0 shadow" style="border-radius:16px;">
        <div class="card-header bg-white" style="border-top-left-radius:16px;border-top-right-radius:16px;">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">Ujian Essay Migas (Semua Soal)</h5>
                <span v-if="uploadDone" class="badge rounded-pill bg-success px-3 py-2">
                  <i class="fa fa-check me-1"></i> File sudah diupload
                </span>
                <span v-else class="badge rounded-pill bg-warning text-dark px-3 py-2">
                  <i class="fa fa-exclamation-circle me-1"></i> File belum diupload
                </span>
              </div>
              <div class="mt-2 p-2 px-3 d-flex align-items-center gap-2" style="border-radius:12px;border:1px solid rgba(0,0,0,.08);">
                <i class="fa fa-info-circle"></i>
                <span class="fw-semibold">
                  <template v-if="hasEssayQuestion">
                    Sebagian soal berbentuk <strong>uraian</strong>. Jawaban diketik langsung pada editor.
                  </template>
                  <template v-else>
                    Jawaban dikumpulkan dalam <strong>1 file</strong> (PDF/DOC/DOCX/ZIP, dll).
                  </template>
                </span>
              </div>
            </div>
            <div class="text-end">
              <VueCountdown :time="duration" @progress="handleChangeDuration" @end="timeUp = true" v-slot="{ hours, minutes, seconds }">
                <span class="badge bg-info p-2" style="border-radius:12px;">
                  <i class="fa fa-clock"></i> {{ hours }} jam, {{ minutes }} menit, {{ seconds }} detik.
                </span>
              </VueCountdown>
            </div>
          </div>
        </div>

        <div class="card-body">
          <!-- LIST SOAL -->
          <div
            v-for="(essayWrap, index) in all_essays"
            :key="essayWrap.id || essayWrap.essay_id || index"
            class="mb-4"
          >
            <div class="p-3" style="border:1px solid rgba(0,0,0,.08);border-radius:14px;">
              <div class="fw-bold mb-1">Soal No. {{ index + 1 }}</div>
              <div v-html="essayWrap.question ?? essayWrap?.essay?.question ?? '-'" style="user-select:none;"></div>

              <!-- Soal uraian: editor teks -->
              <div v-if="getIsEssay(essayWrap)" class="mt-4">
                <div class="fw-semibold mb-2">Jawaban Anda</div>
                <QuillEditor
                  :ref="el => setEditorRef(essayWrap, el)"
                  v-model:content="answers[getEssayId(essayWrap)]"
                  contentType="html"
                  theme="snow"
                  :options="{
                    modules: { toolbar: [['bold','italic','underline'],[{align:[]}],[{header:[1,2,3,false]}],[{list:'ordered'},{list:'bullet'}]] },
                    placeholder: 'Ketik jawaban Anda di sini...'
                  }"
                  style="min-height:300px;width:100%;border-radius:12px;"
                  @update:content="val => (answers[getEssayId(essayWrap)] = val)"
                  @ready="disablePaste"
                />
                <button
                  @click="submitAnswer(essayWrap)"
                  class="btn btn-md btn-info border-0 shadow mt-3 text-white"
                  style="border-radius:12px;"
                  :disabled="savingAnswer[getEssayId(essayWrap)]"
                >
                  <span v-if="savingAnswer[getEssayId(essayWrap)]"><i class="fa fa-spinner fa-spin me-2"></i> Menyimpan...</span>
                  <span v-else><i class="fa fa-save me-2"></i> Simpan Jawaban</span>
                </button>
                <div v-if="savedAnswer[getEssayId(essayWrap)]" class="mt-2">
                  <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa fa-check me-1"></i> Tersimpan</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Upload 1 file (hanya jika tidak ada soal uraian) -->
          <div v-if="!hasEssayQuestion" class="mt-4 p-3" style="border:1px solid rgba(0,0,0,.1);border-radius:14px;">
            <div class="fw-bold mb-2">Upload Jawaban Ujian (1 File)</div>
            <input type="file" class="form-control" @change="onFileChange" />
            <div class="small text-muted mt-2" v-if="selectedFile">
              File dipilih: <strong>{{ selectedFile.name }}</strong> ({{ formatBytes(selectedFile.size) }})
            </div>
            <div class="small text-muted mt-2" v-else>Belum ada file dipilih.</div>

            <button
              class="btn btn-info text-white shadow-sm mt-3"
              style="border-radius:12px;"
              :disabled="!selectedFile || uploading"
              @click="uploadAnswerFile"
            >
              <span v-if="uploading"><i class="fa fa-spinner fa-spin me-2"></i> Upload...</span>
              <span v-else><i class="fa fa-upload me-2"></i> Upload Jawaban</span>
            </button>

            <div v-if="uploading" class="mt-3">
              <div class="progress" style="height:10px;border-radius:999px;">
                <div class="progress-bar" :style="{ width: uploadProgress + '%' }"></div>
              </div>
              <div class="d-flex justify-content-between small text-muted mt-1">
                <div>{{ uploadProgress }}%</div>
                <div v-if="totalBytes > 0">{{ formatBytes(uploadedBytes) }} / {{ formatBytes(totalBytes) }}</div>
                <div v-else>{{ formatBytes(uploadedBytes) }} terupload</div>
              </div>
            </div>

            <div v-if="uploadDone" class="mt-2">
              <span class="badge bg-success rounded-pill px-3 py-2"><i class="fa fa-check me-1"></i> File berhasil diupload</span>
            </div>
            <div v-if="uploadError" class="mt-2">
              <span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa fa-times me-1"></i> {{ uploadError }}</span>
              <div v-if="uploadErrorDetail" class="small text-muted mt-2">
                <div><strong>Status:</strong> {{ uploadErrorDetail.status }}</div>
                <pre class="mb-0">{{ uploadErrorDetail.detail }}</pre>
              </div>
            </div>

            <div v-if="uploadedFile" class="mt-3">
              <div class="small text-muted">
                File tersimpan: <strong>{{ uploadedFile.name }}</strong>
                <span v-if="uploadedFile.size">({{ formatBytes(uploadedFile.size) }})</span>
              </div>
              <a v-if="uploadedFile.url" :href="uploadedFile.url" target="_blank" class="btn btn-outline-info btn-sm mt-2" style="border-radius:12px;">
                <i class="fa fa-eye me-1"></i> Lihat / Unduh
              </a>
            </div>
          </div>
        </div>

        <div class="card-footer bg-white" style="border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
          <button @click="showEndModal = true" class="btn btn-danger btn-md border-0 shadow w-100" style="border-radius:12px;">
            Akhiri Ujian
          </button>
        </div>
      </div>
    </div>
  </div>

  <ExamEndModals
    v-model="showEndModal"
    :time-up="timeUp"
    :answered="uploadDone ? 1 : 0"
    :total="1"
    @confirm="endExam"
  />
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, computed, reactive } from 'vue'
import VueCountdown from '@chenfengyuan/vue-countdown'
import axios from 'axios'
import Swal from 'sweetalert2'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { useExamTimer } from '../../../Composables/useExamTimer'
import ExamEndModals from '../../../Components/Exam/ExamEndModals.vue'

export default {
  layout: LayoutStudent,
  components: { Head, VueCountdown, QuillEditor, ExamEndModals },
  props: {
    id:               Number,
    page:             Number,
    exam_id:          [Number, String],
    exam_session_id:  [Number, String],
    all_essays:       Array,
    duration:         Object,
    existing_file:    Object,
  },

  setup(props) {
    const { duration, handleChangeDuration } = useExamTimer(
      props.duration?.duration ?? 0,
      props.duration?.id,
      '/student/essay-migas-duration/update'
    )

    const examId    = computed(() => parseInt(String(props.exam_id ?? ''), 10) || null)
    const sessionId = computed(() => parseInt(String(props.exam_session_id ?? ''), 10) || null)

    const getIsEssay  = (w) => Number(w?.is_essay ?? w?.essay?.is_essay ?? 0) === 1
    const getEssayId  = (w) => w?.essay_id ?? w?.id ?? w?.essay?.id

    const hasEssayQuestion = computed(() => (props.all_essays || []).some(getIsEssay))

    const answers      = reactive({})
    const savingAnswer = reactive({})
    const savedAnswer  = reactive({})
    const editors      = ref({})

    ;(props.all_essays || []).forEach((item) => {
      const id = getEssayId(item)
      answers[id]      = item?.answer ?? item?.student_answer ?? ''
      savingAnswer[id] = false
      savedAnswer[id]  = false
    })

    const setEditorRef = (essayWrap, el) => {
      const id = getEssayId(essayWrap)
      if (id) editors.value[id] = el
    }

    const disablePaste = (quill) => {
      if (!quill) return
      quill.root.addEventListener('paste', (e) => e.preventDefault())
    }

    async function submitAnswer(essayWrap) {
      const essayId = getEssayId(essayWrap)
      if (!examId.value || !sessionId.value || !essayId) {
        Swal.fire({ title: 'Error!', text: 'exam_id / exam_session_id / essay_id tidak valid.', icon: 'error' })
        return
      }
      savingAnswer[essayId] = true
      savedAnswer[essayId]  = false
      try {
        await axios.post('/student/essay-migas-answer-text', {
          exam_id: examId.value, exam_session_id: sessionId.value,
          exam_group_id: props.id, essay_id: essayId,
          answer: answers[essayId] || '', duration: duration.value,
        })
        savedAnswer[essayId] = true
        Swal.fire({ title: 'Tersimpan!', text: 'Jawaban berhasil disimpan.', icon: 'success', timer: 1500, showConfirmButton: false })
      } catch (error) {
        Swal.fire({ title: 'Error!', text: error?.response?.data?.message || 'Gagal menyimpan jawaban.', icon: 'error' })
      } finally {
        savingAnswer[essayId] = false
      }
    }

    // ── Upload file ──────────────────────────────────────────────────────────
    const selectedFile   = ref(null)
    const uploading      = ref(false)
    const uploadProgress = ref(0)
    const uploadedBytes  = ref(0)
    const totalBytes     = ref(0)
    const uploadDone     = ref(!!props.existing_file)
    const uploadError    = ref(null)
    const uploadErrorDetail = ref(null)
    const uploadedFile   = ref(props.existing_file || null)

    const onFileChange = (e) => {
      const file = e.target.files?.[0] || null
      if (!file) return
      selectedFile.value   = file
      uploadProgress.value = 0
      uploadedBytes.value  = 0
      totalBytes.value     = file.size
      uploadError.value    = null
      uploadErrorDetail.value = null
    }

    async function uploadAnswerFile() {
      if (!selectedFile.value || !examId.value || !sessionId.value) return
      uploading.value   = true
      uploadError.value = null
      uploadErrorDetail.value = null
      try {
        const formData = new FormData()
        formData.append('exam_id', String(examId.value))
        formData.append('exam_session_id', String(sessionId.value))
        formData.append('duration', String(duration.value))
        formData.append('file', selectedFile.value)

        const res = await axios.post('/student/essay-migas-answer', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (ev) => {
            uploadedBytes.value  = ev.loaded || 0
            totalBytes.value     = ev.total || selectedFile.value?.size || 0
            uploadProgress.value = totalBytes.value
              ? Math.min(99, Math.round((uploadedBytes.value * 100) / totalBytes.value))
              : 0
          },
        })
        uploadDone.value     = true
        uploadProgress.value = 100
        uploadedFile.value   = {
          name: res?.data?.file?.name || selectedFile.value.name,
          path: res?.data?.file?.path || null,
          url:  res?.data?.file?.url  || null,
          size: res?.data?.file?.size || selectedFile.value.size,
        }
        Swal.fire({ title: 'Berhasil!', text: 'File jawaban berhasil diupload.', icon: 'success', timer: 2000, showConfirmButton: false })
      } catch (e) {
        const status = e?.response?.status || 'UNKNOWN'
        const data   = e?.response?.data
        const messages = {
          419: 'CSRF token mismatch. Silakan refresh lalu coba lagi.',
          422: 'Validasi gagal. Pastikan semua field terkirim.',
          401: 'Belum login / sesi habis.',
          403: 'Tidak punya akses.',
          404: 'Endpoint tidak ditemukan.',
          500: 'Server error. Cek laravel.log.',
        }
        uploadError.value       = data?.message || messages[status] || 'Gagal upload file.'
        uploadErrorDetail.value = { status, detail: JSON.stringify(data || {}, null, 2) }
        Swal.fire({ title: 'Error!', text: uploadError.value, icon: 'error' })
      } finally {
        uploading.value = false
      }
    }

    const formatBytes = (bytes) => {
      if (!bytes && bytes !== 0) return '0 B'
      const k = 1024, sizes = ['B','KB','MB','GB','TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return `${(bytes / Math.pow(k, i)).toFixed(i === 0 ? 0 : 1)} ${sizes[i]}`
    }

    // ── Modal & akhiri ───────────────────────────────────────────────────────
    const showEndModal = ref(false)
    const timeUp       = ref(false)

    function endExam() {
      if (!examId.value || !sessionId.value) {
        Swal.fire({ title: 'Error!', text: 'exam_id / exam_session_id tidak valid.', icon: 'error' })
        return
      }
      router.post('/student/essay-migas-end', {
        exam_id: examId.value, exam_session_id: sessionId.value, exam_group_id: props.id,
      })
      Swal.fire({ title: 'Ujian Selesai!', icon: 'success', showConfirmButton: false, timer: 4000 })
    }

    return {
      duration, handleChangeDuration,
      getIsEssay, getEssayId, hasEssayQuestion,
      answers, savingAnswer, savedAnswer, setEditorRef, disablePaste, submitAnswer,
      selectedFile, uploading, uploadProgress, uploadedBytes, totalBytes,
      uploadDone, uploadError, uploadErrorDetail, uploadedFile, onFileChange, uploadAnswerFile, formatBytes,
      showEndModal, timeUp, endExam,
    }
  },
}
</script>
