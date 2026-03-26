<script setup>
import {computed, reactive, watch} from 'vue'
import {Head, Link, router} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDuration} from '@/utils/date'
import {formatBitrate, formatFileSize} from '@/utils/video'

const props = defineProps({
    videos: {
        type: Object,
        default: () => ({data: [], current_page: 1, last_page: 1, total: 0, from: null, to: null})
    },
    filters: {
        type: Object,
        default: () => ({q: '', sort: '-uploaded_on', limit: 24})
    },
    sortOptions: {
        type: Array,
        default: () => []
    }
})

const localFilters = reactive({
    q: props.filters.q || '',
    sort: props.filters.sort || '-uploaded_on',
    limit: Number(props.filters.limit || 24),
})

watch(() => props.filters, (nextFilters) => {
    localFilters.q = nextFilters.q || ''
    localFilters.sort = nextFilters.sort || '-uploaded_on'
    localFilters.limit = Number(nextFilters.limit || 24)
})

const videoItems = computed(() => props.videos?.data || [])

const sortField = computed({
    get() {
        return localFilters.sort.startsWith('-') ? localFilters.sort.slice(1) : localFilters.sort
    },
    set(value) {
        localFilters.sort = `${isSortDesc.value ? '-' : ''}${value}`
    }
})

const isSortDesc = computed({
    get() {
        return localFilters.sort.startsWith('-')
    },
    set(value) {
        const field = sortField.value || 'uploaded_on'
        localFilters.sort = `${value ? '-' : ''}${field}`
    }
})

const paginationItems = computed(() => {
    const lastPage = props.videos?.last_page || 1
    const currentPage = props.videos?.current_page || 1

    if (lastPage <= 7) {
        return Array.from({length: lastPage}, (_, i) => i + 1)
    }

    const items = [1]
    const start = Math.max(2, currentPage - 1)
    const end = Math.min(lastPage - 1, currentPage + 1)

    if (start > 2) {
        items.push('...')
    }

    for (let page = start; page <= end; page += 1) {
        items.push(page)
    }

    if (end < lastPage - 1) {
        items.push('...')
    }

    items.push(lastPage)

    return items
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

function buildQuery(overrides = {}) {
    return {
        q: localFilters.q || undefined,
        sort: localFilters.sort,
        limit: localFilters.limit,
        ...overrides,
    }
}

function applyFilters() {
    router.get('/manager/videos', buildQuery({page: 1}), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function goToPage(page) {
    const currentPage = props.videos?.current_page || 1
    const lastPage = props.videos?.last_page || 1

    if (!page || page < 1 || page > lastPage || page === currentPage) {
        return
    }

    router.get('/manager/videos', buildQuery({page}), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function toggleSortDirection() {
    isSortDesc.value = !isSortDesc.value
    applyFilters()
}
</script>

<template>
    <Head title="Vidéos"/>
    <ManagerLayout leftbar-active="videos">
        <div class="w-full">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Vidéos</h1>
                <div class="flex items-center gap-3">
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
            </div>

            <form class="bg-dark-surface rounded-lg p-4 mb-4 grid grid-cols-1 md:grid-cols-12 gap-3" @submit.prevent="applyFilters">
                <div class="md:col-span-5">
                    <input
                        v-model="localFilters.q"
                        type="text"
                        placeholder="Rechercher..."
                        class="w-full bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 py-2 text-white focus:outline-none focus:border-myclap-red"
                    />
                </div>

                <div class="md:col-span-3">
                    <select
                        v-model="sortField"
                        class="w-full bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 py-2 text-white focus:outline-none focus:border-myclap-red"
                        @change="applyFilters"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            Trier par: {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <button
                        type="button"
                        class="w-full bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 py-2 hover:bg-dark-border transition-colors"
                        @click="toggleSortDirection"
                    >
                        {{ isSortDesc ? 'Desc' : 'Asc' }}
                    </button>
                </div>

                <div class="md:col-span-2 flex gap-2">
                    <select
                        v-model.number="localFilters.limit"
                        class="w-full bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 py-2 text-white focus:outline-none focus:border-myclap-red"
                        @change="applyFilters"
                    >
                        <option :value="12">12 / page</option>
                        <option :value="24">24 / page</option>
                        <option :value="48">48 / page</option>
                        <option :value="96">96 / page</option>
                    </select>
                </div>
            </form>

            <div class="text-sm text-gray-400 mb-4">
                {{ videos.total || 0 }} vidéos
                <span v-if="videos.from && videos.to"> • affichage {{ videos.from }}–{{ videos.to }}</span>
            </div>

            <!-- Videos list -->
            <div v-if="videoItems.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="video in videoItems"
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
                        <div class="text-sm text-gray-400 mt-1 flex items-center gap-1 flex-wrap">
                            <span v-if="video.duration" class="mr-1">{{ formatDuration(video.duration) }} •</span>
                            <span v-if="video.file_size" class="mr-1">{{ formatFileSize(video.file_size) }} •</span>
                            <span v-if="video.bitrate" class="mr-1">{{ formatBitrate(video.bitrate) }} •</span>
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
                <p>Aucune vidéo ne correspond à vos critères</p>
            </div>

            <div v-if="videos.last_page > 1" class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <button
                    type="button"
                    class="px-3 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="videos.current_page <= 1"
                    @click="goToPage(videos.current_page - 1)"
                >
                    Précédent
                </button>

                <template v-for="(item, index) in paginationItems" :key="`page-${item}-${index}`">
                    <span v-if="item === '...'" class="px-2 text-gray-400">...</span>
                    <button
                        v-else
                        type="button"
                        :class="[
                            'px-3 py-2 rounded transition-colors',
                            item === videos.current_page
                                ? 'bg-myclap-red text-white'
                                : 'bg-dark-border hover:bg-[#3a3a3a]'
                        ]"
                        @click="goToPage(item)"
                    >
                        {{ item }}
                    </button>
                </template>

                <button
                    type="button"
                    class="px-3 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="videos.current_page >= videos.last_page"
                    @click="goToPage(videos.current_page + 1)"
                >
                    Suivant
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
