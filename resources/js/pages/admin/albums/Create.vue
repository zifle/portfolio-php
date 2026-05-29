<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import EditForm from '@/pages/admin/albums/editForm.vue';
import { index, create } from '@/routes/admin/albums';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Albums',
                href: index(),
            },
            {
                title: 'Create album',
                href: create()
            }
        ],
    },
});

const title = 'Create album';

const album = ref({
    id: 0,
    title: '',
    slug: '',
    published: false,
    location_id: null,
    category_id: null,
    description: '',
    date_start: null,
    date_end: null,
    items: []
});
</script>

<template>
    <Head :title="title"></Head>

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <edit-form :album="album"
                   @album-items="(itms) => album.items = itms"
        ></edit-form>
    </div>
</template>

<style scoped>

</style>
