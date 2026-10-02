<template>
    <div class="card border-0 shadow mb-3 page-guide">
        <button type="button" class="page-guide-toggle d-flex justify-content-between align-items-center w-100"
            :aria-expanded="open" @click="toggle">
            <span class="fw-bold"><i class="fa fa-life-ring me-2 text-primary"></i>{{ title }}</span>
            <span class="small text-muted text-nowrap ms-2">
                {{ open ? 'Sembunyikan' : 'Tampilkan' }}
                <i class="fa ms-1" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </span>
        </button>
        <div v-show="open" class="page-guide-body small">
            <slot />
            <div v-if="fullGuideHref" class="mt-3 pt-2 border-top">
                <Link :href="fullGuideHref"><i class="fa fa-book-open me-1"></i>Buka panduan lengkap</Link>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

// Panduan singkat yang ditempel di atas halaman portal. Isinya komponen di Components/Guide/*,
// yang juga dirender halaman Panduan (Pages/*/Guide/Index) — satu sumber, jadi tidak bisa beda.
// Terbuka pada kunjungan pertama; pilihan Sembunyikan/Tampilkan diingat per halaman (localStorage).
export default {
    components: { Link },
    props: {
        storageKey: { type: String, required: true },
        title:      { type: String, default: 'Panduan halaman ini' },
    },

    data() {
        return { open: this.readState() };
    },

    computed: {
        fullGuideHref() {
            const portal = (this.$page.url || '').split('/')[1];
            return ['admin', 'asesor', 'manager'].includes(portal) ? `/${portal}/panduan` : null;
        },
    },

    methods: {
        key() {
            return `page-guide:${this.storageKey}`;
        },
        readState() {
            try {
                return localStorage.getItem(this.key()) !== 'closed';
            } catch {
                return true;
            }
        },
        toggle() {
            this.open = !this.open;
            try {
                localStorage.setItem(this.key(), this.open ? 'open' : 'closed');
            } catch {
                // localStorage diblokir browser — tetap jalan, hanya tidak diingat.
            }
        },
    },
}
</script>

<style scoped>
.page-guide { overflow: hidden; }
.page-guide-toggle {
    background-color: #ffffff;
    color: #1f2937;
    border: 0;
    padding: .75rem 1.25rem;
    text-align: left;
}
.page-guide-toggle:hover { background-color: #f9fafb; }
.page-guide-body {
    background-color: #ffffff;
    color: #374151;
    padding: .25rem 1.25rem 1rem;
}
</style>
