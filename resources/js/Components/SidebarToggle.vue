<template>
    <button type="button" @click="toggle"
        class="sidebar-toggle btn btn-icon-only text-gray-800 me-2 d-none d-lg-flex align-items-center"
        :title="collapsed ? 'Lebarkan menu' : 'Kecilkan menu'"
        :aria-label="collapsed ? 'Lebarkan menu' : 'Kecilkan menu'"
        :aria-pressed="collapsed">
        <i class="fa fa-bars"></i>
    </button>
</template>

<script>
// Burger untuk mengecilkan sidebar portal Admin / Asesor / Pengambil Keputusan menjadi
// ikon saja (layar >= lg; di layar kecil sidebar sudah memakai tombol collapse sendiri).
// Status disimpan per browser dan dipasang sebagai class di <body> — gaya ada di
// resources/css/app.css. Sengaja tidak memakai id/kelas "sidebar-toggle"/"contracted"
// + localStorage "sidebar" milik volt.js: script itu mengubah class sidebar saat
// DOMContentLoaded dan akan bentrok dengan render Vue.
const STORAGE_KEY = 'sidebarCollapsed';
const BODY_CLASS  = 'sidebar-collapsed';

function stored() {
    try {
        return localStorage.getItem(STORAGE_KEY) === '1';
    } catch (e) {
        return false;
    }
}

// Dipasang saat modul dimuat (sebelum halaman pertama dirender), supaya saat reload
// sidebar langsung kecil tanpa animasi menyusut.
document.body.classList.toggle(BODY_CLASS, stored());

export default {
    data() {
        return { collapsed: document.body.classList.contains(BODY_CLASS) };
    },

    methods: {
        toggle() {
            this.collapsed = !this.collapsed;
            document.body.classList.toggle(BODY_CLASS, this.collapsed);

            try {
                localStorage.setItem(STORAGE_KEY, this.collapsed ? '1' : '0');
            } catch (e) {
                // localStorage diblokir: tetap berfungsi, hanya tidak diingat.
            }
        },
    },
}
</script>
