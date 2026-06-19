<script setup lang="ts">
import { computed } from 'vue';
import { largestImage } from '@/lib/utils';
import type { Album, Image } from '@/types/models';
import { isImage } from '@/types/models';

const props = defineProps(['album']);
const album = computed(() => {
    return props.album as Album;
});

const image = computed(() => {
    let img: Image | undefined;

    for (const item of album.value.items) {
        if (isImage(item)) {
            img = item;
            break;
        }
    }

    if (img) {
        return {
            srcset: img.srcset.join(','),
            description: img.description ?? '',
            src: largestImage(img) ?? '',
            aspect: img.max_width / img.max_height,
            width: img.max_width,
            height: img.max_height,
            sizes: '(max-width: 767px) 120vw, 70vw', // Oversized as the images cover
        };
    }

    return null;
});

const albumDescription = computed(() => {
    let desc = album.value.description;

    // Replace md links
    desc = desc.replaceAll(/\[([^\]]+)]\(([^)]+)\)/gi, (substring, name) => {
        return name;
    });

    return desc;
});
</script>

<template>
    <div
        class="grid aspect-4/3 grid-cols-1 grid-rows-2 md:aspect-square lg:aspect-4/3"
    >
        <img
            v-if="image"
            :srcset="image.srcset"
            :sizes="image.sizes"
            :src="image.src"
            :width="image.width"
            :height="image.height"
            loading="lazy"
            :alt="image.description"
            class="col-start-1 row-start-1 row-end-3 aspect-[inherit] object-cover md:rounded-md"
        />
        <div
            class="z-1 card-body h-20 bg-[rgba(255,255,255,0.5)] backdrop-blur-sm sm:h-37 dark:bg-[rgba(0,0,0,0.5)]"
        >
            <h2 class="card-title justify-between">
                <span>{{ album.title }}</span>
                <span v-if="album.date_start" class="text-sm">{{
                    album.date_start
                }}</span>
            </h2>
            <p class="line-clamp-3">{{ albumDescription.substring(0, 400) }}</p>
        </div>
    </div>
</template>

<style scoped></style>
