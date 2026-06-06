<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ExifReader from 'exifreader';
import { onMounted, onUnmounted, reactive, useTemplateRef } from 'vue';
import { store as storeImage, checkDuplicates } from '@/routes/admin/images';
import type { UploadPlaceholder } from '@/types';
import type { Camera, Image, Lens, Location } from '@/types/models';

const emit = defineEmits([
    'filesUploaded',
    'locations',
    'dates',
    'placeholders',
]);

const fileUpload = useTemplateRef('fileUpload');
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

const upload = reactive({
    uploading: false,
    done: false,
    totalSize: 0,
    totalProgress: undefined,
    fileProgress: [],
    files: 0,
    files_done: 0,
} as {
    uploading: boolean;
    done: boolean;
    totalSize: number;
    totalProgress: number | undefined;
    fileProgress: { filename: string; progress: number }[];
    files: number;
    files_done: number;
});
let uploadPlaceholders: UploadPlaceholder[] = [];
async function doUploadImages(
    file: File,
    placeholder: UploadPlaceholder,
): Promise<SingleImageUploadReturn> {
    const data = new FormData();
    data.append('img', file);
    const path = storeImage();

    return await new Promise((res, rej) => {
        const xhr = new XMLHttpRequest();
        xhr.responseType = 'json';

        xhr.upload.addEventListener('progress', (e) => {
            if (upload.totalProgress) {
                upload.totalProgress += e.loaded;
            } else {
                upload.totalProgress = e.loaded;
            }

            placeholder.progress = e.loaded;
            const fp = upload.fileProgress.find(
                (f) => f.filename === placeholder.filename,
            );

            if (fp) {
                fp.progress = e.loaded;
                upload.totalProgress = upload.fileProgress
                    .map((fp) => fp.progress)
                    .reduce((ac, cv) => ac + cv, 0);
            }
        });

        xhr.addEventListener('load', function () {
            res(this.response as SingleImageUploadReturn);
            const fp = upload.fileProgress.find(
                (f) => f.filename === placeholder.filename,
            );

            if (fp) {
                fp.progress = placeholder.size;
                upload.totalProgress = upload.fileProgress
                    .map((fp) => fp.progress)
                    .reduce((ac, cv) => ac + cv, 0);
            }
        });

        xhr.addEventListener('error', () => {
            rej();
        });

        xhr.open(path.method, path.url);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRFToken', csrf_token);
        xhr.send(data);
    });
}

type ImageDupeProps = {
    filename: string;
    date_taken: string;
    location: null | number[];
};
async function checkImageDuplicates(props: ImageDupeProps[]) {
    if (props.length > 0) {
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

    return {
        success: false,
        cameras: [],
        locations: [],
        upload: [],
    };
}

async function uploadImages(files: FileList | File[]) {
    // Reset uploading alert
    upload.uploading = true;
    upload.done = false;
    upload.totalSize = 0;
    upload.totalProgress = undefined;
    upload.files = upload.files_done = 0;
    upload.fileProgress = [];
    uploadPlaceholders = [];

    try {
        console.log(files);
        const props = await getImageDuplicationProps(files);
        const check = await checkImageDuplicates(props);

        if (check.hasOwnProperty('locations')) {
            emit('filesUploaded', check);
        }

        if (check.upload?.length === 0) {
            // All images are already on the server, no need to send them again
            return;
        }

        upload.totalProgress = 0;

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

            const placeholder = reactive({
                filename: file.name,
                progress: 0,
                size: file.size,
                src: '',
            } as UploadPlaceholder);
            upload.totalSize += file.size;
            upload.files += 1;
            upload.fileProgress.push({
                filename: placeholder.filename,
                progress: 0,
            });
            getImageThumbnail(file).then((b64) => (placeholder.src = b64));

            const prom = doUploadImages(file, placeholder).then(
                (upload_data) => {
                    coords = [...coords, ...upload_data.coords];

                    if (upload_data.date !== null) {
                        dates.push(upload_data.date);
                    }

                    const plcIdx = uploadPlaceholders.findIndex(
                        (a) => a.filename === file.name,
                    );
                    upload.totalSize -= uploadPlaceholders[plcIdx].size;
                    uploadPlaceholders.splice(plcIdx, 1);
                    upload.files_done++;
                    emit('filesUploaded', upload_data);
                },
            );
            uploadPlaceholders.push(placeholder);
            uploadsPromises.push(prom);
        }

        if (uploadPlaceholders.length > 0) {
            emit('placeholders', uploadPlaceholders);
        }

        await Promise.all(uploadsPromises);

        if (dates.length > 0) {
            emit(
                'dates',
                dates.filter((val, idx, arr) => arr.indexOf(val) === idx),
            );
        }
    } finally {
        upload.done = true;
        upload.totalProgress = upload.totalSize;
        setTimeout(() => {
            upload.uploading = false;
            upload.done = false;
            upload.files = upload.files_done = 0;
        }, 5000);
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
                delete exif['MakerNote'];

                let date: string|Date|undefined;

                if (exif.hasOwnProperty('DateTimeOriginal')) {
                    date = exif.DateTimeOriginal?.description;
                } else if (exif.hasOwnProperty('DateTimeDigitized')) {
                    date = exif.DateTimeDigitized?.description;
                } else if (exif.hasOwnProperty('DateTime')) {
                    date = exif.DateTime?.description;
                } else {
                    // Fallback to the file modified date
                    date = new Date(file.lastModified);
                }

                if (date === undefined) {
                    res(null);

                    return;
                }

                if (typeof date === 'string') {
                    // Fix the date format (uses : instead of - to separate y-m-d)
                    const [d, t] = date.split(' ');
                    date = d.replaceAll(':', '-') + 'T' + t;
                    date = new Date(date + '+0000');
                }

                const date_taken = date.toISOString();

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

async function getImageThumbnail(file: File): Promise<string> {
    return new Promise(async (res) => {
        const tags = await ExifReader.load(file);

        delete tags['MakerNote'];

        if (tags['Thumbnail'] && tags['Thumbnail'].image) {
            res('data:image/jpg;base64,' + tags.Thumbnail?.base64);
        } else {
            const reader = new FileReader();
            reader.onloadend = () => {
                res(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    });
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

    <div class="toast">
        <div
            v-if="upload.uploading"
            class="alert alert-vertical sm:alert-horizontal"
            :class="{ 'alert-success': upload.done }"
            role="alert"
        >
            <div>
                <h3 class="flex justify-between font-bold">
                    <template v-if="upload.done"> Upload completed! </template>
                    <template v-else>
                        <span>Uploading images ...</span>
                        <span>
                            {{ upload.files_done }}/{{ upload.files }} uploaded
                        </span>
                    </template>
                </h3>
                <progress
                    class="progress w-xl"
                    :value="upload.totalProgress"
                    :max="upload.totalSize"
                ></progress>
            </div>
        </div>
    </div>

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
