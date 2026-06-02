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
    fileUpload.value?.addEventListener('change', (e: Event) => {
        const files = (e.target as HTMLInputElement).files;

        if (files !== null) {
            uploadImages(files);
        }
    });
});

const page = usePage();
const csrf_token = page.props.csrf_token as string;
type SingleImageUploadReturn = {
    success: boolean;
    cameras: Camera[];
    lenses: Lens[];
    images: Image[];
    coords: [number, number][];
    date: string;
};
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

    return (await response.json()) as SingleImageUploadReturn;
}

type ImageDupeProps = {
    filename: string;
    date_taken: string;
    location: null | number[];
};
async function checkImageDuplicates(props: ImageDupeProps[]) {
    const path = checkDuplicates();
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify({ images: props }),
    });

    return (await response.json()) as {
        success: boolean;
        cameras: Camera[];
        locations?: Location[];
        upload?: string[];
    };
}

async function uploadImages(files: FileList | File[]) {
    uploading.value = true;

    try {
        const props = await getImageDuplicationProps(files);
        const check = await checkImageDuplicates(props);

        if (check.hasOwnProperty('locations')) {
            emit('filesUploaded', check);
        }

        if (check.upload?.length === 0) {
            // All images are already on the server, no need to send them again
            return;
        }

        let coords: [number, number][] = [];
        const dates: string[] = [];
        const uploadsPromises = [];

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                continue;
            }

            if (check.upload && !check.upload.includes(file.name)) {
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

async function getImageDuplicationProps(
    files: FileList | File[],
): Promise<ImageDupeProps[]> {
    const promises = [];

    for (const file of files) {
        if (!file.type.startsWith('image/')) {
            continue;
        }

        const prom: Promise<ImageDupeProps | null> = new Promise(
            async (res) => {
                const exif = await ExifReader.load(file);

                let date;

                if (exif.hasOwnProperty('DateTimeOriginal')) {
                    date = exif.DateTimeOriginal?.description;
                } else if (exif.hasOwnProperty('DateTimeDigitized')) {
                    date = exif.DateTimeDigitized?.description;
                } else if (exif.hasOwnProperty('DateTime')) {
                    date = exif.DateTime?.description;
                }

                if (date === undefined) {
                    res(null);

                    return;
                }

                // Fix the date format (uses : instead of - to separate y-m-d)
                const [d, t] = date.split(' ');
                date = d.replaceAll(':', '-') + 'T' + t;

                const date_taken = new Date(date+'+0000').toISOString();

                let location = null;

                if (exif.GPSLatitude && exif.GPSLongitude) {
                    location = [
                        parseFloat(exif.GPSLatitude.description),
                        parseFloat(exif.GPSLongitude.description),
                    ];
                }

                res({ filename: file.name, date_taken, location });
            },
        );
        promises.push(prom);
    }

    return (await Promise.all(promises)).filter((item) => item !== null);
}

const dropZone = useTemplateRef('fileDropZone');
function dragoverFile(e: DragEvent) {
    if (e.dataTransfer) {
        const fileItems = [...e.dataTransfer.items].filter(
            (i) => i.kind === 'file' && i.type,
        );

        if (fileItems.length > 0) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            dropZone.value?.classList.add('flex');
        } else {
            dropZone.value?.classList.remove('flex');
        }
    } else {
        dropZone.value?.classList.remove('flex');
    }
}
function dropFiles(e: DragEvent) {
    if (e.dataTransfer) {
        const fileItems = [...e.dataTransfer.items].filter(
            (i) => i.kind === 'file' && i.type,
        );

        if (fileItems.length > 0) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            const files = fileItems
                .map((i) => i.getAsFile())
                .filter((i) => i !== null);
            uploadImages(files);
            dragEnd();
        }
    }
}
function dragEnd() {
    dropZone.value?.classList.remove('flex');
}
onMounted(() => {
    document.body.addEventListener('dragover', dragoverFile);
    document.body.addEventListener('dragend', dragEnd);
    dropZone.value?.addEventListener('dragleave', dragEnd);
    dropZone.value?.addEventListener('drop', dropFiles);
});
onUnmounted(() => {
    document.body.removeEventListener('dragover', dragoverFile);
    document.body.removeEventListener('dragend', dragEnd);
    dropZone.value?.removeEventListener('dragleave', dragEnd);
});
</script>

<template>
    <fieldset class="my-3 fieldset">
        <legend class="fieldset-legend">Upload Images</legend>
        <input type="file" multiple class="file-input" ref="fileUpload" />
    </fieldset>

    <div v-if="uploading" class="uploading fixed">Uploading images ...</div>

    <div class="file-dropzone z-20" ref="fileDropZone">+</div>
</template>

<style scoped>
.file-dropzone {
    display: none;
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
.file-dropzone.flex {
    display: flex;
}

.uploading {
    bottom: 20px;
    right: 20px;
    padding: 10px 20px;
    background-color: var(--background);
    border: 2px solid var(--input-color);
}
</style>
