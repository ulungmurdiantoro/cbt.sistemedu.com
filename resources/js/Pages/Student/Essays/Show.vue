<template>
  <Head>
    <title>Ujian Soal No. {{ page }} - Aplikasi Ujian Online</title>
  </Head>

  <div class="row mb-5">
    <!-- Soal -->
    <div class="col-md-7">
      <div class="card border-0 shadow" style="border-radius:16px;">
        <div class="card-header bg-white" style="border-top-left-radius:16px;border-top-right-radius:16px;">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">Soal No. <strong>{{ page }}</strong></h5>
                <span v-if="isSaved" class="badge rounded-pill bg-success px-3 py-2">
                  <i class="fa fa-check me-1"></i> Tersimpan
                </span>
                <span v-else class="badge rounded-pill bg-warning text-dark px-3 py-2">
                  <i class="fa fa-exclamation-circle me-1"></i> Belum tersimpan
                </span>
              </div>
            </div>
            <div class="text-end">
              <VueCountdown :time="duration" @progress="handleChangeDuration" @end="timeUp = true" v-slot="{ hours, minutes, seconds }">
                <span class="badge bg-info p-2"><i class="fa fa-clock"></i> {{ hours }} jam, {{ minutes }} menit, {{ seconds }} detik.</span>
              </VueCountdown>
            </div>
          </div>
          <div class="mt-2 p-2 px-3 d-flex align-items-center" style="background:#EAF4FF;border:1px solid #CFE6FF;border-radius:12px;">
            <i class="fa fa-info-circle text-primary me-2"></i>
            <span class="fw-semibold">💾 Klik <strong>Simpan Jawaban</strong> setelah selesai mengetik.</span>
          </div>
        </div>

        <div class="card-body">
          <div v-if="essay_active">
            <p v-html="essay_active.essay.question" style="user-select:none;"></p>
            <QuillEditor
              ref="editor"
              v-model:content="form.answer"
              contentType="html"
              theme="snow"
              :options="{
                modules: { toolbar: [['bold','italic','underline'],[{align:[]}],[{header:[1,2,3,false]}],[{list:'ordered'},{list:'bullet'}]] },
                placeholder: 'Ketik jawaban Anda di sini...'
              }"
              style="min-height:300px;width:100%;border-radius:12px;"
              @update:content="val => (form.answer = val)"
              @ready="disablePaste"
            />
            <button
              @click="submitAnswer(essay_active.essay.exam.id, essay_active.essay_id)"
              class="btn btn-md btn-info border-0 shadow mt-3 text-white"
              style="border-radius:12px;"
            >
              <i class="fa fa-save me-2"></i> Simpan Jawaban
            </button>
          </div>
        </div>

        <div class="card-footer bg-white" style="border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
          <div class="d-flex justify-content-between">
            <button v-if="page > 1" @click.prevent="prevPage" class="btn btn-gray-400 btn-sm">Sebelumnya</button>
            <div></div>
            <button v-if="page < all_essays.length" @click.prevent="nextPage" class="btn btn-gray-400 btn-sm">Selanjutnya</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigator -->
    <div class="col-md-5">
      <div class="card border-0 shadow" style="border-radius:16px;">
        <div class="card-header text-center bg-white" style="border-top-left-radius:16px;border-top-right-radius:16px;">
          <div class="badge bg-success p-2" style="border-radius:999px;">{{ essay_answered }} dikerjakan</div>
        </div>
        <div class="card-body" style="height:330px;overflow-y:auto;">
          <div v-for="(essay, index) in all_essays" :key="index" style="width:20%;float:left;padding:5px;">
            <button @click.prevent="clickQuestion(index)" class="btn btn-sm w-100" style="border-radius:12px;"
              :class="{
                'btn-gray-400':     index + 1 === page,
                'btn-info text-white': index + 1 !== page && essay.answer != null,
                'btn-outline-info': index + 1 !== page && essay.answer == null,
              }"
            >{{ index + 1 }}</button>
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
    :answered="essay_answered"
    :total="all_essays.length"
    @confirm="endExam"
  />
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue'
import { Head, router } from '@inertiajs/vue3'
import { reactive, ref, watch } from 'vue'
import VueCountdown from '@chenfengyuan/vue-countdown'
import Swal from 'sweetalert2'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import { useExamTimer } from '../../../Composables/useExamTimer'
import ExamEndModals from '../../../Components/Exam/ExamEndModals.vue'

export default {
  layout: LayoutStudent,
  components: { Head, VueCountdown, QuillEditor, ExamEndModals },
  props: {
    id:             Number,
    page:           Number,
    exam_group:     Object,
    all_essays:     Array,
    essay_answered: Number,
    essay_active:   Object,
    answer_order:   Array,
    duration:       Object,
  },
  setup(props) {
    const form   = reactive({ answer: props.essay_active?.answer || '' })
    const isSaved = ref(!!props.essay_active?.answer)

    watch(() => form.answer, () => { isSaved.value = false })

    const { duration, handleChangeDuration, saveDuration } = useExamTimer(
      props.duration.duration,
      props.duration.id,
      '/student/essay-duration/update'
    )

    const showEndModal = ref(false)
    const timeUp       = ref(false)

    function navigate(page) {
      saveDuration()
      router.get(`/student/essay/${props.id}/${page}`)
    }

    const prevPage      = () => navigate(props.page - 1)
    const nextPage      = () => navigate(props.page + 1)
    const clickQuestion = (index) => navigate(index + 1)

    function submitAnswer(exam_id, essay_id) {
      const plain = (form.answer || '').replace(/<(.|\n)*?>/g, '').trim()
      if (!plain) {
        Swal.fire({ title: 'Error!', text: 'Jawaban tidak boleh kosong', icon: 'error' })
        return
      }
      router.post('/student/essay-answer', {
        exam_id,
        exam_session_id: props.exam_group.exam_session.id,
        essay_id,
        answer: form.answer,
        duration: duration.value,
      }, {
        onSuccess: () => {
          isSaved.value = true
          Swal.fire({ title: 'Tersimpan!', text: 'Jawaban berhasil disimpan.', icon: 'success', showConfirmButton: false, timer: 2000 })
        },
        onError: () => {
          Swal.fire({ title: 'Error!', text: 'Gagal menyimpan jawaban', icon: 'error' })
        },
      })
    }

    function endExam() {
      router.post('/student/essay-end', {
        exam_group_id:   props.exam_group.id,
        exam_id:         props.exam_group.exam.id,
        exam_session_id: props.exam_group.exam_session.id,
      })
      Swal.fire({ title: 'Ujian Selesai!', icon: 'success', showConfirmButton: false, timer: 4000 })
    }

    function disablePaste(quill) {
      quill.root.addEventListener('paste', (e) => {
        e.preventDefault()
        alert('Maaf, fitur paste dinonaktifkan untuk jawaban ini.')
      })
    }

    return {
      form, isSaved, duration, handleChangeDuration,
      showEndModal, timeUp,
      prevPage, nextPage, clickQuestion, submitAnswer, endExam, disablePaste,
    }
  },
}
</script>
