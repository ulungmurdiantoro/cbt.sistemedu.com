<template>
  <!-- Modal konfirmasi akhiri ujian -->
  <div
    v-if="modelValue"
    class="modal fade show"
    tabindex="-1"
    aria-hidden="true"
    style="display:block;"
    role="dialog"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Akhiri Ujian ?</h5>
        </div>
        <div class="modal-body">
          <div v-if="unanswered > 0" class="alert alert-warning border-0 mb-3">
            <i class="fa fa-exclamation-triangle"></i>
            Masih ada <strong>{{ unanswered }}</strong> soal belum terjawab
            ({{ answered }} / {{ total }} dikerjakan).
          </div>
          <div v-else class="alert alert-success border-0 mb-3">
            <i class="fa fa-check-circle"></i>
            Semua soal sudah dikerjakan ({{ total }} soal).
          </div>
          Setelah mengakhiri ujian, Anda tidak dapat kembali ke ujian ini lagi. Yakin akan mengakhiri ujian?
        </div>
        <div class="modal-footer">
          <button @click.prevent="$emit('confirm')" type="button" class="btn btn-primary">Ya, Akhiri</button>
          <button @click.prevent="$emit('update:modelValue', false)" type="button" class="btn btn-secondary">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal waktu habis (tidak bisa ditutup) -->
  <div
    v-if="timeUp"
    class="modal fade show"
    data-bs-backdrop="static"
    data-bs-keyboard="false"
    tabindex="-1"
    aria-hidden="true"
    style="display:block;"
    role="dialog"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Waktu Habis !</h5>
        </div>
        <div class="modal-body">
          Waktu ujian sudah berakhir!. Klik <strong>Ya</strong> untuk mengakhiri ujian.
        </div>
        <div class="modal-footer">
          <button @click.prevent="$emit('confirm')" type="button" class="btn btn-primary">Ya</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ExamEndModals',
  props: {
    modelValue: { type: Boolean, default: false }, // v-model: tampilkan modal akhiri
    timeUp:     { type: Boolean, default: false }, // tampilkan modal waktu habis
    answered:   { type: Number, default: 0 },
    total:      { type: Number, default: 0 },
  },
  computed: {
    unanswered() {
      return Math.max(this.total - this.answered, 0)
    },
  },
  emits: ['update:modelValue', 'confirm'],
}
</script>
