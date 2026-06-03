import { ref } from 'vue'
import axios from 'axios'

/**
 * Mengelola sisa waktu ujian dan sinkronisasi durasi ke backend.
 *
 * @param {number} initialMs  - Durasi awal dalam milidetik (dari props.duration.duration)
 * @param {number} gradeId    - ID grade yang akan di-update (dari props.duration.id)
 * @param {string} updateUrl  - URL endpoint PUT, mis. '/student/exam-duration/update/:id'
 */
export function useExamTimer(initialMs, gradeId, updateUrl) {
    const duration = ref(initialMs)
    const counter  = ref(0)

    // Dipanggil oleh @progress event VueCountdown setiap detik
    function handleChangeDuration() {
        duration.value -= 1000
        counter.value  += 1

        // Sync ke backend setiap 10 detik selama waktu masih ada
        if (duration.value > 0 && counter.value % 10 === 1) {
            saveDuration()
        }
    }

    // Simpan durasi saat ini ke backend (fire-and-forget)
    function saveDuration() {
        axios.put(`${updateUrl}/${gradeId}`, { duration: duration.value }).catch(() => {})
    }

    return { duration, handleChangeDuration, saveDuration }
}
