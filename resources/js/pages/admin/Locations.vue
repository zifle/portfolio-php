<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Trash2, Pencil, Save } from '@lucide/vue';
import { ref } from 'vue';
import {
    index,
    update,
    store,
    destroy,
} from '@/routes/admin/locations';
import type { LocationUpdate } from '@/types/models';
import type { RouteDefinition } from '@/wayfinder';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Locations',
                href: index(),
            },
        ],
    },
});

defineProps(['locations']);
const page = usePage();
const csrf_token = page.props.csrf_token as string;

const newLocation = ref({
    name: '',
    coords: null,
    saving: false,
    edit: false,
});
function saveNewLocation() {
    saveLocation(newLocation.value as LocationUpdate).then(() => {
        newLocation.value.name = '';
        newLocation.value.coords = null;
    });
}
async function saveLocation(loc: LocationUpdate) {
    if (loc.saving) {
        return;
    }

    loc.saving = true;
    let locCopy: LocationUpdate = loc;

    if (loc.hasOwnProperty('_edit') && loc._edit !== undefined) {
        locCopy = loc._edit;
    }

    if (locCopy.coords) {
        [locCopy.lat, locCopy.lng] = locCopy.coords
            .split(',')
            .map((c) => parseFloat(c) || null);
    }

    const path: RouteDefinition<'post' | 'put'>
        = loc.id ? update(loc.id) : store();

    const body = {
        id: loc.id,
        name: locCopy.name,
        lat: locCopy.lat,
        lng: locCopy.lng,
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
    loc.saving = false;
    loc.edit = false;
    router.reload({ only: ['locations'] });
}

async function deleteLocation(id: number) {
    await fetch(destroy(id).url, {
        method: 'DELETE',
        headers: {
            'X-CSRFToken': csrf_token,
        }
    })
    router.reload({ only: ['locations'] });
}

function editLocation(loc: LocationUpdate) {
    loc._edit = JSON.parse(JSON.stringify(loc)) as LocationUpdate;
    loc._edit.coords = loc.lat + ',' + loc.lng;
    loc.edit = true;
}

function cancelEdit(loc: LocationUpdate) {
    loc.edit = false;
}
</script>

<template>
    <Head title="Locations" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >

        <table class="table table-zebra">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Coordinates</th>
                    <th>Albums</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="loc in locations as LocationUpdate[]" :key="loc.id">
                    <td>{{ loc.id }}</td>
                    <td>
                        <input
                            v-if="loc.edit"
                            type="text"
                            v-model="loc._edit.name"
                            :disabled="loc.saving"
                            class="input"
                            @keyup.enter="saveLocation(loc)"
                            @keyup.esc="cancelEdit(loc)"
                        />
                        <span v-else @dblclick="editLocation(loc)">{{
                            loc.name
                        }}</span>
                    </td>
                    <td>
                        <template v-if="loc.edit">
                            <input
                                type="text"
                                v-model="loc._edit.coords"
                                :disabled="loc.saving"
                                class="input"
                                @keyup.enter="saveLocation(loc)"
                                @keyup.esc="cancelEdit(loc)"
                            />
                        </template>
                        <span v-else-if="loc.lat" @dblclick="editLocation(loc)">
                            {{ loc.lat }}, {{ loc.lng }}
                        </span>
                    </td>
                    <td>{{ loc.albums_count }}</td>
                    <td class="text-end">
                        <button
                            v-if="!loc.edit"
                            class="btn btn-sm btn-ghost btn-info"
                            @click="editLocation(loc)"
                        >
                            <pencil class="size-6"></pencil>
                        </button>
                        <button
                            v-else
                            class="btn btn-sm btn-success"
                            :disabled="loc.saving"
                            @click="saveLocation(loc)"
                        >
                            <save class="size-6"></save>
                        </button>
                        <button
                            class="btn btn-sm btn-ghost btn-error ms-3"
                            @click="deleteLocation(loc.id)"
                        >
                            <trash2 class="size-6"></trash2>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="divider divider-start mt-10">Create new Location</div>
        <form
            @submit.prevent="saveNewLocation()"
            class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end"
        >
            <div class="px-2">
                <label for="new-loc-name" class="block text-sm/6 font-medium text-white">Location name</label>
                <div class="mt-2">
                    <input
                        type="text"
                        v-model="newLocation.name"
                        class="input w-full"
                        id="new-loc-name"
                        placeholder="Madison Square Garden"
                    />
                </div>
            </div>

            <div class="px-2">
                <label for="new-loc-lat" class="block text-sm/6 font-medium text-white">Coordinates (latitude,longitude)</label>
                <div class="mt-2">
                    <input
                        type="text"
                        v-model="newLocation.coords"
                        class="input w-full"
                        id="new-loc-lat"
                        placeholder="40.7505, -73.9934"
                    />
                </div>

            </div>

            <div class="px-2">
                <button type="submit" class="btn btn-success">Create</button>
            </div>
        </form>
    </div>
</template>
