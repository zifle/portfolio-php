<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
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

const bgParallax = ref([] as string[]);
function setAlbumBackground() {
    const lastImages = [];
    const numImages = 3;

    for (const item of album_items.value) {
        if (isImage(item) && item.paths) {
            lastImages.push(item.paths[item.max_width]);

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
    loadSpinner.value?.classList.remove('opacity-0');
    loadSpinner.value?.classList.remove('hidden');
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
    <div id="bg" class="parallax-slow">
        <div
            v-for="url in bgParallax"
            :key="'bg_prx_' + url"
            :style="`background-image: url('${url}')`"
        ></div>
    </div>

    <album-description :album="album"></album-description>
    <album-items :loading="loading" :items="album_items"
                 @img-loaded="stopLoading"></album-items>

    <div
        class="flex fixed justify-center w-full left-0 top-[25%]"
        ref="load-spinner"
    >
        <div class="loading loading-ring loading-xl"></div>
    </div>
    <div class="my-5"></div>
</template>

<style>
#bg {
    height: 100%;
    width: 100%;
    position: fixed;
    z-index: -1;

    background-size: cover;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: center;

    filter: blur(10px) grayscale(100%) contrast(150%);
    opacity: 0.5;

    > div {
        height: 70%;
        background-size: cover;
        background-repeat: no-repeat;
    }
}

@keyframes move-slow {
    to { transform: translateY(calc(-100% + 60vh)); }
}

.parallax-slow {
    animation: move-slow linear;
    animation-timeline: scroll();
}
</style>
