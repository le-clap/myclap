<script setup>
import {Head} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoCard from '@/Components/VideoCard.vue'

defineProps({
    category: {
        type: Object,
        required: true
    },
    videos: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head :title="`${category.label}`"/>
    <AppLayout leftbar-active="categories">
        <div class="mx-auto px-4" style="max-width: 95%;">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">{{ category.label }}</h1>
                <p v-if="category.description" class="text-gray-400 mt-2">{{ category.description }}</p>
            </div>

            <!-- Video grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VideoCard
                    v-for="video in videos"
                    :key="video.token"
                    :video="video"
                />
            </div>

            <!-- Empty state -->
            <div v-if="videos.length === 0" class="text-center py-12 text-gray-400">
                <p>Aucune vidéo dans cette catégorie</p>
            </div>
        </div>
    </AppLayout>
</template>
