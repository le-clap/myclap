<script setup>
import {Link} from '@inertiajs/vue3'
</script>

<template>
    <header class="fixed top-0 left-0 right-0 h-14 bg-dark-surface border-b border-dark-border z-50 flex items-center px-4">
        <!-- Logo -->
        <div class="flex items-center gap-4">
            <Link href="/admin" class="flex items-center gap-2">
                <img src="/static/myclap/myclap-admin.png" alt="myCLAP Admin" class="h-10"/>
            </Link>
        </div>

        <!-- Right buttons -->
        <div class="flex items-center gap-2 ml-auto">
            <Link
                href="/manager"
                class="p-2 hover:bg-[#3a3a3a] rounded-full"
                title="Retour au Manager"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
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
