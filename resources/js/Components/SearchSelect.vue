<template>
    <div class="search-select">
        <div class="input-group input-group-sm">
            <input ref="input" type="text" class="form-control form-control-sm" autocomplete="off"
                role="combobox" :aria-expanded="open"
                :value="open ? query : selectedLabel"
                :placeholder="open ? searchPlaceholder : emptyLabel"
                @focus="openList" @click="openList" @input="onInput" @blur="close"
                @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="choose(highlighted)" @keydown.esc.prevent="$refs.input.blur()">
            <button v-if="modelValue !== null" type="button" class="btn btn-outline-secondary" title="Kosongkan"
                @mousedown.prevent @click="select(null)">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <!-- Di-teleport ke body supaya tidak terpotong .table-responsive (overflow) -->
        <Teleport to="body">
            <ul v-if="open" ref="menu" class="dropdown-menu show shadow search-select-menu" :style="menuStyle">
                <li v-for="(item, i) in items" :key="item.value ?? 'empty'">
                    <button type="button" class="dropdown-item d-flex justify-content-between align-items-center gap-3"
                        :class="{ 'is-highlighted': i === highlighted, 'fw-bold': item.value === modelValue, 'text-muted': item.value === null }"
                        @mousedown.prevent="choose(i)" @mouseenter="highlighted = i">
                        <span class="text-wrap">
                            <i v-if="item.value === modelValue && item.value !== null" class="fa fa-check text-success me-1"></i>{{ item.label }}
                        </span>
                        <span v-if="item.hint" class="small text-muted text-nowrap">{{ item.hint }}</span>
                    </button>
                </li>
                <li v-if="items.length === 0">
                    <span class="dropdown-item-text small text-muted">Tidak ada yang cocok dengan “{{ query }}”.</span>
                </li>
            </ul>
        </Teleport>
    </div>
</template>

<script>
// Pengganti <select> yang bisa dicari: ketik untuk menyaring, ↑/↓ + Enter untuk memilih, Esc untuk batal.
// options: [{ value, label, hint? }] — hint tampil redup di kanan (mis. jumlah peserta).
const normalize = (text) => String(text ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

export default {
    props: {
        modelValue:        { type: [Number, String], default: null },
        options:           { type: Array, required: true },
        emptyLabel:        { type: String, default: '— Belum dipilih —' },
        searchPlaceholder: { type: String, default: 'Ketik untuk mencari...' },
    },
    emits: ['update:modelValue'],

    data() {
        return { open: false, query: '', highlighted: 0, menuStyle: {} };
    },

    computed: {
        selectedLabel() {
            return this.options.find(o => o.value === this.modelValue)?.label ?? '';
        },
        items() {
            const words = normalize(this.query).split(/\s+/).filter(Boolean);
            if (!words.length) {
                return [{ value: null, label: this.emptyLabel }, ...this.options];
            }
            return this.options.filter(o => {
                const label = normalize(o.label);
                return words.every(w => label.includes(w));
            });
        },
    },

    beforeUnmount() {
        this.unlisten();
    },

    methods: {
        openList() {
            if (this.open) return;
            this.query = '';
            this.open = true;
            this.highlighted = Math.max(0, this.items.findIndex(i => i.value === this.modelValue));
            this.position();
            window.addEventListener('scroll', this.position, true);
            window.addEventListener('resize', this.position);
            this.$nextTick(this.scrollToHighlighted);
        },
        close() {
            this.open = false;
            this.query = '';
            this.unlisten();
        },
        unlisten() {
            window.removeEventListener('scroll', this.position, true);
            window.removeEventListener('resize', this.position);
        },
        onInput(e) {
            this.query = e.target.value;
            this.highlighted = 0;
            if (!this.open) this.openList();
        },
        move(step) {
            if (!this.open) return this.openList();
            const n = this.items.length;
            if (!n) return;
            this.highlighted = (this.highlighted + step + n) % n;
            this.$nextTick(this.scrollToHighlighted);
        },
        choose(i) {
            const item = this.items[i];
            if (item) this.select(item.value);
        },
        select(value) {
            this.$emit('update:modelValue', value);
            this.$refs.input.blur();
        },
        scrollToHighlighted() {
            this.$refs.menu?.querySelector('.is-highlighted')?.scrollIntoView({ block: 'nearest' });
        },
        // Menu fixed di bawah input; dibalik ke atas kalau ruang di bawah tidak cukup.
        position(e) {
            if (e && this.$refs.menu?.contains(e.target)) return; // scroll di dalam menu sendiri
            const rect = this.$refs.input?.getBoundingClientRect();
            if (!rect) return;
            const maxHeight = 280;
            const below = window.innerHeight - rect.bottom;
            const flip = below < maxHeight + 8 && rect.top > below;
            this.menuStyle = {
                position:  'fixed',
                left:      `${rect.left}px`,
                width:     `${Math.max(rect.width, 280)}px`,
                maxHeight: `${Math.min(maxHeight, (flip ? rect.top : below) - 8)}px`,
                ...(flip ? { bottom: `${window.innerHeight - rect.top + 2}px` } : { top: `${rect.bottom + 2}px` }),
            };
        },
    },
}
</script>

<style>
/* Tidak scoped: menu di-teleport ke <body>. */
.search-select-menu {
    overflow-y: auto;
    z-index: 1060;
    margin: 0;
}
.search-select-menu .dropdown-item.is-highlighted {
    background-color: #e5e7eb;
    color: #111827;
}
</style>
