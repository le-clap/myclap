<script setup>
import {Head, Link} from '@inertiajs/vue3'
import {computed, onMounted, ref} from 'vue'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDateTime, formatDuration} from '@/utils/date'
import axios from 'axios'

const animate = ref(false)

onMounted(() => {
    requestAnimationFrame(() => {
        animate.value = true
    })
})

const props = defineProps({
    totalViews: {
        type: Number,
        default: 0
    },
    totalReactions: {
        type: Number,
        default: 0
    },
    totalDuration: {
        type: Number,
        default: 0
    },
    totalVideos: {
        type: Number,
        default: 0
    },
    topVideos: {
        type: Array,
        default: () => []
    },
    recentViewsData: {
        type: Array,
        default: () => []
    },
    viewSources: {
        type: Array,
        default: () => []
    },
    deviceTypes: {
        type: Array,
        default: () => []
    },
    browsers: {
        type: Array,
        default: () => []
    },
    operatingSystems: {
        type: Array,
        default: () => []
    },
    recentViews: {
        type: Array,
        default: () => []
    }
})

const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)

async function searchVideos() {
    if (searchQuery.value.length < 2) {
        searchResults.value = []
        return
    }

    searching.value = true
    try {
        const {data} = await axios.get('/manager/playlists/api/search', {
            params: {
                q: searchQuery.value,
                limit: 10
            }
        })
        searchResults.value = data.videos
    } catch (error) {
        console.error('Search failed:', error)
    } finally {
        searching.value = false
    }
}

const chartData = computed(() => {
    if (!props.recentViewsData || props.recentViewsData.length === 0) return []
    return props.recentViewsData.map(d => ({
        date: new Date(d.date).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'}),
        views: d.views
    }))
})

const maxViews = computed(() => {
    if (chartData.value.length === 0) return 0
    return Math.max(...chartData.value.map(d => d.views))
})

function formatTotalDuration(seconds) {
    if (!seconds) return '0h'
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    if (hours > 0) {
        return `${hours}h ${minutes}m`
    }
    return `${minutes}m`
}

function getPercentage(count, items) {
    const total = items.reduce((sum, item) => sum + item.count, 0)
    return total > 0 ? Math.round((count / total) * 100) : 0
}
</script>

