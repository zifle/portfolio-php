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
    items: Image[]|TextBox[];
};

export type AlbumItem = {
    id: number;
    order: number;
}

export type TextBox = {
    id: number;
    description: string;
    col_size: number;
    order?: number;
}

export type ListItem = {
    id: number;
    order: number;
    type: 'image'|'text';
    description: string;
}
export type ImageListItem = ListItem & {
    srcset: string;
    sizes: string;
    src: string;
    desc: string;
}
export type TextListItem = ListItem & {
    col_size: number;
}
