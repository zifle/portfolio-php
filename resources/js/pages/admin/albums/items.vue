<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, useTemplateRef } from 'vue';
import { update as updateImage } from '@/routes/admin/images';
import {
    store as storeText,
    update as updateText,
} from '@/routes/admin/texts';
import type {
    Image,
    ImageListItem,
    ListItem,
    TextBox,
    TextListItem,
} from '@/types/models';

const page = usePage();
const csrf_token = page.props.csrf_token as string;

const props = defineProps(['items']);
const album_items = props.items as (Image | TextBox)[];
const emit = defineEmits(['albumItems', 'listItems']);

let initialLoad = true;
const itemsList = computed(() => {
    const final_items: ListItem[] = [];
    let order = 0;

    for (const item of album_items) {
        order = order + 1;

        const _item: ListItem = {
            id: item.id,
            order: order,
        };

        if (item.hasOwnProperty('paths')) {
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
            _item['srcset'] = srcset.join(',');
            _item['sizes'] = sizes.join(',');
            _item['src'] = item.paths[max_width];
            _item['desc'] =
                item.description || 'Photo#' + item.id + ' in order#' + order;
            _item['description'] = item.description;
        } else if (item.hasOwnProperty('description')) {
            // While both images and text boxes have descriptions,
            // text boxes are almost exclusively descriptions
            _item['type'] = 'text';
            _item['description'] = item.description;
            _item['col_size'] = item.col_size || 1;
        }

        final_items.push(_item);
    }

    if (initialLoad && final_items.length === 0) {
        initialLoad = false;

        return final_items;
    }

    emit('listItems', final_items);

    return final_items;
});

function removeItem(item: TextBox | Image) {
    const new_list: (TextBox | Image)[] = [];

    for (const itm of album_items) {
        if (itm.id !== item.id) {
            new_list.push(itm);
        }
    }

    emit('albumItems', new_list);
}

