<template>
  <Head>
    <title>Ujian - Semua Soal (Upload 1 File) - Aplikasi Ujian Online</title>
  </Head>

  <div class="row mb-5">
    <div class="col-md-12">
      <div class="card border-0 shadow" style="border-radius: 16px;">
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

              <div
                class="mt-2 p-2 px-3 d-flex align-items-center gap-2"
                style="border-radius: 12px; border: 1px solid rgba(0,0,0,.08);"
              >
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
              <VueCountdown
                :time="durationMs"
                @progress="handleChangeDuration"
                @end="showModalEndTimeExam = true"
                v-slot="{ hours, minutes, seconds }"
              >
                <span class="badge bg-info p-2" style="border-radius: 12px;">
                  <i class="fa fa-clock"></i>
                  {{ hours }} jam, {{ minutes }} menit, {{ seconds }} detik.
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
            <div class="p-3" style="border: 1px solid rgba(0,0,0,.08); border-radius: 14px;">
              <div class="fw-bold mb-1">Soal No. {{ index + 1 }}</div>

              <div
                v-html="essayWrap.question ?? essayWrap?.essay?.question ?? '-'"
                style="user-select:none;"
              ></div>

              <!-- JIKA SOAL URAIAN -->
              <div v-if="getIsEssay(essayWrap)" class="mt-4">
                <div class="fw-semibold mb-2">Jawaban Anda</div>

                <QuillEditor
                  :ref="el => setEditorRef(essayWrap, el)"
                  v-model:content="answers[getEssayId(essayWrap)]"
                  contentType="html"
                  theme="snow"
                  :options="{
                    modules: {
                      toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ align: [] }],
                        [{ header: [1, 2, 3, false] }],
                        [{ list: 'ordered' }, { list: 'bullet' }]
                      ]
                    },
                    placeholder: 'Ketik jawaban Anda di sini...'
                  }"
                  style="min-height: 300px; width: 100%; border-radius: 12px;"
                  @update:content="val => (answers[getEssayId(essayWrap)] = val)"
                  @ready="disablePaste"
                />

                <button
                  @click="submitAnswer(essayWrap)"
                  class="btn btn-md btn-info border-0 shadow mt-3 text-white"
                  style="border-radius: 12px;"
                  :disabled="savingAnswer[getEssayId(essayWrap)]"
                >
                  <span v-if="savingAnswer[getEssayId(essayWrap)]">
                    <i class="fa fa-spinner fa-spin me-2"></i> Menyimpan...
                  </span>
                  <span v-else>
                    <i class="fa fa-save me-2"></i> Simpan Jawaban
                  </span>
                </button>

                <div v-if="savedAnswer[getEssayId(essayWrap)]" class="mt-2">
                  <span class="badge bg-success rounded-pill px-3 py-2">
                    <i class="fa fa-check me-1"></i> Jawaban berhasil disimpan
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- UPLOAD 1 FILE HANYA JIKA TIDAK ADA SOAL ESSAY -->
          <div
            v-if="!hasEssayQuestion"
            class="mt-4 p-3"
            style="border:1px solid rgba(0,0,0,.1); border-radius:14px;"
          >
            <div class="fw-bold mb-2">Upload Jawaban Ujian (1 File)</div>

            <input type="file" class="form-control" @change="onFileChange" />

            <div class="small text-muted mt-2" v-if="selectedFile">
              File dipilih: <strong>{{ selectedFile.name }}</strong> ({{ formatBytes(selectedFile.size) }})
            </div>
            <div class="small text-muted mt-2" v-else>
              Belum ada file dipilih.
            </div>

            <button
              class="btn btn-info text-white shadow-sm mt-3"
              style="border-radius:12px;"
              :disabled="!selectedFile || uploading"
              @click="uploadAnswerFile"
            >
              <span v-if="uploading"><i class="fa fa-spinner fa-spin me-2"></i> Upload...</span>
              <span v-else><i class="fa fa-upload me-2"></i> Upload Jawaban</span>
            </button>

            <div class="mt-3" v-if="uploading">
              <div class="progress" style="height:10px; border-radius:999px;">
                <div class="progress-bar" :style="{ width: uploadProgress + '%' }"></div>
              </div>
              <div class="d-flex justify-content-between small text-muted mt-1">
                <div>{{ uploadProgress }}%</div>
                <div v-if="totalBytes > 0">{{ formatBytes(uploadedBytes) }} / {{ formatBytes(totalBytes) }}</div>
                <div v-else>{{ formatBytes(uploadedBytes) }} terupload</div>
              </div>
            </div>

            <div class="mt-2" v-if="uploadDone">
              <span class="badge bg-success rounded-pill px-3 py-2">
                <i class="fa fa-check me-1"></i> File berhasil diupload
              </span>
            </div>

            <div class="mt-2" v-if="uploadError">
              <span class="badge bg-danger rounded-pill px-3 py-2">
                <i class="fa fa-times me-1"></i> {{ uploadError }}
              </span>

              <div v-if="uploadErrorDetail" class="small text-muted mt-2">
                <div><strong>Status:</strong> {{ uploadErrorDetail.status }}</div>
                <div><strong>Detail:</strong></div>
                <pre class="mb-0">{{ uploadErrorDetail.detail }}</pre>
              </div>
            </div>

            <div class="mt-3" v-if="uploadedFile">
              <div class="small text-muted">
                File tersimpan:
                <strong>{{ uploadedFile.name }}</strong>
                <span v-if="uploadedFile.size">({{ formatBytes(uploadedFile.size) }})</span>
              </div>

              <a
                v-if="uploadedFile.url"
                :href="uploadedFile.url"
                target="_blank"
                class="btn btn-outline-info btn-sm mt-2"
                style="border-radius:12px;"
              >
                <i class="fa fa-eye me-1"></i> Lihat / Unduh
              </a>
            </div>
          </div>
        </div>

        <div class="card-footer bg-white" style="border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
          <button
            @click="showModalEndExam = true"
            class="btn btn-danger btn-md border-0 shadow w-100"
            style="border-radius: 12px;"
          >
            Akhiri Ujian
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL END EXAM -->
  <div
    v-if="showModalEndExam"
    class="modal fade"
    :class="{ show: showModalEndExam }"
    tabindex="-1"
    aria-hidden="true"
    style="display:block;"
    role="dialog"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Akhiri Ujian ?</h5></div>
        <div class="modal-body">
          Setelah mengakhiri ujian, Anda tidak dapat kembali ke ujian ini lagi. Yakin akan mengakhiri ujian?
        </div>
        <div class="modal-footer">
          <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya, Akhiri</button>
          <button @click.prevent="showModalEndExam = false" type="button" class="btn btn-secondary">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL TIME END -->
  <div
    v-if="showModalEndTimeExam"
    class="modal fade"
    :class="{ show: showModalEndTimeExam }"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
    tabindex="-1"
    aria-hidden="true"
    style="display:block;"
    role="dialog"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Waktu Habis !</h5></div>
        <div class="modal-body">
          Waktu ujian sudah berakhir!. Klik <strong class="fw-bold">Ya</strong> untuk mengakhiri ujian.
        </div>
        <div class="modal-footer">
          <button @click.prevent="endExam" type="button" class="btn btn-primary">Ya</button>
        </div>
      </div>
    </div>
  </div>
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

