<script setup lang="ts">
import { Head, InfiniteScroll, usePage } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import type { Image } from '@/types/models';

const page = usePage();
defineProps(['pagination']);
const csrf_token = page.props.csrf_token;

async function deleteImage(id: number) {
    // todo
}

const tableBody = ref();
</script>

<template>
    <Head title="Images" />

    <div
        class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4"
    >
        <InfiniteScroll data="pagination" :items-element="() => tableBody">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>Albums</th>
                        <th>Path</th>
                        <th>Sizes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody ref="tableBody">
                    <tr v-for="im in pagination.data as Image[]" :key="im.id">
                        <th>
                            <img :src="im.paths[Math.min(...im.available_res)]"
                                 class="aspect-3/2 object-cover object-center max-w-30"
                            />
                        </th>
                        <th>{{ im.id }}</th>
                        <th>{{ im.albums_count ?? 'None' }}</th>
                        <th>{{ im.path }}</th>
                        <th>{{ im.available_res }}</th>
                        <th>
                            <button
                                class="btn ms-3 btn-ghost btn-sm btn-error"
                                @click="deleteImage(im.id)"
                            >
                                <trash2 class="size-6"></trash2>
                            </button>
                        </th>
                    </tr>
                </tbody>
            </table>
        </InfiniteScroll>
    </div>
</template>

<style scoped></style>
