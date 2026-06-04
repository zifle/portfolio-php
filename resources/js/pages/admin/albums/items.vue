<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { update as updateImage } from '@/routes/admin/images';
import { store as storeText, update as updateText } from '@/routes/admin/texts';
import type { UploadPlaceholder } from '@/types';
import {
    albumItemType,
    isImage,
    isImageListItem,
    isTextBox,
    isTextListItem,
} from '@/types/models';
import type {
    Image,
    ImageListItem,
    ListItem,
    AlbumListItem,
    AlbumItem,
    TextBox,
    TextListItem,
} from '@/types/models';

const page = usePage();
const csrf_token = page.props.csrf_token as string;

const props = defineProps(['items', 'placeholders']);
const album_items = computed(() => {
    return props.items as AlbumItem[];
});
const placeholders = computed(() => {
    return props.placeholders as UploadPlaceholder[];
});
const emit = defineEmits(['albumItems', 'listItems']);

let initialLoad = true;
const itemsList = computed(() => {
    const final_items: AlbumListItem[] = [];
    let order = 0;

    for (const item of album_items.value) {
        order = order + 1;

        let _item = {
            id: item.id,
            order: order,

            // temp prop values
            type: albumItemType(item) ?? 'image',
            description: '',
        } as AlbumListItem;

        if (isImage(item)) {
            const srcset: string[] = [];
            const sizes: string[] = [];
            const max_width = item.max_width;

            for (const width in item.paths) {
                if (item.paths.hasOwnProperty(width)) {
                    srcset.push(`${item.paths[width]} ${width}w`);
                    // todo We could check whether we're in a viewport
                    //       that uses a smaller column size for the
                    //       images, and thus specify a better width
                    sizes.push(`(width <= ${width}px) ${width}px`);
                }
            }

            _item['type'] = 'image';

            if (isImageListItem(_item)) {
                _item['src'] = item.paths[max_width];

                _item['srcset'] = srcset.join(',');
                _item['sizes'] = sizes.join(',');
                _item['desc'] =
                    item.description ||
                    'Photo#' + item.id + ' in order#' + order;
                _item['description'] = item.description;
            }
        } else if (isTextBox(item)) {
            // While both images and text boxes have descriptions,
            // text boxes are almost exclusively descriptions
            _item = _item as TextListItem;
            _item['type'] = 'textbox';

            if (isTextListItem(_item)) {
                _item['description'] = item.description;
                _item['col_size'] = item.col_size || 1;
            }
        }

        final_items.push(_item);
    }

    if (initialLoad && final_items.length === 0) {
        initialLoad = false;

        return final_items;
    }

    initialLoad = false;

    emit('listItems', final_items);

    return final_items;
});

function removeItem(item: AlbumListItem) {
    const new_list: AlbumItem[] = [];

    for (const itm of album_items.value) {
        if (itm.id !== item.id) {
            new_list.push(itm);
        } else if (item.type !== albumItemType(itm)) {
            new_list.push(itm);
        }
    }

    emit('albumItems', new_list);
}

