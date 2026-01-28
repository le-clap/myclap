<script setup>
import {Link} from '@inertiajs/vue3'

const props = defineProps({
    hasPermission: {
        type: Function,
        required: true
    }
})
</script>

<template>
    <header class="fixed top-0 left-0 right-0 h-14 bg-dark-surface border-b border-dark-border z-50 flex items-center px-4">
        <!-- Logo -->
        <div class="flex items-center gap-4">
            <Link href="/manager" class="flex items-center gap-2">
                <img src="/static/myclap/myclap-manager.png" alt="myCLAP Manager" class="h-10"/>
            </Link>
        </div>

        <!-- Right buttons -->
        <div class="flex items-center gap-2 ml-auto">
            <Link
                v-if="hasPermission('admin')"
                href="/admin"
                class="p-2 hover:bg-[#3a3a3a] rounded-full"
                title="Administration"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </Link>
            <Link
                href="/"
                class="p-2 hover:bg-[#3a3a3a] rounded-full"
                title="Retour myCLAP"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
            </Link>

            <Link
                href="/logout"
                class="p-2 hover:bg-[#3a3a3a] rounded-full"
                title="Se déconnecter"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </Link>
        </div>
    </header>
</template>
