<script setup>
import {Link} from '@inertiajs/vue3'
import {formatDuration, formatRelativeTime} from '@/utils/date'

const props = defineProps({
    video: {
        type: Object,
        required: true
    },
    playlistSlug: {
        type: String,
        default: null
    }
})

const watchUrl = props.playlistSlug
    ? `/playlists/${props.playlistSlug}/${props.video.token}`
    : `/regarder/${props.video.token}`
</script>

<template>
    <Link :href="watchUrl" class="block group">
        <div class="relative aspect-video bg-dark-surface rounded-lg overflow-hidden">
            <img
                :src="video.thumbnail_urls?.['480'] || video.thumbnail_url"
                :alt="video.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
            />

            <div v-if="video.duration"
                 class="absolute bottom-1 right-1 bg-black/30 text-white text-xs px-1 py-0.5 rounded">
                {{ formatDuration(video.duration) }}
            </div>
        </div>

        <div class="mt-3">
            <h3 class="font-medium text-white line-clamp-2 group-hover:text-myclap-red transition-colors text-base">
                {{ video.name }}
            </h3>
            <div class="flex items-center gap-2 mt-2 text-sm text-gray-400">
                <span>{{ video.views?.toLocaleString() || 0 }} vues</span>
                <span>•</span>
                <span>{{ formatRelativeTime(video.created_on) }}</span>
                <span>•</span>
                <span class="flex items-center gap-1">
                    {{ video.reactions?.toLocaleString() || 0 }}
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                              clip-rule="evenodd"/>
                    </svg>
                </span>
            </div>
        </div>
    </Link>
</template>
