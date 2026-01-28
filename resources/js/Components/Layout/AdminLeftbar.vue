<script setup>
import {Link} from '@inertiajs/vue3'

defineProps({
    active: {
        type: String,
        default: 'dashboard'
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
                        href="/admin"
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

                <!-- Permissions -->
                <li>
                    <Link
                        href="/admin/permissions"
                        :class="[
                            'flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg',
                            active === 'permissions'
                                ? 'bg-[#272727] text-white'
                                : 'text-gray-400 hover:text-white hover:bg-[#1a1a1a]'
                        ]"
                    >
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="hidden lg:block">Permissions</span>
                    </Link>
                </li>

                <!-- Separator -->
                <li class="my-4">
                    <div class="border-t border-dark-border"></div>
                </li>

                <!-- Retour Manager -->
                <li>
                    <Link
                        href="/manager"
                        class="flex items-center gap-4 px-3 py-3 rounded-lg transition-colors text-lg text-gray-400 hover:text-white hover:bg-[#1a1a1a]"
                    >
                        <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                        </svg>
                        <span class="hidden lg:block">Retour Manager</span>
                    </Link>
                </li>
            </ul>
        </nav>
    </aside>
</template>
