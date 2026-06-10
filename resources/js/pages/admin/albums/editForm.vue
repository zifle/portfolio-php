<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    edit as editAlbum,
    store as storeAlbum,
    update as updateAlbum,
} from '@/routes/admin/albums';
import type { UploadPlaceholder } from '@/types';
import type {
    Album,
    Image,
    ListItem,
    Location,
    Camera,
    Lens,
    Category,
} from '@/types/models';
import ImageUpload from './imageUpload.vue';
import Items from './items.vue';

const page = usePage();
const csrf_token = page.props.csrf_token as string;
const props = defineProps(['album']);
const album: Album = props.album;
const emit = defineEmits(['albumSaved', 'albumItems']);

const categories = computed(() => {
    return page.props.categories as Category[];
});
const rawLocations = computed(() => {
    return page.props.locations as Location[];
});

async function doSaveAlbum(album: Album) {
    const path = album.id ? updateAlbum(album.id) : storeAlbum();
    const response = await fetch(path.url, {
        method: path.method,
        body: JSON.stringify(album),
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
    });

    toast.success('Album saved');

    return (await response.json()) as Album;
}

const saving = ref(false);
const unsavedChanges = ref(false);
const tmp_album_items: Ref<ListItem[]> = ref([]);
async function saveAlbum() {
    saving.value = true;

    try {
        const copy = JSON.parse(JSON.stringify(album));
        copy.items = tmp_album_items.value.map((item) => ({
            id: item.id,
            order: item.order,
            type: item.type,
        }));
        const is_new = !(copy.id > 0);
        const saved_album = await doSaveAlbum(copy);

        if (is_new) {
            router.replace({
                url: editAlbum(saved_album.id).url,
                component: 'admin/albums/Edit',
                props: (currentProps) => ({
                    ...currentProps,
                    album: saved_album,
                }),
            });
        } else {
            if (saved_album) {
                emit('albumSaved', saved_album);
            }

            router.reload({ only: ['categories'] });
        }

        initialLoad = true; // We get a new tmp_album_items update when updating the album obj
        unsavedChanges.value = false;
    } finally {
        saving.value = false;
    }
}

let initialLoad = true;
function setTempAlbumItems(items: ListItem[]): void {
    tmp_album_items.value = items;

    if (initialLoad) {
        initialLoad = false;

        return;
    }

    changedValues();
}

function changedValues() {
    unsavedChanges.value = true;
}

const locDistances: Ref<Location[]> = ref([]);
const locations = computed(() => {
    // Clone the stores location list, so we can manipulate the props
    // without tempering with the original list
    const locs = JSON.parse(JSON.stringify(rawLocations.value)) as Location[];

    for (const loc of locDistances.value) {
        const _loc = locs.find((_loc) => _loc.id === loc.id);

        if (_loc) {
            _loc.distance = loc.distance;
        }
    }

    locs.sort((a, b) => {
        if (a.distance && b.distance) {
            return a.distance - b.distance;
        } else if (a.hasOwnProperty('distance')) {
            return -1;
        } else if (b.hasOwnProperty('distance')) {
            return 1;
        }

        return 0;
    });

    return locs;
});
function setLocDistances(locations: Location[]) {
    locDistances.value = locations;

    if (!album.location_id && locations.length > 0) {
        album.location_id = locations[0].id;
    }
}

const cameras: Ref<Camera[]> = ref([]);
const lenses: Ref<Lens[]> = ref([]);
const suggested_tags = computed(() => {
    const list: Set<string> = new Set();

    for (const camera of cameras.value) {
        list.add(camera.brand + ' ' + camera.model);
    }

    for (const lens of lenses.value) {
        list.add(lens.brand + ' ' + lens.model);
    }

    if (album.tags !== undefined) {
        for (const tag of album.tags) {
            list.add(tag);
        }
    }

    return list;
});

function checkAndSetDates(dates: string[]) {
    if (album.date_start != null || album.date_end != null) {
        return;
    }

    const len = dates.length;

    if (len > 0) {
        if (len === 1) {
            // We have just one date for all images, use this!
            album.date_start = dates[0];
            album.date_end = dates[0];
        } else {
            let first_date: Date | null = null;
            let last_date: Date | null = null;
            let has_null = false;

            for (const date of dates) {
                if (date == null) {
                    has_null = true;
                }

                const dt = new Date(date);

                if (first_date == null || first_date > dt) {
                    first_date = dt;
                }

                if (last_date == null || last_date < dt) {
                    last_date = dt;
                }
            }

            let days_delta = 0;

            if (last_date && first_date) {
                days_delta =
                    (last_date.getTime() - first_date.getTime()) / 1000 / 86400;
            }

            if (days_delta < 7 && !has_null) {
                album.date_start =
                    first_date?.toISOString().split('T')[0] ?? null;
                album.date_end = last_date?.toISOString().split('T')[0] ?? null;
            }
        }
    }
}

