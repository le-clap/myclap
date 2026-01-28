<script setup>
import {Head} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoCard from '@/Components/VideoCard.vue'
import {formatDuration} from '@/utils/date'
import {computed} from 'vue'

const props = defineProps({
    playlist: {
        type: Object,
        required: true
    },
    videos: {
        type: Array,
        default: () => []
    }
})

const totalDuration = computed(() => {
    return props.videos.reduce((sum, v) => sum + (v.duration || 0), 0)
})
</script>

<template>
    <Head :title="`${playlist.name}`"/>
    <AppLayout leftbar-active="playlists">
        <div class="mx-auto px-4" style="max-width: 95%;">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">{{ playlist.name }}</h1>
                <p v-if="playlist.description" class="text-gray-400 mt-2">{{ playlist.description }}</p>
                <p class="text-sm text-gray-500 mt-1">
                    {{ videos.length }} vidéos
                    <span v-if="totalDuration"> • {{ formatDuration(totalDuration) }}</span>
                </p>
            </div>

            <!-- Video grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VideoCard
                    v-for="video in videos"
                    :key="video.token"
                    :video="video"
                    :playlist-slug="playlist.slug"
                />
            </div>

            <!-- Empty state -->
            <div v-if="videos.length === 0" class="text-center py-12 text-gray-400">
                <p>Aucune vidéo dans cette playlist</p>
            </div>
        </div>
    </AppLayout>
</template>
