<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Ref } from 'vue';
import EditForm from '@/pages/admin/albums/editForm.vue';
import { index } from '@/routes/admin/albums';
import type { Album } from '@/types/models';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Albums',
                href: index(),
            },
            {
                title: 'Edit album',
                // href: edit(page.props.album.id)
            }
        ],
    },
});

const props = defineProps(['album']);
const album: Ref<Album> = ref(props.album);
const title = computed(() => {
    return `Edit ${album.value.title}`;
});

</script>

<template>
    <Head :title="title"></Head>

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <edit-form :album="album" @album-saved="(na) => album = na"
                   @album-items="(itms) => album.items = itms"
        ></edit-form>
    </div>
</template>

<style scoped>

</style>
