<script setup lang="ts">
import {
    computed,
    onUpdated,
    onMounted,
    ref,
    useTemplateRef,
    watch,
    onUnmounted,
} from 'vue';
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
    if (!loading.value) {
        return stopLoading();
    }

    const imgs = images.value?.querySelectorAll('img') ?? [];
    const imgLoadPromises = [];

    for (const img of imgs) {
        if (!img.complete) {
            imgLoadPromises.push(
                new Promise<void>((res) => {
                    img.onload = () => {
                        img.classList.remove('opacity-0');
                        res();
                    };
                }),
            );
        } else {
            imgLoadPromises.push(true);
        }
    }

    if (imgLoadPromises.length > 0) {
        Promise.any(imgLoadPromises).then(() => {
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
const numCols = ref();
const resizeDB = debounce(() => {
    numCols.value = getNumCols();
}, 50);
onMounted(() => {
    numCols.value = getNumCols();
    window.addEventListener('resize', resizeDB);
});
onUnmounted(() => {
    window.removeEventListener('resize', resizeDB);
});

type ImageItem = {
    order: number;
    gridSize: number;
    col: number;
    type: 'image';
    srcset: string;
    sizes: string;
    src: string;
    desc: string;
    aspect: number;
    width: number;
    height: number;
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
    const vw = typeof window !== 'undefined' ? window.innerWidth : 2000;
    const cols = numCols.value;
    const colWidth = vw / cols;
    let col = 0;
    const sizes =
        '(max-width: 767px) 100vw, (max-width: 1024px) 50vw, (max-width: 1280px) 33vw, 20vw';

    for (const item of albumItems.value) {
        const _item: { [key: string]: any } = {
            order: item.order as number,
            gridSize: 1,
            col,
        };
        let albItem: AlbItem | undefined = undefined;
        col += 1;

        if (col >= cols) {
            col = 0;
        }

        if (isImage(item)) {
            let srcset = [];

            if (item.srcset) {
                srcset = item.srcset;
            } else {
                for (const width in item.paths) {
                    if (item.paths.hasOwnProperty(width)) {
                        srcset.push(`${item.paths[width]} ${width}w`);
                    }
                }
            }

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
            _item.sizes = sizes;
            _item.src = item.paths[width];
            _item.aspect = item.max_width / item.max_height;
            _item.desc = item.description || 'Photo#' + item.order;
            _item.width = item.max_width;
            _item.height = item.max_height;
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
    <div
        class="relative z-1 columns-1 gap-2 px-1 *:mb-2 sm:px-2 md:columns-2 lg:columns-3 xl:columns-5"
        ref="images"
    >
        <div
            v-for="item of computedItems"
            :key="'itm_' + item.order"
            class="image-container"
        >
            <img
                v-if="item.type === 'image'"
                :srcset="item.srcset"
                :sizes="item.sizes"
                :src="item.src"
                class="image w-full opacity-0"
                @click="toggleGigante($event)"
                :alt="item.desc"
                :width="item.width"
                :height="item.height"
                :style="`aspect-ratio: ${item.aspect};`"
                decoding="async"
                loading="lazy"
            />
            <p v-else-if="item.type === 'textbox'" class="text-box opacity-0">
                {{ item.description }}
            </p>
        </div>
    </div>
    <div id="view-container-container" class="z-100 hidden">
        <div id="view-container"></div>
    </div>
</template>

<style>
@media screen and (min-width: 800px) {
    .image-container {
        transition: 150ms filter linear;
    }

    .image-container:hover {
        filter: contrast(90%) !important;
    }
}

.image-container > img:not(.opacity-0) {
    transition: opacity 0.3s;
}

.text-box {
    white-space: pre-wrap;
}
</style>
