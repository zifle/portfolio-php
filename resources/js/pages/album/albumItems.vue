<script setup lang="ts">
import {
    computed,
    onUpdated,
    onMounted,
    useTemplateRef,
    watch,
    onUnmounted,
} from 'vue';
import { viewedImage } from '@/actions/App/Http/Controllers/ViewController';
import { debounce } from '@/lib/debounce';
import { toggleGigante } from '@/lib/gigante/gigante';
import { smallestImage } from '@/lib/utils';
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

const resizeDB = debounce(() => {
    if (images.value) {
        const imgs = (images.value.querySelectorAll('.image') ??
            []) as NodeListOf<HTMLImageElement>;

        for (const image of imgs) {
            const w = image.getBoundingClientRect().width;
            image.sizes = `(max-width: 767px) 100vw, ${Math.ceil(w)}px`;
        }
    }
}, 100);
let ro: ResizeObserver;
onMounted(() => {
    ro = new ResizeObserver(() => {
        resizeDB();
    });

    if (images.value) {
        ro.observe(images.value);
    }
});
onUnmounted(() => {
    ro.disconnect();
});

type ImageItem = {
    id: number;
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
    let col = 0;
    const sizes = '(max-width: 767px) 100vw';

    for (const item of albumItems.value) {
        const _item: { [key: string]: any } = {
            order: item.order as number,
            gridSize: 1,
            col,
        };
        let albItem: AlbItem | undefined = undefined;
        col += 1;

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

            // Calculate the absolute minimum width the image can be, for the
            // initial load. The `240`px is based on the calculated min-height
            // from `md:min-h-60`, and should be updated if that does.
            const minWidth = Math.round(
                (item.max_width / item.max_height) * 240,
            );

            _item.type = 'image';
            _item.srcset = srcset.join(',');
            _item.sizes = sizes + ', ' + minWidth + 'px';
            _item.src = smallestImage(item) ?? '';
            _item.aspect = item.max_width / item.max_height;
            _item.desc = item.description ?? '';
            _item.width = item.max_width;
            _item.height = item.max_height;
            _item.id = item.id;
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

function showFullImage(id: number, ev: PointerEvent) {
    toggleGigante(ev);

    if ('sendBeacon' in navigator) {
        try {
            navigator.sendBeacon(viewedImage(id).url);
        } catch {}
    }
}
</script>

<template>
    <div
        class="relative z-1 content-stretch justify-evenly gap-2 px-1 *:mb-2 sm:px-2 md:flex md:flex-wrap"
        ref="images"
    >
        <div
            v-for="item of computedItems"
            :key="'itm_' + item.order"
            class="image-container w-full md:max-h-[80vh] md:min-h-60 md:w-min xl:min-h-96"
            :style="'aspect' in item ? `aspect-ratio: ${item.aspect};` : ''"
            style="flex: 1 1 auto; max-width: fit-content"
            @click="
                item.type === 'image' ? showFullImage(item.id, $event) : null
            "
        >
            <img
                v-if="item.type === 'image'"
                :srcset="item.srcset"
                :sizes="item.sizes"
                :src="item.src"
                class="image h-full object-contain opacity-0"
                :alt="item.desc"
                :width="item.width"
                :height="item.height"
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

.image-container .image {
    user-drag: none; /* Disable dragging */
    user-select: none; /* Disable selection */
    -webkit-user-drag: none; /* For WebKit browsers (Chrome and Safari) */
    -webkit-user-select: none;
    -moz-user-select: none; /* For Firefox */
    -ms-user-select: none; /* For Internet Explorer */
    pointer-events: none;
}
</style>
