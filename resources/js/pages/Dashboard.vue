<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { smallestImage } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { Album, Image } from '@/types/models';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

defineProps<{
    views: {
        frontpage: number;
        albums: number;
        images: number;
    };
    top10: {
        albums: Album[];
        images: Image[];
    };
}>();
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="stats stats-vertical sm:stats-horizontal">
            <div class="stat">
                <div class="stat-title">Frontpage views</div>
                <div class="stat-value">{{ views.frontpage }}</div>
                <div class="stat-desc">All time</div>
            </div>
            <div class="stat">
                <div class="stat-title">Album views</div>
                <div class="stat-value">{{ views.albums }}</div>
                <div class="stat-desc">All time</div>
            </div>
            <div class="stat">
                <div class="stat-title">Image views</div>
                <div class="stat-value">{{ views.images }}</div>
                <div class="stat-desc">All time</div>
            </div>
        </div>
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <ul class="list">
                <li class="p-4 pb-2 text-sm tracking-wide">
                    Top 10 viewed albums
                </li>
                <li
                    v-for="album in top10.albums"
                    :key="`top_albums_${album.id}`"
                    class="list-row"
                >
                    <div>{{ album.title }}</div>
                    <div></div>
                    <div>{{ album.views_count ?? 0 }}</div>
                </li>
            </ul>
            <div
                class="relative rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <h4 class="card-title px-4 pt-2">Top 12 viewed images</h4>
                <div
                    class="grid grid-cols-3 justify-around gap-4 p-5 sm:grid-cols-4 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="image in top10.images"
                        :key="`top_images_${image.id}`"
                        class="indicator w-full"
                    >
                        <div
                            class="indicator-item badge badge-sm badge-success"
                        >
                            {{ image.views_count ?? 0 }}
                        </div>
                        <div class="avatar w-full">
                            <div class="rounded">
                                <img :src="smallestImage(image) ?? ''" alt="" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
