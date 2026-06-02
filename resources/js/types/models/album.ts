import type { Category } from './category';
import type { Image } from './image';
import type { Location } from './location';

export type Album = {
    id: number;
    title: string;
    slug: string;
    order: number;
    description: string;
    category_id: number|null;
    location_id: number|null;
    date_start: string|null;
    date_end: string|null;
    published_at: string|null;
    archived_at: string|null;

    created_at?: string;
    updated_at?: string;

    images_count?: number;
    text_boxes_count?: number;
    published: boolean;

    location?: Location;
    category?: Category;
    images?: Image[];
    items: AlbumItem[];
};

export type AlbumItem = Image|TextBox;
export type AlbumItemShortType = 'image' | 'textbox';
export type AlbumItemPivot = {
    album_item_type: 'App\\Models\\Image' | 'App\\Models\\TextBox';
    album_id?: number;
    album_item_id: number;
    order: number;
    shortType: AlbumItemShortType;
};

export type TextBox = {
    id: number;
    description: string;
    col_size: number;

    order?: number;
    pivot?: AlbumItemPivot;
}
export function isImage(item: AlbumItem): item is Image {
    return (item.pivot?.shortType === 'image') || 'path' in item;
}

export function isTextBox(item: AlbumItem): item is TextBox {
    return (item.pivot?.shortType === 'textbox') || ('description' in item && 'col_size' in item);
}

export function albumItemType(item: AlbumItem): null | AlbumItemShortType {
    if (isImage(item)) {
        return 'image';
    } else if (isTextBox(item)) {
        return 'textbox';
    }

    return null;
}

export type ListItem = {
    id: number;
    order: number;
    type: AlbumItemShortType;
}
export type AlbumListItem = ImageListItem|TextListItem;

export type ImageListItem = ListItem & {
    srcset: string;
    sizes: string;
    src: string;
    description: string|null;
    desc: string;
}
export type TextListItem = ListItem & {
    description: string;
    col_size: number;
}

export function isImageListItem(item: AlbumListItem): item is ImageListItem {
    // We use this function as a type assertion, so we rely on always returning true
    return item.type === 'image';
}

export function isTextListItem(item: AlbumListItem): item is TextListItem {
    // We use this function as a type assertion, so we rely on always returning true
    return item.type === 'textbox';
}