let dragging: ListItem | undefined;
let draggingOrder: number | undefined;
let dropInOrder: number | undefined;
const sortableList = useTemplateRef('sortable');
onMounted(() => {
    sortableList.value?.addEventListener('dragstart', (e: DragEvent) => {
        if (!(e.target instanceof HTMLElement)) {
            return;
        }

        const itemElm = getDragAfterElement(
            e.target,
            sortableList.value as HTMLDivElement,
        );

        if (itemElm instanceof HTMLElement) {
            const itemId = parseInt(itemElm.dataset.id ?? '-1');
            dragging = itemsList.value.find((itm) => itm.id === itemId);

            if (dragging) {
                itemElm.classList.add('dragging');
                draggingOrder = dragging.order;
            }
        }
    });
    sortableList.value?.addEventListener('dragend', (e: DragEvent) => {
        // Clean up temp dragging vars (this is called after `drop` event, so we're done handling it)
        const itemElm = getDragAfterElement(
            e.target as HTMLElement,
            sortableList.value as HTMLDivElement,
        );

        if (itemElm instanceof Element) {
            itemElm.classList.remove('dragging');
        }

        const elms = sortableList.value?.querySelectorAll('[draggable=true]');

        if (elms?.length) {
            for (const elm of elms) {
                elm.classList.remove('over', 'drop-right', 'drop-left');
            }
        }

        dropInOrder = undefined;
        draggingOrder = undefined;
        dragging = undefined;
    });
    sortableList.value?.addEventListener('drop', (e: DragEvent) => {
        e.preventDefault();

        if (
            dropInOrder === undefined ||
            dropInOrder === draggingOrder ||
            draggingOrder === undefined ||
            dropInOrder === draggingOrder + 1
        ) {
            // We skip handling the drop if we're dropping in either the same order, or +1.
            // +1 order is essentially the same, since it will be "before the next" or "after this"
            return;
        }

        const list = album_items.value;
        const listLen = list.length;
        const newList: AlbumItem[] = [];
        const moveItem = list.find((itm) => itm.id === dragging?.id);

        if (moveItem === undefined) {
            return;
        }

        for (let i = 0; i < listLen; i++) {
            if (i + 1 === dropInOrder) {
                newList.push(moveItem);
            } else if (i + 1 === dragging?.order) {
                continue;
            }

            newList.push(list[i]);
        }

        if (dropInOrder > listLen) {
            // Make sure we append the item, if it was placed last
            newList.push(moveItem);
        }

        let order = 0;

        for (const itm of newList) {
            itm.order = order = order + 1;
        }

        emit('albumItems', newList);
    });
    sortableList.value?.addEventListener('dragleave', (e: DragEvent) => {
        if (e.target instanceof HTMLElement) {
            const t = getDragAfterElement(
                e.target,
                sortableList.value as HTMLDivElement,
            );

            if (t instanceof Element) {
                return;
            }
        }

        dropInOrder = undefined;
        const elms = sortableList.value?.querySelectorAll('[draggable=true]');

        if (elms?.length) {
            for (const elm of elms) {
                elm.classList.remove('over', 'drop-right', 'drop-left');
            }
        }
    });
    sortableList.value?.addEventListener('dragover', (e: DragEvent) => {
        const fileItems = e.dataTransfer
            ? [...e.dataTransfer.items].filter(
                  (i) => i.kind === 'file' && i.type,
              )
            : [];

        if (fileItems.length > 0 || !(e.target instanceof HTMLElement)) {
            // We only handle element reordering here, file drops not included
            return;
        }

        e.preventDefault();
        const draggingOverItemElm = getDragAfterElement(
            e.target,
            sortableList.value as HTMLDivElement,
        );
        const elms = sortableList.value?.querySelectorAll('[draggable=true]');

        if (elms?.length) {
            for (const elm of elms) {
                elm.classList.remove('over', 'drop-right', 'drop-left');
            }
        }

        dropInOrder = undefined;

        if (draggingOverItemElm) {
            const targetW = e.target.clientWidth;
            const targetCenter = targetW / 3;
            const hoverX = e.offsetX;
            const hoverOrder = parseInt(
                draggingOverItemElm.dataset.order ?? '-1',
            );
            const cls = ['over'];

            if (hoverX < targetCenter) {
                // Dropping before elm
                cls.push('drop-left');
                dropInOrder = hoverOrder;
            } else if (hoverX > targetCenter * 2) {
                // Dropping after elm
                cls.push('drop-right');
                dropInOrder = hoverOrder + 1;
            }

            draggingOverItemElm.classList.add(...cls);
        }
    });
});
function getDragAfterElement(
    target: HTMLElement,
    container: HTMLElement,
): HTMLElement | null {
    if (target === document.body) {
        return null;
    }

    if (target === container) {
        return null;
    }

    if (target.getAttribute('draggable')) {
        return target;
    }

    if (target.parentElement) {
        return getDragAfterElement(target.parentElement, container);
    }

    return null;
}

