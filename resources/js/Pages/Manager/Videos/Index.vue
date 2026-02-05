<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDuration} from '@/utils/date'

defineProps({
    videos: {
        type: Array,
        default: () => []
    }
})

function getStatusClass(status) {
    switch (status) {
        case 0:
            return 'bg-green-500/20 text-green-400'
        case 1:
            return 'bg-yellow-500/20 text-yellow-400'
        case 2:
            return 'bg-gray-500/20 text-gray-400'
        default:
            return 'bg-gray-500/20 text-gray-400'
    }
}
</script>

<template>
    <Head title="Vidéos"/>
    <ManagerLayout leftbar-active="videos">
        <div class="w-full">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Vidéos</h1>
                <Link
                    href="/manager/videos/ajouter"
                    class="flex items-center gap-2 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter
                </Link>
            </div>

            <!-- Videos list -->
            <div v-if="videos.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="video in videos"
                    :key="video.token"
                    :href="`/manager/videos/v/${video.token}`"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                >
                    <img
                        :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                        :alt="video.name"
                        class="w-32 h-18 object-cover rounded"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ video.name }}</div>
                        <div class="text-sm text-gray-400 mt-1 flex items-center gap-1">
                            <span v-if="video.duration" class="mr-1">{{ formatDuration(video.duration) }} •</span>
                            {{ video.views?.toLocaleString() || 0 }} vues •
                            {{ video.reactions?.toLocaleString() || 0 }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                      clip-rule="evenodd"/>
                            </svg>
                            • {{ video.access_label }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Ajoutée le {{ new Date(video.uploaded_on).toLocaleDateString('fr-FR') }} par
                            {{ video.uploaded_by }}
                        </div>
                    </div>
                    <span :class="['px-2 py-1 rounded text-xs', getStatusClass(video.upload_status)]">
                        {{ video.upload_status_label }}
                    </span>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <p>Aucune vidéo</p>
                <Link
                    href="/manager/videos/ajouter"
                    class="inline-block mt-4 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                >
                    Ajouter une vidéo
                </Link>
            </div>
        </div>
    </ManagerLayout>
</template>
