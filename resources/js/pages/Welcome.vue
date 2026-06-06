<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AlbumDescription from '@/pages/album/albumDescription.vue';

const props = defineProps(['album']);

const firstImage = computed(() => {
    if (props.album.items?.length) {
        const img = props.album.items[0];
        const largestWidth = Object.keys(img.paths).pop();
        const src = largestWidth && img.paths.hasOwnProperty(largestWidth)
            ? img.paths[largestWidth]
            : null;

        return {
            src,
            w: img.max_width,
            h: img.max_height,
        };
    }

    return null;
})
</script>

<template>
    <Head title="Welcome" />

    <album-description :album="album"></album-description>

    <div class="flex justify-center">
        <img v-if="firstImage" :src="firstImage.src"
             :width="firstImage.w" :height="firstImage.h"
             alt="" class="max-w-64">
    </div>
</template>