async function saveImageDescription(itm: ImageListItem) {
    const path = updateImage(itm.id);
    const itmCopy = {
        id: itm.id,
        description: itm.description,
    }
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify(itmCopy),
    });

    return (await response.json()) as Image;
}
async function saveTextBox(itm: TextListItem) {
    const path = itm.id ? updateText(itm.id) : storeText();
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify(itm),
    });

    return (await response.json()) as TextBox;
}
function addTextBox() {
    const defaultText = 'Add some text here ...';
    const last = itemsList.value[itemsList.value.length - 1];
    const itm = {
        id: 0,
        order: last.order + 1,
        description: defaultText,
        col_size: 1,
    };
    const new_list = [...album_items.value];
    new_list.push(itm);
    emit('albumItems', new_list);
}

const editTextId: Ref<number | null> = ref(null);
async function saveText(itm: ImageListItem | TextListItem) {
    let _itm: AlbumItem;

    if (isImageListItem(itm)) {
        _itm = await saveImageDescription(itm);
    } else if (isTextListItem(itm)) {
        _itm = await saveTextBox(itm);
    } else {
        editTextId.value = null;

        return;
    }

    if (_itm) {
        _itm.order = itm.order;
        const new_items = album_items.value;

        for (const item of new_items) {
            if (item.id === itm.id) {
                item.id = _itm.id;
                item.description = _itm.description;

                if (isTextBox(_itm) && isTextBox(item)) {
                    item.col_size = _itm.col_size;
                }

                break;
            }
        }

        emit('albumItems', new_items);
    }

    editTextId.value = null;
}
function editText(itm: ListItem) {
    editTextId.value = itm.id;
    nextTick(() => {
        const txtField = document.querySelector('.edit-text-field');

        if (txtField instanceof HTMLTextAreaElement) {
            txtField.focus();
        }
    });
}
</script>

<template>
    <div
        class="images grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6"
        ref="sortable"
    >
        <div
            v-for="itm of itemsList"
            :key="itm.type + itm.id"
            class="group relative"
            draggable="true"
            :data-order="itm.order"
            :data-id="itm.id"
        >
            <div
                class="hover-controls absolute top-0 right-0 hidden group-hover:block"
            >
                <span class="btn btn-xs btn-error" @click="removeItem(itm)"
                    >X</span
                >
            </div>

            <template v-if="isImageListItem(itm)">
                <img
                    loading="lazy"
                    :srcset="itm.srcset"
                    :sizes="itm.sizes"
                    :src="itm.src"
                    class="max-w-full"
                    :alt="itm.desc"
                    @dblclick="editText(itm)"
                />
                <textarea
                    v-if="editTextId === itm.id"
                    class="edit-text-field textarea"
                    v-model="itm.description"
                    @blur="saveText(itm)"
                ></textarea>
            </template>
            <template v-else-if="isTextListItem(itm)">
                <textarea
                    v-if="editTextId === itm.id"
                    class="edit-text-field textarea h-full"
                    v-model="itm.description"
                    @blur="saveText(itm)"
                ></textarea>
                <pre
                    v-else
                    @dblclick="editText(itm)"
                    class="h-full cursor-text"
                    >{{ itm.description }}</pre
                >
            </template>
        </div>
        <div
            v-for="plc of placeholders"
            :key="`plc_${plc.filename}`"
            class="group relative opacity-40">
            <img :src="plc.src" alt="" class="max-w-full">
            <progress class="progress w-full" :value="plc.progress" :max="plc.size"></progress>
        </div>
        <div class="">
            <div
                class="flex h-full cursor-pointer items-center justify-center border-3 border-gray-600 text-3xl select-none"
                @click="addTextBox"
            >
                <span class="text-5xl">+</span>
                Add Text
            </div>
        </div>
    </div>
</template>

<style>
.over::after,
.over::before {
    content: '';
    display: block;
    position: absolute;
    width: 33%;
    height: 100%;
    background-color: rgba(33, 33, 33, 0.3);
    top: 0;
}
.over::before {
    left: 0;
}
.over::after {
    right: 0;
}
.over.drop-left::before {
    background-color: rgba(0, 255, 0, 0.3);
}
.over.drop-right::after {
    background-color: rgba(0, 255, 0, 0.3);
}

.dragging {
    filter: grayscale(1);
    opacity: 0.4;
}
</style>