export default {
  layout: LayoutStudent,
  components: { Head, VueCountdown, QuillEditor },
  props: {
    id: Number,
    page: Number,
    exam_id: [Number, String],
    exam_session_id: [Number, String],
    all_essays: Array,
    duration: Object,
    existing_file: Object,
  },

  setup(props) {
    const durationMs = ref(props.duration?.duration ?? 0)

    const handleChangeDuration = () => {
      durationMs.value = durationMs.value - 1000
    }

    const examId = computed(() => {
      const v = parseInt(String(props.exam_id ?? ''), 10)
      return Number.isInteger(v) ? v : null
    })

    const sessionId = computed(() => {
      const v = parseInt(String(props.exam_session_id ?? ''), 10)
      return Number.isInteger(v) ? v : null
    })

    const getIsEssay = (essayWrap) => {
      return Number(essayWrap?.is_essay ?? essayWrap?.essay?.is_essay ?? 0) === 1
    }

    const getEssayId = (essayWrap) => {
      return essayWrap?.essay_id ?? essayWrap?.id ?? essayWrap?.essay?.id
    }

    const hasEssayQuestion = computed(() => {
      return (props.all_essays || []).some(item => getIsEssay(item))
    })

    const answers = reactive({})
    const savingAnswer = reactive({})
    const savedAnswer = reactive({})
    const editors = ref({})

    ;(props.all_essays || []).forEach((item) => {
      const essayId = getEssayId(item)
      answers[essayId] = item?.answer ?? item?.student_answer ?? ''
      savingAnswer[essayId] = false
      savedAnswer[essayId] = false
    })

    const setEditorRef = (essayWrap, el) => {
      const essayId = getEssayId(essayWrap)
      if (essayId) editors.value[essayId] = el
    }

    const disablePaste = (quill) => {
      if (!quill) return
      quill.root.addEventListener('paste', (e) => {
        e.preventDefault()
      })
    }

    const submitAnswer = async (essayWrap) => {
      const essayId = getEssayId(essayWrap)

      if (!examId.value || !sessionId.value || !essayId) {
        Swal.fire({
          title: 'Error!',
          text: 'exam_id / exam_session_id / essay_id tidak valid.',
          icon: 'error'
        })
        return
      }

      savingAnswer[essayId] = true
      savedAnswer[essayId] = false

      try {
        const payload = {
          exam_id: examId.value,
          exam_session_id: sessionId.value,
          exam_group_id: props.id,
          essay_id: essayId,
          answer: answers[essayId] || '',
          duration: durationMs.value,
        }

        await axios.post('/student/essay-migas-answer-text', payload)

        savedAnswer[essayId] = true

        Swal.fire({
          title: 'Success!',
          text: 'Jawaban berhasil disimpan.',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false
        })
      } catch (error) {
        Swal.fire({
          title: 'Error!',
          text: error?.response?.data?.message || 'Gagal menyimpan jawaban.',
          icon: 'error'
        })
      } finally {
        savingAnswer[essayId] = false
      }
    }

    const selectedFile = ref(null)
    const uploading = ref(false)
    const uploadProgress = ref(0)
    const uploadedBytes = ref(0)
    const totalBytes = ref(0)
    const uploadDone = ref(!!props.existing_file)
    const uploadError = ref(null)
    const uploadErrorDetail = ref(null)
    const uploadedFile = ref(props.existing_file || null)

    const onFileChange = (e) => {
      const file = e.target.files?.[0] || null
      if (!file) return

      selectedFile.value = file
      uploadProgress.value = 0
      uploadedBytes.value = 0
      totalBytes.value = file.size
      uploadError.value = null
      uploadErrorDetail.value = null
    }

    const uploadAnswerFile = async () => {
      if (!selectedFile.value) return

      if (!examId.value || !sessionId.value) {
        uploadError.value = 'exam_id / exam_session_id tidak valid (bukan integer).'
        uploadErrorDetail.value = {
          status: 422,
          detail: JSON.stringify({
            exam_id: props.exam_id,
            exam_session_id: props.exam_session_id,
            resolved: { examId: examId.value, sessionId: sessionId.value }
          }, null, 2),
        }
        return
      }

      uploading.value = true
      uploadError.value = null
      uploadErrorDetail.value = null

      try {
        const formData = new FormData()
        formData.append('exam_id', String(examId.value))
        formData.append('exam_session_id', String(sessionId.value))
        formData.append('duration', String(durationMs.value))
        formData.append('file', selectedFile.value)

        const res = await axios.post('/student/essay-migas-answer', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (ev) => {
            uploadedBytes.value = ev.loaded || 0

            if (ev.total) {
              totalBytes.value = ev.total
              uploadProgress.value = Math.round((ev.loaded * 100) / ev.total)
            } else {
              totalBytes.value = selectedFile.value?.size || 0
              uploadProgress.value = totalBytes.value
                ? Math.min(99, Math.round((uploadedBytes.value * 100) / totalBytes.value))
                : 0
            }
          }
        })

        uploadDone.value = true
        uploadProgress.value = 100

        uploadedFile.value = {
          name: res?.data?.file?.name || selectedFile.value.name,
          path: res?.data?.file?.path || null,
          url: res?.data?.file?.url || null,
          size: res?.data?.file?.size || selectedFile.value.size,
        }

        Swal.fire({
          title: 'Success!',
          text: 'File jawaban berhasil diupload.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        })
      } catch (e) {
        const status = e?.response?.status || 'UNKNOWN'
        const data = e?.response?.data

        uploadError.value =
          data?.message ||
          (status === 419 ? 'CSRF token mismatch (419). Silakan refresh lalu coba lagi.' :
          status === 422 ? 'Validasi gagal (422). Pastikan exam_id, exam_session_id, dan file terkirim.' :
          status === 401 ? 'Belum login / sesi habis (401).' :
          status === 403 ? 'Tidak punya akses (403).' :
          status === 404 ? 'Endpoint tidak ditemukan (404).' :
          status === 500 ? 'Server error (500). Cek laravel.log.' :
          'Gagal upload file.')

        uploadErrorDetail.value = {
          status,
          detail: JSON.stringify(data || {}, null, 2)
        }

        Swal.fire({
          title: 'Error!',
          text: uploadError.value,
          icon: 'error',
          showConfirmButton: true
        })
      } finally {
        uploading.value = false
      }
    }

    const formatBytes = (bytes) => {
      if (!bytes && bytes !== 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      const val = bytes / Math.pow(k, i)
      return `${val.toFixed(val >= 10 || i === 0 ? 0 : 1)} ${sizes[i]}`
    }

    const showModalEndExam = ref(false)
    const showModalEndTimeExam = ref(false)

    const endExam = () => {
      if (!examId.value || !sessionId.value) {
        Swal.fire({ title: 'Error!', text: 'exam_id / exam_session_id tidak valid.', icon: 'error' })
        return
      }

      router.post('/student/essay-migas-end', {
        exam_id: examId.value,
        exam_session_id: sessionId.value,
        exam_group_id: props.id,
      })

      Swal.fire({
        title: 'Success!',
        text: 'Ujian Selesai!.',
        icon: 'success',
        showConfirmButton: false,
        timer: 4000
      })
    }

    return {
      durationMs,
      handleChangeDuration,

      getIsEssay,
      getEssayId,
      hasEssayQuestion,

      answers,
      savingAnswer,
      savedAnswer,
      setEditorRef,
      disablePaste,
      submitAnswer,

      selectedFile,
      uploading,
      uploadProgress,
      uploadedBytes,
      totalBytes,
      uploadDone,
      uploadError,
      uploadErrorDetail,
      uploadedFile,
      onFileChange,
      uploadAnswerFile,
      formatBytes,

      showModalEndExam,
      showModalEndTimeExam,
      endExam,
    }
  }
}
</script>