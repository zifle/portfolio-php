<script setup lang="ts">
import { Head, InfiniteScroll, usePage, router } from '@inertiajs/vue3';
import { Trash2, Aperture, Cone, Gauge, Camera, Settings } from '@lucide/vue';
import { ref } from 'vue';
import { index, destroy, destroyUnused } from '@/routes/admin/images';
import type { Image } from '@/types/models';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Images',
                href: index(),
            },
        ],
    },
});

const page = usePage();
defineProps(['pagination', 'unused_count', 'total_count']);
const csrf_token = page.props.csrf_token as string;

async function deleteImage(id: number) {
    await fetch(destroy(id).url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        }
    });
    router.visit(index(), {
        data: { },
        only: ['pagination', 'unused_count', 'total_count'],
        reset: ['pagination', 'unused_count', 'total_count'],
    });
}

async function deleteUnused() {
    await fetch(destroyUnused().url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        }
    });
    router.visit(index(), {
        data: { },
        only: ['pagination', 'unused_count', 'total_count'],
        reset: ['pagination', 'unused_count', 'total_count'],
    });
}

const list = ref();
</script>

<template>
    <Head title="Images" />

    <div
        class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4"
    >
        <h5>Total {{ total_count }} images, with {{ unused_count }} unused</h5>
        <InfiniteScroll data="pagination" :items-element="() => list">
            <ul class="list bg-base-100 dark:bg-base-300 rounded-box shadow-md" ref="list">
                <li v-for="im in pagination.data as Image[]" :key="im.id" class="list-row">
                    <img :src="im.paths[Math.min(...im.available_res)]" alt=""
                         class="size-14 lg:size-20 rounded-box object-cover object-center">
                    <div class="list-col-wrap text-center text-sm lg:text-lg opacity-40">
                        {{ im.id }}
                    </div>
                    <div>
                        <p class="list-col-wrap text-md">
                            <template v-if="im.albums">
                                <span v-for="alb in im.albums" :key="`${im.id}_${alb.id}`"
                                      class="badge dark:badge-soft"
                                      :class="{'badge-success': alb.published, 'badge-warning': !alb.published}"
                                >
                                    {{ alb.title }}
                                </span>
                            </template>
                        </p>
                        <p class="list-col-wrap text-md">{{ im.description }}</p>
                    </div>
                    <div class="list-col-wrap grid grid-cols-3 opacity-50">
                        <p class="text-xs opacity-60 col-span-3">{{ im.path }}</p>
                        <span v-if="im.camera" class="col-span-3 text-xs">
                            <span class="text-nowrap">
                                <Camera class="inline size-4"></Camera>
                                {{ im.camera.str }}
                            </span>
                            <span v-if="im.lens" class="text-nowrap">
                                - {{ im.lens.str }}
                            </span>
                        </span>
                        <span v-if="im.focal_length" class="text-nowrap flex-1 text-xs">
                            <Cone class="inline size-4 rotate-180"></Cone>
                            {{ im.focal_length }}mm
                        </span>
                        <span v-if="im.aperture" class="text-nowrap flex-1 text-xs">
                            <Aperture class="inline size-4"></Aperture>
                            f/{{ im.aperture }}
                        </span>
                        <span v-if="im.exposure_time" class="text-nowrap flex-1 text-xs">
                            <Gauge class="inline size-4"></Gauge>
                            {{ im.exposure_time }}
                        </span>
                    </div>
                    <button
                        class="btn ms-3 btn-ghost btn-sm btn-error"
                        @click="deleteImage(im.id)"
                        :disabled="im.albums !== undefined ? im.albums.length > 0 : true"
                    >
                        <trash2 class="size-6"></trash2>
                    </button>
                </li>
            </ul>
        </InfiniteScroll>
    </div>

    <div class="fab">
        <div class="btn btn-lg btn-circle btn-info" tabindex="0" role="button">
            <settings></settings>
        </div>

        <div>
            Delete {{ unused_count }} unused
            <button class="btn btn-lg btn-circle btn-error"
                    :disabled="!unused_count" @click="deleteUnused">
                <trash2></trash2>
            </button>
        </div>
    </div>
</template>

<style scoped>
@media screen and (max-width: 768px) {
    table,
    thead,
    tbody,
    th,
    td,
    tr {
        display: block;
    }

    thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }

    tr {
        margin-bottom: 20px;
        border: 1px solid #ddd;
    }

    td {
        border: none;
        position: relative;
        padding-left: 50%;
    }

    td:before {
        position: absolute;
        left: 6px;
        content: attr(data-label);
        font-weight: bold;
    }
}
</style>