function imagesUploaded(data: {
    images?: Image[];
    locations?: Location[];
    cameras?: Camera[];
    lenses?: Lens[];
    dates?: string[];
}) {
    if (data.hasOwnProperty('images') && data.images) {
        emit('albumItems', [...album.items, ...data.images]);
    }

    if (data.hasOwnProperty('locations') && data.locations) {
        imageLocations(data.locations);
    }

    if (data.hasOwnProperty('cameras') && data.cameras) {
        cameras.value = [...cameras.value, ...data.cameras].filter(
            (val, idx, arr) => arr.findIndex((a) => a.id === val.id) === idx,
        );
    }

    if (data.hasOwnProperty('lenses') && data.lenses) {
        lenses.value = [...lenses.value, ...data.lenses].filter(
            (val, idx, arr) => arr.findIndex((a) => a.id === val.id) === idx,
        );
    }

    if (data.hasOwnProperty('dates') && data.dates) {
        imageDates(data.dates);
    }
}

function imageDates(dates: string[]) {
    checkAndSetDates(dates);
}

function imageLocations(locations: Location[]) {
    setLocDistances(locations);
}

const placeholders: Ref<UploadPlaceholder[]> = ref([]);
function setPlaceholders(plcs: UploadPlaceholder[]) {
    placeholders.value = plcs;
}

async function insertIntoDescription(text: string) {
    const aDescElm = document.getElementsByName(
        'album_description',
    )[0] as HTMLTextAreaElement;
    const cursor = aDescElm.selectionStart;

    album.description =
        album.description.slice(0, cursor) +
        text +
        album.description.slice(cursor);
}
</script>

<template>
    <h2>
        <template v-if="album.id">Edit {{ album.title }}</template>
        <template v-else>Create new album</template>
    </h2>
    <form @submit.prevent="saveAlbum" class="mb-3">
        <div class="grid grid-cols-1 gap-x-10 lg:grid-cols-12">
            <fieldset class="col-span-7 my-3 fieldset xl:col-span-8">
                <legend class="fieldset-legend">Album Title</legend>
                <input
                    type="text"
                    v-model="album.title"
                    name="album_title"
                    class="input w-full"
                    @change="changedValues"
                />
            </fieldset>
            <fieldset class="col-span-2 my-3 fieldset">
                <legend class="fieldset-legend">Order</legend>
                <input
                    type="number"
                    v-model="album.order"
                    name="album_order"
                    class="input w-full"
                    @change="changedValues"
                />
            </fieldset>
            <div
                class="align-content-end col-span-3 my-3 content-end py-2 xl:col-span-2"
            >
                <label class="label">
                    <input
                        type="checkbox"
                        class="unchecked:bg-error toggle toggle-success"
                        id="album-published"
                        v-model="album.published"
                        name="album_published"
                        @change="changedValues"
                    />
                    Published
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-x-10 lg:grid-cols-2">
            <fieldset class="my-3 fieldset">
                <legend class="fieldset-legend">Start Date</legend>
                <input
                    type="date"
                    v-model="album.date_start"
                    name="album_date_start"
                    class="input w-full"
                    @change="changedValues"
                />
            </fieldset>
            <fieldset class="my-3 fieldset">
                <legend class="fieldset-legend">End Date</legend>
                <input
                    type="date"
                    v-model="album.date_end"
                    name="album_date_end"
                    class="input w-full"
                    @change="changedValues"
                />
            </fieldset>
        </div>
        <div class="grid grid-cols-1 gap-x-10 lg:grid-cols-2">
            <fieldset class="my-3 fieldset">
                <legend class="fieldset-legend">Category</legend>
                <select
                    v-model="album.category_id"
                    name="album_category"
                    class="select w-full"
                    @change="changedValues"
                >
                    <option :value="null">None</option>
                    <option
                        v-for="cat in categories"
                        :key="`cat_${cat.id}`"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </fieldset>
            <fieldset class="my-3 fieldset">
                <legend class="fieldset-legend">Location</legend>
                <select
                    v-model="album.location_id"
                    name="album_location"
                    class="select w-full"
                    @change="changedValues"
                >
                    <option :value="null">None</option>
                    <option
                        v-for="loc in locations"
                        :key="`loc_${loc.id}`"
                        :value="loc.id"
                    >
                        {{ loc.name }}
                        <template v-if="loc.distance">
                            - {{ loc.distance.toFixed(2) }}km away
                        </template>
                    </option>
                </select>
            </fieldset>
        </div>
        <fieldset class="my-3 fieldset">
            <legend class="fieldset-legend">Album Description</legend>
            <textarea
                cols="30"
                rows="10"
                v-model="album.description"
                name="album_description"
                class="textarea w-full"
                @change="changedValues"
            ></textarea>
            <div class="form-text">
                <span
                    v-for="tag in [...suggested_tags]"
                    :key="`tag_${tag}`"
                    @click="insertIntoDescription(tag)"
                    class="text-bg-secondary me-2 badge cursor-pointer"
                >
                    {{ tag }}
                </span>
            </div>
        </fieldset>

        <button type="submit" class="btn me-2 btn-success" :disabled="saving">
            Save Album
        </button>
        <span v-if="unsavedChanges" class="me-2 text-warning"
            >Unsaved changes</span
        >
        <span v-if="saving">Saving ...</span>
    </form>

    <h4>Album items</h4>
    <items
        class="mt-4"
        :items="album.items"
        @album-items="(itms) => emit('albumItems', itms)"
        @list-items="setTempAlbumItems"
        :placeholders="placeholders"
    ></items>

    <image-upload
        @files-uploaded="imagesUploaded"
        @dates="imageDates"
        @locations="imageLocations"
        @placeholders="setPlaceholders"
    ></image-upload>
</template>

<style scoped></style>
