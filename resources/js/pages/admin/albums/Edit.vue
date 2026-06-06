<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import type { Reactive } from 'vue';
import EditForm from '@/pages/admin/albums/editForm.vue';
import { index } from '@/routes/admin/albums';
import type { Album, AlbumItem } from '@/types/models';

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
            },
        ],
    },
});

const props = defineProps(['album']);
const album: Reactive<Album> = reactive(props.album);
const title = computed(() => {
    return `Edit ${album.title}`;
});

function setAlbumItems(items: AlbumItem[]) {
    album.items = items;
}

function setAlbum(newAlbum: Album) {
    Object.assign(album, newAlbum);
}
</script>

<template>
    <Head :title="title"></Head>

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <edit-form
            :album="album"
            @album-saved="setAlbum"
            @album-items="setAlbumItems"
        ></edit-form>
    </div>
</template>

<style scoped></style>
