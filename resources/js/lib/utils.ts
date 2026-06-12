import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import type { Image } from '@/types/models';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

export function largestImage(image: Image) {
    return image.paths[image.max_width];
}

export function smallestImage(image: Image) {
    let width = 4000;

    for (const w in image.paths) {
        if (image.paths.hasOwnProperty(w)) {
            if (parseInt(w) < width) {
                width = parseInt(w);
            }
        }
    }

    if (image.paths.hasOwnProperty(width)) {
        return image.paths[width];
    }

    return null;
}
