<script setup>
import {Head, Link} from '@inertiajs/vue3'
import {onMounted, ref} from 'vue'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDate, formatDateTime, formatDuration} from '@/utils/date'

const animate = ref(false)

onMounted(() => {
    requestAnimationFrame(() => {
        animate.value = true
    })
})

const props = defineProps({
    video: Object,
    viewsData: {
        type: Array,
        default: () => []
    },
    uniqueViewers: {
        type: Number,
        default: 0
    },
    averageWatchTime: {
        type: Number,
        default: 0
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

function getPercentage(count, items) {
    const total = items.reduce((sum, item) => sum + item.count, 0)
    return total > 0 ? Math.round((count / total) * 100) : 0
}
</script>

<template>
    <Head :title="`Stats: ${video.name}`"/>
    <ManagerLayout leftbar-active="stats">
        <div class="w-full">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-6">
                <Link href="/manager/stats"
                      class="text-gray-400 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-3xl font-bebas tracking-wide">{{ video.name }}</h1>
            </div>

            <!-- Video Info + Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-8">
                <!-- Thumbnail -->
                <div>
                    <img
                        :src="video.thumbnail_urls?.['480'] || video.thumbnail_url"
                        :alt="video.name"
                        class="w-full aspect-video object-cover rounded-lg"
                    />
                    <div class="mt-3 text-sm text-gray-400 space-y-1">
                        <div><span class="text-gray-500">Auteur:</span> {{ video.author }}</div>
                        <div><span class="text-gray-500">Durée:</span> {{ formatDuration(video.duration) }}</div>
                        <div><span class="text-gray-500">Accès:</span> {{ video.access }}</div>
                        <div><span class="text-gray-500">Uploadée le:</span> {{ formatDate(video.uploaded_on) }}</div>
                    </div>
                </div>

                <!-- KPI Column -->
                <div class="lg:col-span-1 bg-dark-surface rounded-lg p-6 space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ video.views?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-gray-400">Vues</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ video.reactions?.toLocaleString() || 0 }}</div>
                            <div class="text-sm text-gray-400">Réactions</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ uniqueViewers }}</div>
                            <div class="text-sm text-gray-400">Spectateurs uniques</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold">{{ formatDuration(averageWatchTime) }}</div>
                            <div class="text-sm text-gray-400">Temps moyen</div>
                        </div>
                    </div>
                </div>

                <!-- Two breakdown columns -->
                <div class="lg:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <!-- Sources -->
                        <div class="bg-dark-surface rounded-lg p-4">
                            <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Sources</h3>
                            <div v-if="viewSources.length > 0" class="space-y-2">
                                <div v-for="source in viewSources" :key="source.view_source"
                                     class="flex items-center justify-between">
                                    <span class="text-sm truncate mr-2">{{ source.view_source || 'unknown' }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-2 bg-dark-border rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full"
                                                 :style="{width: `${getPercentage(source.count, viewSources)}%`}"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-6 text-right">{{ source.count }}</span>
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
                                    <span class="text-sm truncate mr-2">{{ device.device_type || 'unknown' }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-2 bg-dark-border rounded-full overflow-hidden">
                                            <div class="h-full bg-green-500 rounded-full"
                                                 :style="{width: `${getPercentage(device.count, deviceTypes)}%`}"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-6 text-right">{{ device.count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Browsers -->
                        <div class="bg-dark-surface rounded-lg p-4">
                            <h3 class="font-bold mb-3 text-sm uppercase text-gray-400">Navigateurs</h3>
                            <div v-if="browsers.length > 0" class="space-y-2">
                                <div v-for="browser in browsers" :key="browser.browser"
                                     class="flex items-center justify-between">
                                    <span class="text-sm truncate mr-2">{{ browser.browser || 'unknown' }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-2 bg-dark-border rounded-full overflow-hidden">
                                            <div class="h-full bg-yellow-500 rounded-full"
                                                 :style="{width: `${getPercentage(browser.count, browsers)}%`}"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-6 text-right">{{ browser.count }}</span>
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
                                    <span class="text-sm truncate mr-2">{{ os.os || 'unknown' }}</span>
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 h-2 bg-dark-border rounded-full overflow-hidden">
                                            <div class="h-full bg-purple-500 rounded-full"
                                                 :style="{width: `${getPercentage(os.count, operatingSystems)}%`}"></div>
                                        </div>
                                        <span class="text-xs text-gray-400 w-6 text-right">{{ os.count }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">Aucune donnée</div>
                        </div>
                    </div>
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
