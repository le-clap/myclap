<script setup>
import {Head, Link} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoCard from '@/Components/VideoCard.vue'

defineProps({
    videos: {
        type: Array,
        default: () => []
    },
    needsAuth: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <Head title="Mes favoris"/>
    <AppLayout leftbar-active="favoris">
        <div class="mx-auto px-4" style="max-width: 95%;">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">Mes Favoris</h1>

            <!-- Auth required -->
            <div v-if="needsAuth" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-pink-500" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <p class="text-gray-400 mb-4">Connectez-vous pour voir vos vidéos favorites</p>
                <Link
                    href="/login"
                    class="inline-block px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-full text-white font-medium"
                >
                    Se connecter
                </Link>
            </div>

            <!-- Video grid -->
            <div v-else-if="videos.length > 0"
                 class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VideoCard
                    v-for="video in videos"
                    :key="video.token"
                    :video="video"
                />
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <p>Vous n'avez pas encore de vidéos favorites</p>
                <p class="text-sm mt-2 flex items-center justify-center gap-1">
                    Cliquez sur le
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                              clip-rule="evenodd"/>
                    </svg>
                    d'une vidéo pour l'ajouter à vos favoris
                </p>
            </div>
        </div>
    </AppLayout>
</template>
