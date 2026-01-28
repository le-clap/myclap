<script setup>
import {Link} from '@inertiajs/vue3'

const props = defineProps({
    active: {
        type: String,
        default: 'dashboard'
    },
    hasPermission: {
        type: Function,
        required: true
    },
    hasPermissionGroup: {
        type: Function,
        required: true
    }
})
</script>

<template>
    <aside
        class="fixed left-0 top-14 bottom-0 w-16 lg:w-56 bg-dark-bg border-r border-dark-border overflow-y-auto z-40">
        <nav class="py-4">
            <ul class="space-y-1 px-2">
                <!-- Dashboard -->
                <li>
                    <Link
                        href="/manager"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'dashboard'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="hidden lg:block">Dashboard</span>
                    </Link>
                </li>

                <!-- Videos -->
                <li v-if="hasPermissionGroup('manager.video')">
                    <Link
                        href="/manager/videos"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'videos'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden lg:block">Vidéos</span>
                    </Link>
                </li>

                <!-- Playlists -->
                <li v-if="hasPermission('manager.playlist')">
                    <Link
                        href="/manager/playlists"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'playlists'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="hidden lg:block">Playlists</span>
                    </Link>
                </li>

                <!-- Categories -->
                <li v-if="hasPermission('manager.category')">
                    <Link
                        href="/manager/categories"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'categories'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span class="hidden lg:block">Catégories</span>
                    </Link>
                </li>

                <!-- Design (Présentation) -->
                <li v-if="hasPermission('manager.design')">
                    <Link
                        href="/manager/presentation"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'design'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        <span class="hidden lg:block">Présentation</span>
                    </Link>
                </li>

                <!-- Statistiques -->
                <li v-if="hasPermission('manager.stat')">
                    <Link
                        href="/manager/stats"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'stats'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                        ]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="hidden lg:block">Statistiques</span>
                    </Link>
                </li>

                <!-- Separator et Admin -->
                <li v-if="hasPermission('admin')" class="my-4">
                    <div class="border-t border-dark-border"></div>
                </li>

                <li v-if="hasPermission('admin')">
                    <Link
                        href="/admin"
                        class="flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg text-gray-400 hover:text-white hover:bg-dark-surface"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="hidden lg:block">Administration</span>
                    </Link>
                </li>
            </ul>
        </nav>
    </aside>
</template>
