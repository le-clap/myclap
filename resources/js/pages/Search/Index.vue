<script setup>
import {Head} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoCard from '@/Components/VideoCard.vue'

defineProps({
    videos: {
        type: Array,
        default: () => []
    },
    query: {
        type: String,
        default: ''
    }
})
</script>

<template>
    <Head :title="`'${query}'`"/>
    <AppLayout>
        <div class="mx-auto px-4" style="max-width: 95%;">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">
                <span v-if="query">Résultats pour "{{ query }}"</span>
                <span v-else>Recherche</span>
            </h1>

            <!-- Results -->
            <div v-if="videos.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VideoCard
                    v-for="video in videos"
                    :key="video.token"
                    :video="video"
                />
            </div>

            <!-- Empty state -->
            <div v-else-if="query" class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p>Aucun résultat pour "{{ query }}"</p>
            </div>

            <!-- Initial state -->
            <div v-else class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <p>Entrez un terme de recherche</p>
            </div>
        </div>
    </AppLayout>
</template>
