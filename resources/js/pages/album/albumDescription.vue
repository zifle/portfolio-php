<script setup lang="ts">
import { Pin, CalendarDays } from '@lucide/vue';
import { computed } from 'vue';
import type { Album } from '@/types/models';

const props = defineProps(['album']);
const album = computed(() => props.album as Album);

const useIcons = false;
const dateFormatter = new Intl.DateTimeFormat(navigator.language, {
    dateStyle: 'long',
});

const date_start = computed(() => {
    if (!album.value.date_start) {
        return null;
    }

    return dateFormatter.format(new Date(album.value.date_start));
});
const date_end = computed(() => {
    if (!album.value.date_end) {
        return null;
    }

    const de = dateFormatter.format(new Date(album.value.date_end));

    if (date_start.value != null && date_start.value == de) {
        return null;
    }

    return de;
});

const albumDescription = computed(() => {
    let desc = album.value.description;

    // Replace md links
    desc = desc.replaceAll(
        /\[([^\]]+)]\(([^)]+)\)/gi,
        (substring, name, href) => {
            return `<a href="${href}" class="link">${name}</a>`;
        },
    );

    return desc;
});
</script>

<template>
    <div class="album-intro relative z-1 my-5">
        <template v-if="album">
            <div class="mb-5">
                <h2 class="text-center text-4xl">{{ album.title }}</h2>
                <p class="text-center">
                    <span v-if="date_start">
                        <calendar-days
                            v-if="useIcons"
                            class="inline size-5"
                        ></calendar-days>
                        <time :datetime="album.date_start ?? undefined">{{
                            date_start
                        }}</time>
                        <span v-if="date_start && date_end"> - </span>
                        <time
                            v-if="date_end"
                            :datetime="album.date_end ?? undefined"
                            >{{ date_end }}</time
                        >
                    </span>
                    <span v-if="date_start && album.location?.name" class="mx-3"
                        >|</span
                    >
                    <span v-if="album.location?.name">
                        <pin v-if="useIcons" class="inline size-5"></pin>
                        {{ album.location?.name }}
                    </span>
                </p>
            </div>
            <div
                class="justify-items-center px-3 py-3 md:px-10 md:py-7"
                v-if="album.description"
            >
                <p
                    class="max-w-[60rem] whitespace-pre-wrap"
                    v-html="albumDescription"
                ></p>
            </div>
            <div class="my-3 text-end" v-if="album.tags">
                <span
                    v-for="tag in album.tags"
                    :key="tag"
                    class="mx-1 badge badge-ghost badge-sm"
                >
                    {{ tag }}
                </span>
            </div>
        </template>
    </div>
</template>

<style scoped></style>
