<template>
    <Head>
        <title>Ujian Soal No. {{ page }} - Aplikasi Ujian Online</title>
    </Head>

    <div class="row mb-5">
        <!-- Soal -->
        <div class="col-md-7">
            <div class="card border-0 shadow">
                <div class="card-header">
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-0">Soal No. <strong>{{ page }}</strong></h5>
                        <VueCountdown :time="duration" @progress="handleChangeDuration" @end="timeUp = true" v-slot="{ hours, minutes, seconds }">
                            <span class="badge bg-info p-2"><i class="fa fa-clock"></i> {{ hours }} jam, {{ minutes }} menit, {{ seconds }} detik.</span>
                        </VueCountdown>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="question_active">
                        <div style="user-select:none;" v-html="question_active.question.question"></div>
                        <table style="user-select:none;">
                            <tbody>
                                <tr v-for="(answer, index) in answer_order" :key="index">
                                    <td width="50" style="padding:10px;">
                                        <button
                                            v-if="answer == question_active.answer"
                                            class="btn btn-info btn-sm w-100"
                                        >{{ options[index] }}</button>
                                        <button
                                            v-else
                                            @click.prevent="submitAnswer(question_active.question.exam.id, question_active.question.id, answer)"
                                            class="btn btn-outline-info btn-sm w-100"
                                        >{{ options[index] }}</button>
                                    </td>
                                    <td style="padding:10px;">
                                        <p v-html="question_active.question['option_'+answer]"></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else>
                        <div class="alert alert-danger border-0 shadow">
                            <i class="fa fa-exclamation-triangle"></i> Soal Tidak Ditemukan!
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button v-if="page > 1" @click.prevent="prevPage" class="btn btn-gray-400 btn-sm">Sebelumnya</button>
                        <div></div>
                        <button v-if="page < all_questions.length" @click.prevent="nextPage" class="btn btn-gray-400 btn-sm">Selanjutnya</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigator -->
        <div class="col-md-5">
            <div class="card border-0 shadow">
                <div class="card-header text-center">
                    <div class="badge bg-success p-2">{{ question_answered }} / {{ all_questions.length }} soal dikerjakan</div>
                </div>
                <div class="card-body" style="height:330px;overflow-y:auto;">
                    <div v-for="(q, index) in all_questions" :key="index" style="width:20%;float:left;padding:5px;">
                        <button @click.prevent="clickQuestion(index)" class="btn btn-sm w-100"
                            :class="{
                                'btn-gray-400':     index + 1 === page,
                                'btn-info':         index + 1 !== page && q.answer != 0,
                                'btn-outline-info': index + 1 !== page && q.answer == 0,
                            }"
                        >{{ index + 1 }}</button>
                    </div>
                </div>
                <div class="card-footer">
                    <button @click="showEndModal = true" class="btn btn-danger btn-md border-0 shadow w-100">Akhiri Ujian</button>
                </div>
            </div>
        </div>
    </div>

    <ExamEndModals
        v-model="showEndModal"
        :time-up="timeUp"
        :answered="question_answered"
        :total="all_questions.length"
        @confirm="endExam"
    />
</template>

<script>
import LayoutStudent from '../../../Layouts/Student.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import VueCountdown from '@chenfengyuan/vue-countdown'
import Swal from 'sweetalert2'
import { useExamTimer } from '../../../Composables/useExamTimer'
import ExamEndModals from '../../../Components/Exam/ExamEndModals.vue'

export default {
    layout: LayoutStudent,
    components: { Head, VueCountdown, ExamEndModals },
    props: {
        id:                 Number,
        page:               Number,
        exam_group:         Object,
        all_questions:      Array,
        question_answered:  Number,
        question_active:    Object,
        answer_order:       Array,
        duration:           Object,
    },
    setup(props) {
        const options = ['A', 'B', 'C', 'D', 'E']

        const { duration, handleChangeDuration, saveDuration } = useExamTimer(
            props.duration.duration,
            props.duration.id,
            '/student/exam-duration/update'
        )

        const showEndModal = ref(false)
        const timeUp       = ref(false)

        function navigate(page) {
            saveDuration()
            router.get(`/student/exam/${props.id}/${page}`)
        }

        const prevPage     = () => navigate(props.page - 1)
        const nextPage     = () => navigate(props.page + 1)
        const clickQuestion = (index) => navigate(index + 1)

        function submitAnswer(exam_id, question_id, answer) {
            router.post('/student/exam-answer', {
                exam_id,
                exam_session_id: props.exam_group.exam_session.id,
                question_id,
                answer,
                duration: duration.value,
            })
        }

        function endExam() {
            router.post('/student/exam-end', {
                exam_group_id:   props.exam_group.id,
                exam_id:         props.exam_group.exam.id,
                exam_session_id: props.exam_group.exam_session.id,
            })
            Swal.fire({ title: 'Ujian Selesai!', icon: 'success', showConfirmButton: false, timer: 4000 })
        }

        return {
            options, duration, handleChangeDuration,
            showEndModal, timeUp,
            prevPage, nextPage, clickQuestion, submitAnswer, endExam,
        }
    },
}
</script>
