<script setup lang="ts">
import {
    Pin,
    CalendarDays,
} from 'lucide-vue-next';
import {computed} from "vue";
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
})
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
</script>

<template>
    <div class="album-intro my-5 relative z-1">
        <template v-if="album">
            <div class="mb-5">
                <h2 class="text-center text-4xl">{{ album.title }}</h2>
                <p class="text-center">
                    <span v-if="date_start">
                        <calendar-days v-if="useIcons" class="inline size-5"></calendar-days>
                        <time :datetime="album.date_start ?? undefined">{{ date_start }}</time>
                        <span v-if="date_start && date_end"> - </span>
                        <time v-if="date_end" :datetime="album.date_end ?? undefined">{{ date_end }}</time>
                    </span>
                    <span v-if="date_start && album.location?.name" class="mx-3">|</span>
                    <span v-if="album.location?.name">
                        <pin v-if="useIcons" class="inline size-5"></pin>
                        {{ album.location?.name }}
                    </span>
                </p>
            </div>
            <div class="py-3 md:py-7 px-3 md:px-10 justify-items-center" v-if="album.description">
                <p class="max-w-[60rem] whitespace-pre-wrap">{{ album.description }}</p>
            </div>
            <div class="text-end my-3" v-if="album.tags">
                <span v-for="tag in album.tags" :key="tag" class="badge badge-ghost badge-sm mx-1">
                    {{ tag }}
                </span>
            </div>
        </template>
    </div>
</template>

<style scoped></style>
