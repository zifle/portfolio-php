import type { Album } from './album';

export type Image = {
    id: number;
    title: string;
    slug: string;
    order: number;
    description: string;
    category_id: number|null;
    location_id: number|null;
    date_start: Date|null;
    date_end: Date|null;
    published_at: Date|null;
    archived_at: Date|null;

    created_at?: string;
    updated_at?: string;

    albums_count?: number;

    albums?: Album[];
};

export type Camera = {
    id: number;
    brand: string;
    model: string;
}

export type Lens = Camera; // We reuse the properties of Camera
