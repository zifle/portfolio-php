<script setup lang="ts">
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from '@lucide/vue';
import {
    index,
    edit,
    create,
    destroy,
    togglePublished as tglPub,
} from '@/routes/admin/albums';
import type { Album } from '@/types/models';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Albums',
                href: index(),
            },
        ],
    },
});

const page = usePage();
const csrf_token = page.props.csrf_token as string;
defineProps(['pagination']);

async function togglePublished(album: Album) {
    const path = tglPub(album.id);
    await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify({ publish: album.published }),
    });
}

async function deleteAlbum(album: Album) {
    if (
        confirm(
            'Are you sure you want to delete this album? This action CANNOT be reversed',
        )
    ) {
        await fetch(destroy(album.id).url, {
            method: 'DELETE',
            headers: {
                'X-CSRFToken': csrf_token,
            },
        });
        router.reload({ only: ['pagination', 'albums'] });
    }
}
</script>

<template>
    <Head title="Albums" />

    <div class="fab">
        <Link :href="create()" class="btn btn-circle btn-lg btn-primary">
            <Plus></Plus>
        </Link>
    </div>

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <InfiniteScroll data="pagination">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Order</th>
                        <th>Title</th>
                        <th>Published</th>
                        <th>Images</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="album of pagination.data as Album[]"
                        :key="album.id"
                    >
                        <td>{{ album.id }}</td>
                        <td>{{ album.category?.name ?? 'None' }}</td>
                        <td>{{ album.order }}</td>
                        <td>{{ album.title }}</td>
                        <td>
                            <input
                                type="checkbox"
                                class="toggle toggle-success"
                                v-model="album.published"
                                @change="togglePublished(album)"
                            />
                        </td>
                        <td>{{ album.images_count }}</td>
                        <td class="text-end">
                            <Link
                                :href="edit(album.id)"
                                class="btn me-3 btn-ghost btn-sm btn-info"
                            >
                                <pencil class="size-6"></pencil>
                            </Link>
                            <button
                                :disabled="album.published"
                                class="btn btn-ghost btn-sm btn-error"
                                @click="deleteAlbum(album)"
                            >
                                <trash2 class="size-6"></trash2>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </InfiniteScroll>
    </div>
</template>

<style scoped></style>
