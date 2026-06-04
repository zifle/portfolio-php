export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

export type Pagination<T> = {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    current_page_url: string;
    first_page_url: string;
    last_page_url: string;
    next_page_url: string|null;
    previous_page_url: string|null;
    path: string;
    from: number;
    to: number;
    data: T[];
}

export type UploadPlaceholder = {
    filename: string;
    src: string;
    size: number;
    progress: number;
}