let dragging: ListItem|null = null;
let draggingOrder: number|null = null;
let dropInOrder: number|null = null;
const sortableList = useTemplateRef('sortable');
onMounted(() => {
    sortableList.value.addEventListener('dragstart', (e) => {
        const itemElm = getDragAfterElement(e.target, sortableList);
        itemElm.classList.add('dragging');
        const itemId = parseInt(itemElm.dataset.id);
        dragging = itemsList.value.find((itm) => itm.id === itemId);
        draggingOrder = dragging.order;
    });
    sortableList.value.addEventListener('dragend', (e) => {
        // Clean up temp dragging vars (this is called after `drop` event, so we're done handling it)
        const itemElm = e.target;
        itemElm.classList.remove('dragging');
        const elms = sortableList.value.querySelectorAll('[draggable=true]');

        for (const elm of elms) {
            elm.classList.remove('over', 'drop-right', 'drop-left');
        }

        dropInOrder = null;
        draggingOrder = null;
        dragging = null;
    });
    sortableList.value.addEventListener('drop', (e) => {
        e.preventDefault();

        if (
            dropInOrder === null ||
            dropInOrder === draggingOrder ||
            dropInOrder === draggingOrder + 1
        ) {
            // We skip handling the drop if we're dropping in either the same order, or +1.
            // +1 order is essentially the same, since it will be "before the next" or "after this"
            return;
        }

        const list = album_items;
        const listLen = list.length;
        const newList: (Image | TextBox)[] = [];
        const moveItem = list.find((itm) => itm.id === dragging.id);

        for (let i = 0; i < listLen; i++) {
            if (i + 1 === dropInOrder) {
                newList.push(moveItem);
            } else if (i + 1 === dragging.order) {
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
    sortableList.value.addEventListener('dragleave', () => {
        dropInOrder = null;
        const elms = sortableList.value.querySelectorAll('[draggable=true]');

        for (const elm of elms) {
            elm.classList.remove('over', 'drop-right', 'drop-left');
        }
    });
    sortableList.value.addEventListener('dragover', (e) => {
        if (e.dataTransfer.types.includes('Files')) {
            // We only handle element reordering here, file drops not included
            return;
        }

        e.preventDefault();
        const draggingOverItemElm = getDragAfterElement(
            e.target,
            sortableList.value,
        );
        const elms = sortableList.value.querySelectorAll('[draggable=true]');

        for (const elm of elms) {
            elm.classList.remove('over', 'drop-right', 'drop-left');
        }

        dropInOrder = null;

        if (draggingOverItemElm) {
            const targetW = e.target.clientWidth;
            const targetCenter = targetW / 3;
            const hoverX = e.offsetX;
            const hoverOrder = parseInt(draggingOverItemElm.dataset.order);
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
function getDragAfterElement(target, container) {
    if (target === document.body) {
        return null;
    }

    if (target === container) {
        return null;
    }

    if (target.attributes['draggable']) {
        return target;
    }

    return getDragAfterElement(target.parentElement, container);
}

async function saveImageDescription(itm: ImageListItem) {
    const path = updateImage(itm.id);
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRFToken': csrf_token,
        },
        body: JSON.stringify(itm),
    });

    return (await response.json()) as Image;
}
async function saveTextBox(itm: TextListItem) {
    const path = itm.id ? updateText(itm.id) : storeText();
    const response = await fetch(path.url, {
        method: path.method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
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
    const new_list = [...album_items];
    new_list.push(itm);
    emit('albumItems', new_list);
}

const editTextId = ref(null);
async function saveText(itm: ImageListItem | TextListItem) {
    let _itm: Image|TextBox;

    if (itm.type === 'image') {
        _itm = await saveImageDescription(itm);
    } else if (itm.type === 'text') {
        _itm = await saveTextBox(itm);
    }

    if (_itm) {
        _itm.order = itm.order;
        const new_items = album_items;

        for (const item of new_items) {
            if (item.id === itm.id) {
                item.id = _itm.id;
                item.description = _itm.description;

                if (_itm.hasOwnProperty('col_size')) {
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

        if (txtField) {
            txtField.focus();
        }
    });
}
</script>

<template>
    <div class="row images" ref="sortable">
        <div
            v-for="itm of itemsList"
            :key="itm.type + itm.id"
            class="col-lg-3 has-hover-controls col-6 mb-3"
            draggable="true"
            :data-order="itm.order"
            :data-id="itm.id"
        >
            <div class="hover-controls">
                <span
                    class="text-bg-danger clickable badge"
                    @click="removeItem(itm)"
                    >X</span
                >
            </div>

            <template v-if="itm.type === 'image'">
                <img
                    loading="lazy"
                    :srcset="itm.srcset"
                    :sizes="itm.sizes"
                    :src="itm.src"
                    class="image-preview"
                    :alt="itm.desc"
                    @dblclick="editText(itm)"
                />
                <textarea
                    v-if="editTextId === itm.id"
                    class="form-control edit-text-field"
                    v-model="itm.description"
                    @blur="saveText(itm)"
                ></textarea>
            </template>
            <template v-else-if="itm.type === 'text'">
                <textarea
                    v-if="editTextId === itm.id"
                    class="form-control edit-text-field h-100"
                    v-model="itm.description"
                    @blur="saveText(itm)"
                ></textarea>
                <pre
                    v-else
                    @dblclick="editText(itm)"
                    class="h-100 cursor-text"
                    >{{ itm.description }}</pre
                >
            </template>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="create-text-box clickable" @click="addTextBox">
                <span class="plus">+</span>
                Add Text
            </div>
        </div>
    </div>
</template>

<style scoped>
.image-preview {
    max-width: 100%;
}

.hover-controls {
    position: absolute;
    top: 0;
    right: 0;
    display: none;
}

.has-hover-controls {
    position: relative;
}
.has-hover-controls:hover > .hover-controls {
    display: block;
}

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

.create-text-box {
    display: flex;
    height: 100%;
    align-items: center;
    justify-content: center;
    user-select: none;
    font-size: 2em;
    border: 5px solid rgba(0, 0, 0, 0.2);
}
.create-text-box > .plus {
    font-size: 1.5em;
}
</style>
