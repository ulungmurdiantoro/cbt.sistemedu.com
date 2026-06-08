<template>
    <div :class="hasMeta ? 'd-flex flex-wrap align-items-center justify-content-between gap-2 mt-4' : ''">
        <span v-if="hasMeta" class="text-muted small">
            Menampilkan {{ from || 0 }}–{{ to || 0 }} dari {{ total }} {{ entity }}
        </span>
        <nav>
            <ul :class="`pagination pagination-sm justify-content-${align} mb-0 ${hasMeta ? '' : 'mt-4'}`">
                <li :class="[
                        'page-item',
                        link.url == null ? 'disabled' : '',
                        link.active ? 'active' : '',
                    ]"
                    v-for="(link, index) in links" :key="index">
                    <Link
                        class="page-link"
                        :href="link.url === null ? '#' : link.url"
                        v-html="link.label">
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>

    //import Link
    import { Link } from '@inertiajs/vue3';

    export default {
        props: {
            links: Array,
            align: {
                type: String,
                default: 'end',
            },
            // Optional pagination meta — when `total` is provided, the
            // "Menampilkan X–Y dari N <entitas>" summary (Blueprint §3) is shown.
            total: {
                type: Number,
                default: null,
            },
            from: {
                type: Number,
                default: null,
            },
            to: {
                type: Number,
                default: null,
            },
            entity: {
                type: String,
                default: 'data',
            },
        },

        components: {
            Link,
        },

        computed: {
            hasMeta() {
                return this.total !== null && this.total !== undefined;
            },
        },
    }
</script>

<style>

</style>
