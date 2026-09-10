import LayoutAuth from './Auth.vue';

// Halaman Panduan role dipakai di dua konteks:
//   - di dalam portal (sudah login)      → pakai layout portal lengkap dengan sidebar
//   - dibuka dari /login (belum login)    → pakai layout polos (tanpa sidebar)
// Inertia memanggil `layout` sebagai fungsi (h, page); `page.props.auth.user`
// null saat belum login karena di-share lewat HandleInertiaRequests.
export default function guideLayout(PortalLayout) {
    return (h, page) => h(page.props?.auth?.user ? PortalLayout : LayoutAuth, () => page);
}
