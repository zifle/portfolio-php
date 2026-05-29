<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ExifReader from 'exifreader';
import { onMounted, onUnmounted, ref, useTemplateRef } from 'vue';
import { store as storeImage, checkDuplicates } from '@/routes/admin/images';
import type { Camera, Image, Lens, Location } from '@/types/models';

const emit = defineEmits(['filesUploaded', 'locations', 'dates']);

const fileUpload = useTemplateRef('fileUpload');
const uploading = ref(false);
onMounted(() => {
    fileUpload.value.addEventListener('change', (e) => {
        const files = e.target.files;
        uploadImages(files);
    });
});

const page = usePage();
const csrf_token = page.props.csrf_token as string;
async function doUploadImages(data: FormData) {
    const path = storeImage();
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: data,
    });

    return (await response.json()) as {
        success: boolean;
        cameras: Camera[];
        lenses: Lens[];
        images: Image[];
        coords: [number, number];
        date: string;
    } | {
        success: boolean;
        cameras: Camera[];
        lenses: Lens[];
        images: Image[];
        locations: Location[];
        dates: string;
    };
}
async function checkImageDuplicates(props) {
    const path = checkDuplicates();
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify(props),
    });

    return (await response.json()) as {
        success: boolean;
        cameras: Camera[];
        locations?: Location[];
        upload?: string[];
    };
}

async function uploadImages(files: File[]) {
    uploading.value = true;

    try {
        const props = await getImageDuplicationProps(files);
        const check = await checkImageDuplicates(props);

        if (check.hasOwnProperty('locations')) {
            emit('filesUploaded', check);
        }

        if (check.hasOwnProperty('upload') && check.upload.length === 0) {
            // All images are already on the server, no need to send them again
            return;
        }

        let coords = [];
        const dates = [];
        const uploadsPromises = [];

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                continue;
            }

            if (!check.upload.includes(file.name)) {
                continue;
            }

            const data = new FormData();
            data.append('img', file);
            const prom = doUploadImages(data).then((upload_data) => {
                coords = [...coords, ...upload_data.coords];

                if (upload_data.date !== null) {
                    dates.push(upload_data.date);
                }

                emit('filesUploaded', upload_data);
            });
            uploadsPromises.push(prom);
        }

        await Promise.all(uploadsPromises);

        if (coords.length > 0) {
            const locStore = useLocationStore();
            locStore.getNearbyLocations(coords).then((locations) => {
                emit('locations', locations);
            });
        }

        if (dates.length > 0) {
            emit(
                'dates',
                dates.filter((val, idx, arr) => arr.indexOf(val) === idx),
            );
        }
    } finally {
        uploading.value = false;
    }
}

async function getImageDuplicationProps(files: File[]) {
    const promises = [];

    for (const file of files) {
        if (!file.type.startsWith('image/')) {
            continue;
        }

        const prom = new Promise(async (res) => {
            const exif = await ExifReader.load(file);

            let date;

            if (exif.hasOwnProperty('DateTimeOriginal')) {
                date = exif.DateTimeOriginal.description;
            } else if (exif.hasOwnProperty('DateTimeDigitized')) {
                date = exif.DateTimeDigitized.description;
            } else if (exif.hasOwnProperty('DateTime')) {
                date = exif.DateTime.description;
            }

            if (date) {
                // Fix the date format (uses : instead of - to separate y-m-d)
                const [d, t] = date.split(' ');
                date = d.replaceAll(':', '-') + 'T' + t;
            }

            let offset = '+0000';

            if (exif.hasOwnProperty('OffsetTimeOriginal')) {
                offset = exif.OffsetTimeOriginal.description.replace(':', '');
            } else if (exif.hasOwnProperty('OffsetTime')) {
                offset = exif.OffsetTime.description.replace(':', '');
            }

            const date_taken = new Date(date + offset).toISOString();

            let location = null;

            if (
                exif.hasOwnProperty('GPSLatitude') &&
                exif.hasOwnProperty('GPSLongitude')
            ) {
                location = [
                    parseFloat(exif.GPSLatitude.description),
                    parseFloat(exif.GPSLongitude.description),
                ];
            }

            res({ filename: file.name, date_taken, location });
        });
        promises.push(prom);
    }

    return await Promise.all(promises);
}

const dropZone = useTemplateRef('fileDropZone');
function dragoverFile(e) {
    const fileItems = [...e.dataTransfer.items].filter(
        (i) => i.kind === 'file',
    );

    if (fileItems.length > 0) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        dropZone.value.classList.remove('hidden');
    } else {
        dropZone.value.classList.add('hidden');
    }
}
function dropFiles(e) {
    const fileItems = [...e.dataTransfer.items].filter(
        (i) => i.kind === 'file',
    );

    if (fileItems.length > 0) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        uploadImages(fileItems.map((i) => i.getAsFile()));
        dragEnd();
    }
}
function dragEnd() {
    dropZone.value.classList.add('hidden');
}
onMounted(() => {
    document.body.addEventListener('dragover', dragoverFile);
    document.body.addEventListener('dragend', dragEnd);
    document.body.addEventListener('dragleave', dragEnd);
    dropZone.value.addEventListener('drop', dropFiles);
});
onUnmounted(() => {
    document.body.removeEventListener('dragover', dragoverFile);
    document.body.removeEventListener('dragend', dragEnd);
    document.body.removeEventListener('dragleave', dragEnd);
});
</script>

<template>
    <fieldset class="fieldset my-3">
        <legend class="fieldset-legend">Upload Images</legend>
        <input
            type="file"
            multiple
            class="file-input"
            ref="fileUpload"
        />
    </fieldset>

    <div v-if="uploading" class="uploading fixed">
        Uploading images ...
    </div>

    <div class="file-dropzone flex hidden" ref="fileDropZone">+</div>
</template>

<style scoped>
.file-dropzone {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    font-size: 20rem;
    user-select: none;
}

.uploading {
    bottom: 20px;
    right: 20px;
    padding: 10px 20px;
    background-color: var(--background);
    border: 2px solid var(--input-color);
}
</style>
