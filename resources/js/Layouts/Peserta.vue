<template>
    <nav class="navbar navbar-expand-lg navbar-transparent navbar-dark navbar-theme-primary mb-4 shadow">
        <div class="container position-relative">
            <Link class="navbar-brand me-lg-3" href="/peserta/dashboard">
                <img class="navbar-brand-dark" src="/assets/images/logo.png" style="height:70px">
            </Link>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPeserta">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarPeserta">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <Link class="nav-link text-white" href="/peserta/dashboard">Dashboard</Link>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2" v-if="$page.props.auth.participant">
                    <span class="text-white small">{{ $page.props.auth.participant.name }}</span>
                    <Link href="/peserta/logout" method="POST" class="btn btn-secondary shadow btn-sm" as="button">Logout</Link>
                </div>
            </div>
        </div>
    </nav>
    <div class="container">
        <slot />
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    components: { Link },
}
</script>
