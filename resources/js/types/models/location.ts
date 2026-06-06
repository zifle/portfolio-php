export type Location = {
    id: number;
    name: string;
    lat: number | null;
    lng: number | null;

    albums_count?: number;

    distance?: number;
};

export type LocationUpdate = Location & {
    coords: string | null;
    saving: boolean;
    edit: boolean;
    _edit: LocationUpdate;
};
