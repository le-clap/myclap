<script setup>
import {Head, Link} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import {formatDuration} from '@/utils/date'

defineProps({
    broadcastPlaylists: {
        type: Array,
        default: () => []
    },
    classicPlaylists: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head title="Playlists"/>
    <AppLayout leftbar-active="playlists">
        <div class="mx-auto px-4 space-y-8" style="max-width: 95%;">
            <!-- Broadcast playlists -->
            <section v-if="broadcastPlaylists.length > 0">
                <h2 class="text-3xl font-bebas tracking-wide mb-4">Diffusions</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
                    <Link
                        v-for="playlist in broadcastPlaylists"
                        :key="playlist.slug"
                        :href="`/playlists/${playlist.slug}`"
                        class="block group"
                    >
                        <div class="relative aspect-video bg-dark-surface rounded-lg overflow-hidden">
                            <img
                                v-if="playlist.first_video_thumbnail"
                                :src="playlist.first_video_thumbnail"
                                :alt="playlist.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div class="absolute inset-0 bg-linear-to-t from-black/80 to-transparent"></div>
                            <div class="absolute bottom-3 left-3 right-3">
                                <h3 class="font-bold text-white text-sm leading-tight line-clamp-2">{{ playlist.name }}</h3>
                                <p class="text-sm text-gray-300">
                                    {{ playlist.video_count }} vidéos
                                    <span v-if="playlist.total_duration"> • {{
                                            formatDuration(playlist.total_duration)
                                        }}</span>
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Classic playlists -->
            <section v-if="classicPlaylists.length > 0">
                <h2 class="text-3xl font-bebas tracking-wide mb-4">Playlists</h2>
                <div class="space-y-6">
                    <Link
                        v-for="playlist in classicPlaylists"
                        :key="playlist.slug"
                        :href="`/playlists/${playlist.slug}`"
                        class="block group"
                    >
                        <div class="bg-dark-surface rounded-lg p-4 hover:bg-[#222] transition-colors">
                            <div class="flex items-start gap-6">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-white group-hover:text-myclap-red transition-colors">
                                        {{ playlist.name }}
                                    </h3>
                                    <p class="text-sm text-gray-400 mt-1">
                                        {{ playlist.video_count }} vidéos
                                        <span v-if="playlist.total_duration"> • {{
                                                formatDuration(playlist.total_duration)
                                            }}</span>
                                    </p>
                                    <p v-if="playlist.description" class="text-sm text-gray-500 mt-2 line-clamp-2">
                                        {{ playlist.description }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="playlist.videos_preview?.length > 0" class="flex gap-2 mt-4 overflow-x-auto">
                                <img
                                    v-for="video in playlist.videos_preview"
                                    :key="video.token"
                                    :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                                    :alt="video.name"
                                    class="w-24 h-14 object-cover rounded shrink-0"
                                />
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Empty state -->
            <div v-if="broadcastPlaylists.length === 0 && classicPlaylists.length === 0"
                 class="text-center py-12 text-gray-400">
                <p>Aucune playlist pour le moment</p>
            </div>
        </div>
    </AppLayout>
</template>
