<script setup>
import {Link} from '@inertiajs/vue3'

defineProps({
    active: {
        type: String,
        default: 'accueil'
    }
})

const menuItems = [
    {id: 'accueil', label: 'Accueil', href: '/', icon: 'home'},
    {id: 'playlists', label: 'Playlists', href: '/playlists', icon: 'playlist'},
    {id: 'categories', label: 'Catégories', href: '/categories', icon: 'folder'},
    {id: 'favoris', label: 'Favoris', href: '/favoris', icon: 'heart'},
]
</script>

<template>
    <aside
        class="fixed left-0 top-14 bottom-0 w-16 lg:w-56 bg-dark-bg border-r border-dark-border overflow-y-auto z-40">
        <nav class="py-4">
            <ul class="space-y-1 px-2">
                <li v-for="item in menuItems" :key="item.id">
                    <Link
                        :href="item.href"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === item.id
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <!-- Home icon -->
                        <svg v-if="item.icon === 'home'" class="w-6 h-6 shrink-0" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>

                        <!-- Playlist icon -->
                        <svg v-if="item.icon === 'playlist'" class="w-6 h-6 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>

                        <!-- Folder icon -->
                        <svg v-if="item.icon === 'folder'" class="w-6 h-6 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>

                        <!-- Heart icon -->
                        <svg v-if="item.icon === 'heart'" class="w-6 h-6 shrink-0"
                             :class="active === 'favoris' ? 'text-pink-500' : ''" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>

                        <span class="hidden lg:block">{{ item.label }}</span>
                    </Link>
                </li>
            </ul>

            <hr class="my-4 border-dark-border mx-2"/>

            <ul class="space-y-1 px-2">
                <li>
                    <a
                        href="https://le-clap.fr"
                        target="_blank"
                        class="flex items-center gap-4 px-3 py-3 rounded-lg text-gray-400 hover:text-white hover:bg-dark-surface transition-colors text-lg"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden lg:block">le CLAP</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
</template>
