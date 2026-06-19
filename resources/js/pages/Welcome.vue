<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AlbumTeaser from '@/components/AlbumTeaser.vue';
import AlbumDescription from '@/pages/album/albumDescription.vue';
import { show as showAlbum } from '@/routes/album';

const props = defineProps(['album', 'top6']);

const firstImage = computed(() => {
    if (props.album.items?.length) {
        const img = props.album.items[0];
        const largestWidth = Object.keys(img.paths).pop();
        const src =
            largestWidth && img.paths.hasOwnProperty(largestWidth)
                ? img.paths[largestWidth]
                : null;

        return {
            src,
            w: img.max_width,
            h: img.max_height,
        };
    }

    return null;
});
</script>

<template>
    <Head title="Welcome" />

    <h2 class="hidden text-center text-4xl lg:block">{{ album.title }}</h2>

    <div
        class="flex flex-wrap-reverse justify-center pb-5 lg:flex-nowrap lg:pb-0"
    >
        <album-description :album="album" lg-no-title></album-description>

        <div class="place-content-center lg:pe-10">
            <img
                v-if="firstImage"
                :src="firstImage.src"
                :width="firstImage.w"
                :height="firstImage.h"
                alt=""
                class="aspect-square max-w-64"
            />
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 md:px-2 lg:gap-10 lg:px-5">
        <Link
            v-for="album of top6"
            :key="'tease_' + album.slug"
            :href="showAlbum(album.slug)"
        >
            <album-teaser :album="album"></album-teaser>
        </Link>
    </div>
</template>
