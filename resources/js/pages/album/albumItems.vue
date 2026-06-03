<script setup lang="ts">
import { computed, onUpdated, ref, useTemplateRef, watch } from 'vue';
import { debounce } from '@/lib/debounce';
import { toggleGigante } from '@/lib/gigante/gigante';
import { isImage, isTextBox } from '@/types/models';
import type { AlbumItem } from '@/types/models';

const props = defineProps(['items', 'loading']);
const albumItems = computed(() => {
    return props.items as AlbumItem[];
});
const loading = computed(() => {
    return props.loading as boolean;
});

const emit = defineEmits(['imgLoaded']);

const images = useTemplateRef('images');

watch(loading, () => {
    if (loading.value) {
        const items = images.value?.querySelectorAll('.image,.text-box') ?? [];

        for (const item of items) {
            item.classList.add('opacity-0');
        }
    }
});
function stopLoading() {
    if (images.value !== null) {
        const items = images.value.querySelectorAll('.image,.text-box');

        for (const item of items) {
            item.classList.remove('opacity-0');
        }
    }
}
onUpdated(() => {
    const imgs = images.value?.querySelectorAll('img') ?? [];
    const imgLoadPromises = [];

    for (const img of imgs) {
        if (!img.complete) {
            imgLoadPromises.push(
                new Promise<void>((res) => {
                    img.onload = () => res();
                }),
            );
        } else {
            imgLoadPromises.push(true);
        }
    }

    if (imgLoadPromises.length > 0) {
        Promise.all(imgLoadPromises).then(() => {
            emit('imgLoaded');
            stopLoading();
        });
    }
});

const getNumCols = () => {
    const vw = window.innerWidth;
    let cols = 1;

    if (vw >= 800 && vw < 1200) {
        cols = 3;
    } else if (vw >= 1200 && vw < 1600) {
        cols = 4;
    } else if (vw >= 1600) {
        cols = 5;
    }

    return cols;
};
const numCols = ref(getNumCols());
const resizeDB = debounce(() => {
    numCols.value = getNumCols();
}, 50);
window.addEventListener('resize', resizeDB);

type ImageItem = {
    order: number;
    gridSize: number;
    col: number;
    type: 'image';
    srcset: string;
    sizes: string;
    src: string;
    desc: string;
};
type TextBoxItem = {
    order: number;
    gridSize: number;
    col: number;
    type: 'textbox';
    description: string;
};
type AlbItem = ImageItem | TextBoxItem;

const computedItems = computed(() => {
    const rtn: AlbItem[] = [];
    const vw = window.innerWidth;
    const cols = numCols.value;
    const colWidth = vw / cols;
    let col = 0;

    for (const item of albumItems.value) {
        const _item: { [key: string]: any } = {
            order: item.order as number,
            gridSize: 1,
            col,
        };
        let albItem: AlbItem|undefined = undefined;
        col += 1;

        if (col >= cols) {
            col = 0;
        }

        if (isImage(item)) {
            const srcset = [];
            let min_width = item.max_width;

            for (const width in item.paths) {
                if (item.paths.hasOwnProperty(width)) {
                    const w = parseInt(width);

                    if (w < min_width) {
                        min_width = w;
                    }

                    srcset.push(`${item.paths[width]} ${width}w`);
                }
            }

            // The _slot_ part of the `sizes` simply needs to know how
            // big the image will display
            let maxImgWidth = colWidth * _item.gridSize;

            if (cols < _item.gridSize) {
                maxImgWidth = vw;
            }

            let width = maxImgWidth;

            for (const w in item.paths) {
                const wNum = parseInt(w);

                if (item.paths.hasOwnProperty(w) && wNum >= maxImgWidth) {
                    if (wNum < width) {
                        width = wNum;
                    }
                }
            }

            _item.type = 'image';
            _item.srcset = srcset.join(',');
            _item.sizes = 100 / cols + 'vw';
            _item.src = item.paths ? item.paths[width] : '';
            _item.desc = item.description || 'Photo#' + item.order;
            albItem = _item as ImageItem;
        } else if (isTextBox(item)) {
            _item.type = 'textbox';
            _item.description = item.description;
            albItem = _item as TextBoxItem;
        }

        if (albItem !== undefined) {
            rtn.push(albItem);
        }
    }

    return rtn.toSorted((a, b) => a.order - b.order);
});
</script>

<template>
    <div class="columns-1 px-1 sm:px-2 gap-2 md:columns-2 lg:columns-3 xl:columns-5 *:mb-2 relative z-1" ref="images">
        <div v-for="item of computedItems" :key="'itm_'+item.order" class="image-container">
            <img
                v-if="item.type === 'image'"
                :srcset="item.srcset"
                :sizes="item.sizes"
                :src="item.src"
                class="image w-full opacity-0"
                @click="toggleGigante($event)"
                :alt="item.desc"
            />
            <p v-else-if="item.type === 'textbox'" class="text-box opacity-0">
                {{ item.description }}
            </p>
        </div>
    </div>
    <div id="view-container-container" class="hidden z-100">
        <div id="view-container"></div>
    </div>
</template>

<style>

@media screen and (min-width: 800px) {
    body[data-bs-theme='dark'] .image-container {
        filter: contrast(80%) brightness(80%) grayscale(30%);
    }

    .image-container {
        transition: 0.2s filter linear;
        filter: contrast(70%) brightness(100%) grayscale(30%);
    }

    .image-container:hover {
        filter: contrast(100%) brightness(100%) grayscale(0%) !important;
    }
}

.image-container > img:not(.opacity-0) {
    transition: opacity 0.3s;
}

.text-box {
    white-space: pre-wrap;
}

</style>
