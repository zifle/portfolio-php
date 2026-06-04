<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2, Pencil, Save } from '@lucide/vue';
import { ref } from 'vue';
import {
    index,
    update,
    store,
    destroy,
} from '@/routes/admin/categories';
import type { CategoryUpdate } from '@/types/models';
import type { RouteDefinition } from '@/wayfinder';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Categories',
                href: index(),
            },
        ],
    },
});

defineProps(['categories']);
const page = usePage();
const csrf_token = page.props.csrf_token as string;

const newCategory = ref({
    name: '',
    order: 0,
    saving: false,
    edit: false,
});
function saveNewCategory() {
    saveCategory(newCategory.value as CategoryUpdate).then(() => {
        newCategory.value.name = '';
        newCategory.value.order = 0;
    });
}
async function saveCategory(cat: CategoryUpdate) {
    if (cat.saving) {
        return;
    }

    cat.saving = true;
    let catCopy: CategoryUpdate = cat;

    if (cat.hasOwnProperty('_edit') && cat._edit !== undefined) {
        catCopy = cat._edit;
    }

    const path: RouteDefinition<'post' | 'put'>
        = cat.id ? update(cat.id) : store();

    const body = {
        id: cat.id,
        name: catCopy.name,
        order: catCopy.order,
    };
    await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify(body),
    });
    cat.saving = false;
    cat.edit = false;
    router.reload({ only: ['categories'] });
}

async function deleteCategory(id: number) {
    await fetch(destroy(id).url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        }
    })
    router.reload({ only: ['categories'] });
}

function editCategory(cat: CategoryUpdate) {
    cat._edit = JSON.parse(JSON.stringify(cat)) as CategoryUpdate;
    cat.edit = true;
}

function cancelEdit(cat: CategoryUpdate) {
    cat.edit = false;
}
</script>

<template>
    <Head title="Categories" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Name</th>
                    <th>Albums</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="cat in categories as CategoryUpdate[]" :key="cat.id">
                    <td>{{ cat.id }}</td>
                    <td>
                        <template v-if="cat.edit">
                            <input
                                type="number"
                                v-model="cat._edit.order"
                                :disabled="cat.saving"
                                class="input"
                                @keyup.enter="saveCategory(cat)"
                                @keyup.esc="cancelEdit(cat)"
                            />
                        </template>
                        <span v-else @dblclick="editCategory(cat)">
                            {{ cat.order }}
                        </span>
                    </td>
                    <td>
                        <input
                            v-if="cat.edit"
                            type="text"
                            v-model="cat._edit.name"
                            :disabled="cat.saving"
                            class="input"
                            @keyup.enter="saveCategory(cat)"
                            @keyup.esc="cancelEdit(cat)"
                        />
                        <span v-else @dblclick="editCategory(cat)">{{
                            cat.name
                        }}</span>
                    </td>
                    <td>{{ cat.albums_count }}</td>
                    <td class="text-end">
                        <button
                            v-if="!cat.edit"
                            class="btn btn-ghost btn-sm btn-info"
                            @click="editCategory(cat)"
                        >
                            <pencil class="size-6"></pencil>
                        </button>
                        <button
                            v-else
                            class="btn btn-sm btn-success"
                            :disabled="cat.saving"
                            @click="saveCategory(cat)"
                        >
                            <save class="size-6"></save>
                        </button>
                        <button
                            class="btn ms-3 btn-ghost btn-sm btn-error"
                            @click="deleteCategory(cat.id)"
                        >
                            <trash2 class="size-6"></trash2>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="divider mt-10 divider-start">Create new Category</div>
        <form
            @submit.prevent="saveNewCategory()"
            class="grid grid-cols-1 items-end gap-3 md:grid-cols-3"
        >
            <div class="px-2">
                <label
                    for="new-cat-name"
                    class="block text-sm/6 font-medium text-white"
                    >Category name</label
                >
                <div class="mt-2">
                    <input
                        type="text"
                        v-model="newCategory.name"
                        class="input w-full"
                        id="new-cat-name"
                        placeholder="Concerts"
                    />
                </div>
            </div>

            <div class="px-2">
                <label
                    for="new-order"
                    class="block text-sm/6 font-medium text-white"
                    >Order (lowest first)</label
                >
                <div class="mt-2">
                    <input
                        type="number"
                        v-model="newCategory.order"
                        class="input w-full"
                        id="new-order"
                        placeholder="0"
                        min="-127"
                        max="127"
                    />
                </div>
            </div>

            <div class="px-2">
                <button type="submit" class="btn btn-success">Create</button>
            </div>
        </form>
    </div>
</template>
