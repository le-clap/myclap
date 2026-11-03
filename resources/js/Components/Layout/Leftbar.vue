<script setup>
import {computed} from 'vue'
import {Link} from '@inertiajs/vue3'
import NavIcon from './NavIcon.vue'
import {usePermissions} from '@/composables/usePermissions'

const props = defineProps({
    section: {
        type: String,
        required: true
    },
    active: {
        type: String,
        required: true
    }
})

const {allows} = usePermissions()

const menus = {
    app: [
        {id: 'accueil', label: 'Accueil', href: '/', icon: 'home'},
        {id: 'playlists', label: 'Playlists', href: '/playlists', icon: 'list'},
        {id: 'categories', label: 'Catégories', href: '/categories', icon: 'folder'},
        {id: 'favoris', label: 'Favoris', href: '/favoris', icon: 'heart', activeIcon: 'text-pink-500'},
        {separator: true},
        {label: 'le CLAP', icon: 'video'},
    ],
    manager: [
        {id: 'dashboard', label: 'Dashboard', href: '/manager', icon: 'dashboard'},
        {id: 'videos', label: 'Vidéos', href: '/manager/videos', icon: 'video', group: 'manager.video'},
        {id: 'transcode', label: 'Transcodage', href: '/manager/transcodage', icon: 'transcode', group: 'manager.video'},
        {id: 'playlists', label: 'Playlists', href: '/manager/playlists', icon: 'list', permission: 'manager.playlist'},
        {id: 'categories', label: 'Catégories', href: '/manager/categories', icon: 'folder', permission: 'manager.category'},
        {id: 'design', label: 'Présentation', href: '/manager/presentation', icon: 'design', permission: 'manager.design'},
        {id: 'stats', label: 'Statistiques', href: '/manager/stats', icon: 'stats', permission: 'manager.stat'},
        {separator: true, permission: 'admin'},
        {label: 'Administration', href: '/admin', icon: 'gear', permission: 'admin'},
    ],
    admin: [
        {id: 'dashboard', label: 'Dashboard', href: '/admin', icon: 'dashboard'},
        {id: 'permissions', label: 'Permissions', href: '/admin/permissions', icon: 'users'},
        {separator: true},
        {label: 'Retour Manager', href: '/manager', icon: 'arrow-back'},
    ],
}

const items = computed(() => menus[props.section].filter(allows))
</script>

<template>
    <aside
        class="fixed left-0 top-14 bottom-0 w-16 lg:w-56 bg-dark-bg border-r border-dark-border overflow-y-auto z-40">
        <nav class="py-4">
            <ul class="space-y-1 px-2">
                <template v-for="item in items" :key="item.label ?? 'separator'">
                    <li v-if="item.separator" class="my-4">
                        <div class="border-t border-dark-border"></div>
                    </li>
                    <li v-else>
                        <component
                            :is="item.href ? Link : 'a'"
                            :href="item.href"
                            :class="[
                                'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                                { 'cursor-default': !item.href },
                                active === item.id
                                    ? 'bg-[#272727] text-white'
                                    : 'text-gray-400 hover:text-white hover:bg-dark-surface'
                            ]"
                        >
                            <NavIcon
                                :name="item.icon"
                                class="w-6 h-6 shrink-0"
                                :class="active === item.id && item.activeIcon"
                            />
                            <span class="hidden lg:block">{{ item.label }}</span>
                        </component>
                    </li>
                </template>
            </ul>
        </nav>
    </aside>
</template>
