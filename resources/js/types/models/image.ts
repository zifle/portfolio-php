import type { Album, AlbumItemPivot } from './album';

export type Image = {
    id: number;
    path: string;
    date_taken: string;
    available_res: number[];
    max_width: number;
    max_height: number;
    description: string | null;
    camera_id: number | null;
    camera: Camera | null;
    lens_id: number | null;
    lens: Lens | null;
    focal_length: number | null;
    focal_length_35: number | null;
    exposure_time: string | null;
    exposure_compensation: number | null;
    aperture: number | null;
    rating: number | null;

    created_at?: string;
    updated_at?: string;

    paths: { [key: string]: string };
    albums_count?: number;
    albums?: Album[];
    order?: number;
    pivot?: AlbumItemPivot;
    views_count?: number;

    srcset?: string[];
};

export type Camera = {
    id: number;
    brand: string;
    model: string;
    str: string;
};

export type Lens = Camera; // We reuse the properties of Camera
