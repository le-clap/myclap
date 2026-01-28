<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    recentVideos: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head title="Manager"/>
    <ManagerLayout leftbar-active="dashboard">
        <div class="w-full space-y-6">
            <h1 class="text-3xl font-bebas tracking-wide">Dashboard</h1>

            <!-- Stats cards -->
            <div v-if="Object.keys(stats).length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-dark-surface rounded-lg p-4">
                    <div class="text-3xl font-bold text-myclap-red">{{ stats.published_videos }}</div>
                    <div class="text-sm text-gray-400 mt-1">Vidéos publiées</div>
                </div>
                <div class="bg-dark-surface rounded-lg p-4">
                    <div class="text-3xl font-bold text-blue-500">{{ stats.total_playlists }}</div>
                    <div class="text-sm text-gray-400 mt-1">Playlists</div>
                </div>
                <div class="bg-dark-surface rounded-lg p-4">
                    <div class="text-3xl font-bold text-green-500">{{ stats.total_views?.toLocaleString() }}</div>
                    <div class="text-sm text-gray-400 mt-1">Vues totales</div>
                </div>
                <div class="bg-dark-surface rounded-lg p-4">
                    <div class="text-3xl font-bold text-pink-500">{{ stats.total_reactions?.toLocaleString() }}</div>
                    <div class="text-sm text-gray-400 mt-1">Réactions</div>
                </div>
            </div>

            <!-- Recent videos -->
            <div v-if="recentVideos.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">Vidéos récentes</h2>
                    <Link href="/manager/videos" class="text-myclap-red hover:underline text-sm">
                        Voir toutes
                    </Link>
                </div>
                <div class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                    <Link
                        v-for="video in recentVideos"
                        :key="video.token"
                        :href="`/manager/videos/v/${video.token}`"
                        class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                    >
                        <img
                            :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                            :alt="video.name"
                            class="w-24 h-14 object-cover rounded"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-medium truncate">{{ video.name }}</div>
                            <div class="text-sm text-gray-400 flex items-center gap-1">
                                {{ video.views }} vues • {{ video.reactions }}
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <span
                            :class="[
                                'px-2 py-1 rounded text-xs',
                                video.upload_status === 0
                                    ? 'bg-green-500/20 text-green-400'
                                    : 'bg-yellow-500/20 text-yellow-400'
                            ]"
                        >
                            {{ video.upload_status === 0 ? 'Publié' : 'En cours' }}
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Quick actions -->
            <div>
                <h2 class="text-xl font-bold mb-4">Actions rapides</h2>
                <div class="flex flex-wrap gap-4">
                    <Link
                        href="/manager/videos/ajouter"
                        class="flex items-center gap-2 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajouter une vidéo
                    </Link>
                    <Link
                        href="/manager/playlists/ajouter"
                        class="flex items-center gap-2 px-4 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Créer une playlist
                    </Link>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>