<template>
    <Head title="Statistiques"/>
    <ManagerLayout leftbar-active="stats">
        <div class="w-full">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">Statistiques</h1>

            <!-- Stats globales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-dark-surface rounded-lg p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ totalVideos }}</div>
                            <div class="text-sm text-gray-400">Vidéos publiées</div>
                        </div>
                    </div>
                </div>

                <div class="bg-dark-surface rounded-lg p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ totalViews?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-gray-400">Vues totales</div>
                        </div>
                    </div>
                </div>

                <div class="bg-dark-surface rounded-lg p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ totalReactions?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-gray-400">Réactions totales</div>
                        </div>
                    </div>
                </div>

                <div class="bg-dark-surface rounded-lg p-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ formatTotalDuration(totalDuration) }}</div>
                            <div class="text-sm text-gray-400">Durée totale</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique des vues récentes -->
            <div class="bg-dark-surface rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Vues des 30 derniers jours</h2>
                <div v-if="chartData.length > 0" class="h-64 flex items-end justify-between gap-1">
                    <div
                        v-for="(item, index) in chartData"
                        :key="index"
                        class="flex-1 flex flex-col items-center h-full"
                    >
                        <!-- Zone graphique -->
                        <div class="relative w-full flex-1 flex items-end">
                            <!-- Valeur -->
                            <div class="absolute -top-5 w-full text-xs text-gray-400 text-center">
                                {{ item.views }}
                            </div>

                            <!-- Barre -->
                            <div
                                class="w-full bg-myclap-red rounded-t transition-all duration-700 ease-out"
                                :style="{
                height: animate && maxViews > 0
                    ? `${Math.max((item.views / maxViews) * 100, 2)}%`
                    : '0%'
            }"
                            ></div>
                        </div>

                        <!-- Date -->
                        <div class="text-xs text-gray-500 mt-2">
                            {{ item.date }}
                        </div>
                    </div>

                </div>
                <div v-else class="text-center py-12 text-gray-400">
                    Aucune donnée disponible
                </div>
            </div>

            <!-- Video Search -->
            <div class="bg-dark-surface rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Rechercher une vidéo</h2>
                <div class="relative">
                    <input
                        v-model="searchQuery"
                        @input="searchVideos"
                        type="text"
                        placeholder="Rechercher une vidéo par nom..."
                        class="w-full bg-[#1a1a1a] border border-[#3a3a3a] rounded-lg px-4 py-3 text-white focus:outline-none focus:border-myclap-red"
                    />
                    <div v-if="searchResults.length > 0"
                         class="absolute z-10 w-full mt-2 bg-dark-surface border border-[#3a3a3a] rounded-lg max-h-96 overflow-y-auto">
                        <Link
                            v-for="video in searchResults"
                            :key="video.token"
                            :href="`/manager/stats/video/${video.token}`"
                            class="w-full text-left px-4 py-3 hover:bg-[#222] flex items-center gap-3 transition-colors"
                        >
                            <img :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                                 :alt="video.name"
                                 class="w-20 h-11 object-cover rounded"/>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ video.name }}</div>
                                <div class="text-sm text-gray-400">
                                    {{ video.views?.toLocaleString() || 0 }} vues
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Top vidéos -->
            <div class="bg-dark-surface rounded-lg p-6 mb-8">
                <h2 class="text-xl font-bold mb-4">Top 10 des vidéos les plus vues</h2>
                <div v-if="topVideos.length > 0" class="space-y-2">
                    <Link
                        v-for="(video, index) in topVideos"
                        :key="video.token"
                        :href="`/manager/stats/video/${video.token}`"
                        class="flex items-center gap-4 p-3 hover:bg-[#222] rounded-lg transition-colors"
                    >
                        <div class="w-8 text-center font-bold text-gray-500">#{{ index + 1 }}</div>
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
                    </Link>
                </div>
                <div v-else class="text-center py-8 text-gray-400">
                    Aucune vidéo publiée
                </div>
            </div>

            <!-- Analytics Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Sources -->
                <div class="bg-dark-surface rounded-lg p-4">
                    <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Sources</h3>
                    <div v-if="viewSources.length > 0" class="space-y-2">
                        <div v-for="source in viewSources" :key="source.view_source"
                             class="flex items-center justify-between">
                            <span class="text-sm">{{ source.view_source || 'unknown' }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full"
                                         :style="{width: `${getPercentage(source.count, viewSources)}%`}"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-8 text-right">{{ source.count }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                </div>

                <!-- Devices -->
                <div class="bg-dark-surface rounded-lg p-4">
                    <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Appareils</h3>
                    <div v-if="deviceTypes.length > 0" class="space-y-2">
                        <div v-for="device in deviceTypes" :key="device.device_type"
                             class="flex items-center justify-between">
                            <span class="text-sm">{{ device.device_type || 'unknown' }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full"
                                         :style="{width: `${getPercentage(device.count, deviceTypes)}%`}"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-8 text-right">{{ device.count }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                </div>

                <!-- Browsers -->
                <div class="bg-dark-surface rounded-lg p-4">
                    <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Navigateurs</h3>
                    <div v-if="browsers.length > 0" class="space-y-2">
                        <div v-for="browser in browsers" :key="browser.browser"
                             class="flex items-center justify-between">
                            <span class="text-sm">{{ browser.browser || 'unknown' }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500 rounded-full"
                                         :style="{width: `${getPercentage(browser.count, browsers)}%`}"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-8 text-right">{{ browser.count }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                </div>

                <!-- OS -->
                <div class="bg-dark-surface rounded-lg p-4">
                    <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Systèmes</h3>
                    <div v-if="operatingSystems.length > 0" class="space-y-2">
                        <div v-for="os in operatingSystems" :key="os.os" class="flex items-center justify-between">
                            <span class="text-sm">{{ os.os || 'unknown' }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-2 bg-dark-border rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full"
                                         :style="{width: `${getPercentage(os.count, operatingSystems)}%`}"></div>
                                </div>
                                <span class="text-xs text-gray-400 w-8 text-right">{{ os.count }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                </div>
            </div>

            <!-- Recent Views -->
            <div class="bg-dark-surface rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Historique des vues récentes</h2>
                <div v-if="recentViews.length === 0" class="text-center py-8 text-gray-400">
                    Aucun historique disponible
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="text-left text-gray-400 text-sm border-b border-dark-border">
                            <th class="pb-3 pr-4 font-medium">Vidéo</th>
                            <th class="pb-3 pr-4 font-medium">Utilisateur</th>
                            <th class="pb-3 pr-4 font-medium">Date</th>
                            <th class="pb-3 font-medium">Temps regardé</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="view in recentViews"
                            :key="view.id"
                            class="border-b border-dark-border hover:bg-[#222] transition-colors"
                        >
                            <td class="py-3 pr-4 text-sm">
                                <Link
                                    v-if="view.video"
                                    :href="`/manager/stats/video/${view.video_token}`"
                                    class="hover:text-myclap-red transition-colors truncate block max-w-48"
                                >
                                    {{ view.video.name }}
                                </Link>
                                <span v-else class="text-gray-500">{{ view.video_token }}</span>
                            </td>
                            <td class="py-3 pr-4 text-sm">{{ view.username || '-' }}</td>
                            <td class="py-3 pr-4 text-sm text-gray-400">{{ formatDateTime(view.created_on) }}</td>
                            <td class="py-3 text-sm">{{
                                    view.watch_time ? formatDuration(view.watch_time) : '-'
                                }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>
