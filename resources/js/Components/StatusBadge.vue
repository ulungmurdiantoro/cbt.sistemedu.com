<template>
    <!--
      Badge status "senada" sesuai Admin Blueprint §4 (Tata Bahasa Badge).
      Selalu soft (isian tint), bentuk pill, huruf kecil-tebal.
      Tone mengikuti makna:
        accent    → Berlangsung / Aktif        (royal blue)
        secondary → Terjadwal / Menunggu        (gold)
        success   → Selesai / Lulus / Disetujui  (emerald)
        danger    → Tidak Lulus / Ditolak / Waktu Habis (rose)
        neutral   → Draft / Nonaktif            (gray)
    -->
    <span class="status-badge" :class="`sb-${tone}`">
        <slot>{{ label }}</slot>
    </span>
</template>

<script>
export default {
    name: 'StatusBadge',
    props: {
        // accent | secondary | success | danger | neutral
        tone: {
            type: String,
            default: 'neutral',
        },
        label: {
            type: String,
            default: '',
        },
    },
}
</script>

<style scoped>
.status-badge {
    display: inline-block;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1.2;
    padding: 0.25rem 0.7rem;
    border-radius: 50rem;      /* pill */
    white-space: nowrap;
    letter-spacing: 0.01em;
}

/* Soft tints — derived from the EDUKIA/Volt semantic palette. */
.sb-accent    { background: rgba(35, 97, 206, 0.14);  color: #1c4ea3; }
.sb-secondary { background: rgba(240, 188, 116, 0.30); color: #6b4a16; }
.sb-success   { background: rgba(16, 185, 129, 0.16);  color: #0a6b4a; }
.sb-danger    { background: rgba(225, 29, 72, 0.14);   color: #a01233; }
.sb-neutral   { background: #e5e7eb;                   color: #4b5563; }
</style>
