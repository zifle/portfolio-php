<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { largestImage } from '@/lib/utils';
import AlbumDescription from '@/pages/album/albumDescription.vue';
import AlbumItems from '@/pages/album/albumItems.vue';
import { isImage } from '@/types/models';
import type { Album } from '@/types/models';

const page = usePage();

const album = computed(() => {
    return page.props.album as Album;
});
const album_items = computed(() => {
    if (album.value && album.value.items) {
        return album.value.items;
    }

    return [];
});

onMounted(() => {
    startLoading();
});

const bgParallax = ref([] as { src: string; srcset: string; aspect: number }[]);
function setAlbumBackground() {
    const lastImages = [];
    const numImages = 3;

    for (const item of album_items.value) {
        if (isImage(item) && item.paths) {
            const obj = {
                src: largestImage(item),
                srcset: item.srcset?.join(', ') ?? '',
                aspect: item.max_width / item.max_height,
            };
            lastImages.push(obj);

            if (lastImages.length > numImages) {
                lastImages.shift();
            }
        }
    }

    if (lastImages.length) {
        bgParallax.value = lastImages.toReversed();
    } else {
        bgParallax.value = [];
    }
}

const loading = ref(false);
function startLoading() {
    loadSpinner.value?.classList.remove('opacity-0', 'hidden');
    loading.value = true;
}
function stopLoading() {
    loadSpinner.value?.classList.add('opacity-0');
    setTimeout(() => {
        loadSpinner.value?.classList.add('hidden');
    }, 300);
    loading.value = false;
    setAlbumBackground();
}

const loadSpinner = useTemplateRef('load-spinner');
</script>

<template>
    <Head :title="album.title" />

    <div id="bg" class="parallax-slow">
        <div
            v-for="(bg, idx) in bgParallax"
            :key="'bg_prx_' + idx"
            class="h-[70%] w-full"
        >
            <img
                :src="bg.src"
                alt=""
                decoding="async"
                fetchpriority="low"
                :srcset="bg.srcset"
                loading="lazy"
                class="h-full w-full object-cover object-center"
                :style="`aspect-ratio: ${bg.aspect};`"
            />
        </div>
    </div>

    <album-description :album="album"></album-description>
    <album-items
        :loading="loading"
        :items="album_items"
        @img-loaded="stopLoading"
    ></album-items>

    <div
        class="fixed top-[25%] left-0 z-10 flex w-full justify-center duration-300 not-[opacity-0]:transition-opacity"
        ref="load-spinner"
    >
        <div class="loading loading-xl loading-ring"></div>
    </div>
    <div class="my-5"></div>
</template>

<style>
#bg {
    height: 100%;
    width: 100%;
    position: fixed;
    z-index: 0;
    top: 0;

    filter: blur(10px) grayscale(100%) contrast(150%);
    opacity: 0.3;

    user-drag: none; /* Disable dragging */
    user-select: none; /* Disable selection */
    -webkit-user-drag: none; /* For WebKit browsers (Chrome and Safari) */
    -webkit-user-select: none;
    -moz-user-select: none; /* For Firefox */
    -ms-user-select: none; /* For Internet Explorer */
    pointer-events: none; /* Disable right-click and long-press */
}

@keyframes move-slow {
    to {
        transform: translateY(calc(-100% + 60vh));
    }
}

.parallax-slow {
    animation: move-slow linear;
    animation-timeline: scroll();
}
</style>
