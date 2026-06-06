<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import { FacebookIcon, InstagramIcon } from 'vue3-simple-icons';
import AppLogo from '@/components/AppLogo.vue';
import { show as showAlbum } from '@/routes/album';
import type { Album, Category } from '@/types/models';

const page = usePage();

const menu = computed(() => {
    return page.props.menu as (Category | Album)[];
});

const socials = computed(() => {
    return page.props.socials as {
        facebook: string|null,
        instagram: string|null,
    }
})

function isCategory(item: Category | Album): item is Category {
    return item.hasOwnProperty('name') && item.hasOwnProperty('albums_count');
}

function closeDetailsMenu(ev: MouseEvent) {
    let target: Element|null = ev.target as Element;
    let clickedOn = null;

    while (target && target?.tagName != 'DETAILS') {
        target = target.parentElement;
    }

    if (target && target.tagName == 'DETAILS') {
        clickedOn = target;
    }

    [...document.getElementsByTagName('details')]
        .filter((el: HTMLDetailsElement) => el.open && el != clickedOn)
        .forEach((el: HTMLDetailsElement) => el.open = false);
}
onMounted(() => {
    document.addEventListener('click', closeDetailsMenu);
});
onUnmounted(() => {
    document.removeEventListener('click', closeDetailsMenu);
});
</script>

<template>
    <div class="p-2">
        <div
            class="relative z-2 w-full rounded-md bg-base-300 shadow-sm max-lg:collapse"
        >
            <input type="checkbox" id="navbar-toggle" class="peer hidden" />
            <label
                for="navbar-toggle"
                class="fixed inset-0 hidden max-lg:peer-checked:block"
            ></label>
            <div class="collapse-title navbar">
                <div class="navbar-start">
                    <label for="navbar-toggle" class="btn btn-ghost lg:hidden">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h8m-8 6h16"
                            />
                        </svg>
                    </label>
                    <a href="/" class="-mt-3">
                        <AppLogo></AppLogo>
                    </a>
                </div>
                <div class="navbar-center hidden lg:flex">
                    <ul class="menu menu-horizontal px-1">
                        <li
                            v-for="item in menu"
                            :key="(isCategory(item) ? 'cat' : 'alb') + item.id"
                        >
                            <details v-if="isCategory(item)">
                                <summary>{{ item.name }}</summary>
                                <ul class="z-1 w-60 bg-base-100 p-2">
                                    <li
                                        v-for="album in item.albums"
                                        :key="'alb' + album.id"
                                    >
                                        <Link :href="showAlbum(album.slug)">
                                            {{ album.title }}
                                        </Link>
                                    </li>
                                </ul>
                            </details>
                            <Link v-else :href="showAlbum(item.slug)">{{
                                item.title
                            }}</Link>
                        </li>
                    </ul>
                </div>
                <div class="navbar-end gap-3 pe-2">
                    <Link v-if="socials.facebook" :href="socials.facebook">
                        <FacebookIcon></FacebookIcon>
                    </Link>
                    <Link v-if="socials.instagram" :href="socials.instagram">
                        <InstagramIcon></InstagramIcon>
                    </Link>
                </div>
            </div>

            <div class="collapse-content z-1 lg:hidden">
                <ul class="menu">
                    <li
                        v-for="item in menu"
                        :key="(isCategory(item) ? 'col_cat' : 'col_alb') + item.id"
                    >
                        <details v-if="isCategory(item)">
                            <summary>{{ item.name }}</summary>
                            <ul class="z-1 w-60 bg-base-100 p-2">
                                <li
                                    v-for="album in item.albums"
                                    :key="'col_alb' + album.id"
                                >
                                    <Link :href="showAlbum(album.slug)">
                                        {{ album.title }}
                                    </Link>
                                </li>
                            </ul>
                        </details>
                        <Link v-else :href="showAlbum(item.slug)">{{
                                item.title
                        }}</Link>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
