import type { Album } from './album';

export type Category = {
    id: number;
    name: string;
    order: number;

    albums_count?: number;
    albums?: Album[];
};

export type CategoryUpdate = Category & {
    saving: boolean;
    edit: boolean;
    _edit: CategoryUpdate;
};
