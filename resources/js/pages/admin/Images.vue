<script setup lang="ts">
import { Head, InfiniteScroll, usePage, router } from '@inertiajs/vue3';
import { Trash2, Aperture, Cone, Gauge, Camera, Settings } from '@lucide/vue';
import { computed, ref } from 'vue';
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
const props = defineProps(['pagination', 'unused_count', 'total_count', 'filter']);
const csrf_token = page.props.csrf_token as string;

type ImagesFilter = {
    used?: null|string;
}
const filter = computed(() => {
    return props.filter as ImagesFilter;
});

async function deleteImage(id: number) {
    await fetch(destroy(id).url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        },
    });
    router.visit(index(), {
        data: { filter: filter.value },
        only: ['pagination', 'unused_count', 'total_count'],
        reset: ['pagination', 'unused_count', 'total_count'],
    });
}

async function deleteUnused() {
    await fetch(destroyUnused().url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        },
    });
    router.visit(index(), {
        data: { filter: filter.value },
        only: ['pagination', 'unused_count', 'total_count'],
        reset: ['pagination', 'unused_count', 'total_count'],
    });
}

const list = ref();

function updateFilter(filter: ImagesFilter) {

    router.visit(index(), {
        data: { filter },
        only: ['pagination', 'filter'],
        reset: ['pagination', 'filter'],
    });
}

function toggleUsedFilter() {
    const filt = JSON.parse(JSON.stringify(filter.value)) as ImagesFilter;

    if (filt.used === null) {
        filt.used = 'true';
    } else if (filt.used === 'true') {
        filt.used = 'false';
    } else {
        delete filt.used;
    }

    updateFilter(filt);
}
</script>

<template>
    <Head title="Images" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h5>Total {{ total_count }} images, with {{ unused_count }} unused</h5>
        <div class="flex">
            <button
                class="btn w-48 btn-sm"
                :class="{
                    'btn-success': filter?.used === 'true',
                    'btn-warning': filter?.used === 'false',
                }"
                @click="toggleUsedFilter"
            >
                <template v-if="filter?.used === 'true'">
                    Only used images
                </template>
                <template v-else-if="filter?.used === 'false'">
                    Only unused images
                </template>
                <template v-else> All images </template>
            </button>
        </div>
        <InfiniteScroll data="pagination" :items-element="() => list">
            <ul
                class="list rounded-box bg-base-100 shadow-md dark:bg-base-300"
                ref="list"
            >
                <li
                    v-for="im in pagination.data as Image[]"
                    :key="im.id"
                    class="list-row"
                >
                    <img
                        :src="im.paths[Math.min(...im.available_res)]"
                        alt=""
                        class="size-14 rounded-box object-cover object-center lg:size-20"
                    />
                    <div
                        class="list-col-wrap text-center text-sm opacity-40 lg:text-lg"
                    >
                        {{ im.id }}
                    </div>
                    <div>
                        <p class="text-md list-col-wrap">
                            <template v-if="im.albums">
                                <span
                                    v-for="alb in im.albums"
                                    :key="`${im.id}_${alb.id}`"
                                    class="badge dark:badge-soft"
                                    :class="{
                                        'badge-success': alb.published,
                                        'badge-warning': !alb.published,
                                    }"
                                >
                                    {{ alb.title }}
                                </span>
                            </template>
                        </p>
                        <p class="text-md list-col-wrap">
                            {{ im.description }}
                        </p>
                    </div>
                    <div class="list-col-wrap grid grid-cols-3 opacity-50">
                        <p class="col-span-3 text-xs opacity-60">
                            {{ im.path }}
                        </p>
                        <span v-if="im.camera" class="col-span-3 text-xs">
                            <span class="text-nowrap">
                                <Camera class="inline size-4"></Camera>
                                {{ im.camera.str }}
                            </span>
                            <span v-if="im.lens" class="text-nowrap">
                                - {{ im.lens.str }}
                            </span>
                        </span>
                        <span
                            v-if="im.focal_length"
                            class="flex-1 text-xs text-nowrap"
                        >
                            <Cone class="inline size-4 rotate-180"></Cone>
                            {{ im.focal_length }}mm
                        </span>
                        <span
                            v-if="im.aperture"
                            class="flex-1 text-xs text-nowrap"
                        >
                            <Aperture class="inline size-4"></Aperture>
                            f/{{ im.aperture }}
                        </span>
                        <span
                            v-if="im.exposure_time"
                            class="flex-1 text-xs text-nowrap"
                        >
                            <Gauge class="inline size-4"></Gauge>
                            {{ im.exposure_time }}
                        </span>
                    </div>
                    <button
                        class="btn ms-3 btn-ghost btn-sm btn-error"
                        @click="deleteImage(im.id)"
                        :disabled="
                            im.albums !== undefined
                                ? im.albums.length > 0
                                : true
                        "
                    >
                        <trash2 class="size-6"></trash2>
                    </button>
                </li>
            </ul>
        </InfiniteScroll>
    </div>

    <div class="fab">
        <div class="btn btn-circle btn-lg btn-info" tabindex="0" role="button">
            <settings></settings>
        </div>

        <div>
            Delete {{ unused_count }} unused
            <button
                class="btn btn-circle btn-lg btn-error"
                :disabled="!unused_count"
                @click="deleteUnused"
            >
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
