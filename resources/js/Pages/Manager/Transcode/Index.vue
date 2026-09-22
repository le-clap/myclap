<script setup>
import {computed, onUnmounted, reactive, watch} from 'vue'
import {Head, router} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDateTime, formatDuration} from '@/utils/date'

const props = defineProps({
    attempts: {
        type: Object,
        default: () => ({data: [], current_page: 1, last_page: 1, total: 0, from: null, to: null})
    },
    filters: {
        type: Object,
        default: () => ({q: '', sort: '-started_on', limit: 24, status: 'processing'})
    },
    statusOptions: {
        type: Array,
        default: () => []
    },
    sortOptions: {
        type: Array,
        default: () => []
    }
})

const localFilters = reactive({
    q: props.filters.q || '',
    sort: props.filters.sort || '-started_on',
    limit: Number(props.filters.limit || 24),
    status: props.filters.status || 'processing',
})

watch(() => props.filters, (nextFilters) => {
    localFilters.q = nextFilters.q || ''
    localFilters.sort = nextFilters.sort || '-started_on'
    localFilters.limit = Number(nextFilters.limit || 24)
    localFilters.status = nextFilters.status || 'processing'
})

const attemptItems = computed(() => props.attempts?.data || [])

const hasInFlightAttempts = computed(() => attemptItems.value.some(a => [1, 2].includes(a.status)))

let pollTimer = null

function schedulePoll() {
    if (pollTimer) {
        return
    }

    pollTimer = setInterval(() => {
        if (!hasInFlightAttempts.value) {
            clearInterval(pollTimer)
            pollTimer = null
            return
        }

        router.reload({only: ['attempts'], preserveScroll: true, preserveState: true})
    }, 2500)
}

watch(hasInFlightAttempts, (inFlight) => {
    if (inFlight) {
        schedulePoll()
    }
}, {immediate: true})

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer)
    }
})

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
        const field = sortField.value || 'started_on'
        localFilters.sort = `${value ? '-' : ''}${field}`
    }
})

const paginationItems = computed(() => {
    const lastPage = props.attempts?.last_page || 1
    const currentPage = props.attempts?.current_page || 1

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

function statusClass(status) {
    switch (status) {
        case 3: // COMPLIANT
        case 4: // TRANSCODED
            return 'bg-green-500/20 text-green-400'
        case 1: // PENDING
        case 2: // PROCESSING
            return 'bg-blue-500/20 text-blue-400'
        case 5: // FAILED
            return 'bg-red-500/20 text-red-400'
        default:
            return 'bg-gray-500/20 text-gray-400'
    }
}

function elapsedSeconds(attempt) {
    if (!attempt.finished_on) {
        return null
    }

    return Math.round((new Date(attempt.finished_on) - new Date(attempt.started_on)) / 1000)
}

function buildQuery(overrides = {}) {
    return {
        q: localFilters.q || undefined,
        sort: localFilters.sort,
        limit: localFilters.limit,
        status: localFilters.status,
        ...overrides,
    }
}

function applyFilters() {
    router.get('/manager/transcodage', buildQuery({page: 1}), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function setStatus(status) {
    localFilters.status = status
    applyFilters()
}

function goToPage(page) {
    const currentPage = props.attempts?.current_page || 1
    const lastPage = props.attempts?.last_page || 1

    if (!page || page < 1 || page > lastPage || page === currentPage) {
        return
    }

    router.get('/manager/transcodage', buildQuery({page}), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function toggleSortDirection() {
    isSortDesc.value = !isSortDesc.value
    applyFilters()
}

const emptyStateMessage = computed(() => {
    if (localFilters.q) {
        return 'Aucun résultat pour cette recherche.'
    }

    switch (localFilters.status) {
        case 'processing':
            return "Aucun traitement en cours."
        case 'failed':
            return 'Aucun échec à afficher.'
        case 'ok':
            return 'Aucune vérification réussie à afficher.'
        default:
            return 'Aucun historique à afficher.'
    }
})
</script>

<template>
    <Head title="Transcodage"/>
    <ManagerLayout leftbar-active="transcode">
        <div class="w-full">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bebas tracking-wide">Transcodage</h1>
                    <p class="text-sm text-gray-400 mt-1">Historique des vérifications et ré-encodages. Pour relancer une vidéo précise, ouvrez sa fiche.</p>
                </div>
            </div>

            <!-- Status tabs: the primary way to triage this page, so counts stay
                 visible regardless of which one is selected. -->
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <button
                    v-for="option in statusOptions"
                    :key="option.value"
                    type="button"
                    :class="[
                        'h-10 px-4 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2 border',
                        localFilters.status === option.value
                            ? 'bg-myclap-red border-myclap-red text-white'
                            : 'bg-dark-surface border-[#3a3a3a] text-gray-300 hover:bg-dark-border'
                    ]"
                    @click="setStatus(option.value)"
                >
                    {{ option.label }}
                    <span
                        :class="[
                            'text-xs px-1.5 py-0.5 rounded-full leading-none',
                            localFilters.status === option.value ? 'bg-white/20' : 'bg-dark-border text-gray-400'
                        ]"
                    >{{ option.count }}</span>
                </button>
            </div>

            <form class="bg-dark-surface rounded-lg p-4 mb-4 grid grid-cols-1 md:grid-cols-12 gap-3" @submit.prevent="applyFilters">
                <div class="md:col-span-5">
                    <input
                        v-model="localFilters.q"
                        type="text"
                        placeholder="Rechercher une vidéo..."
                        class="w-full h-10 bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 text-white focus:outline-none focus:border-myclap-red"
                    />
                </div>

                <div class="md:col-span-3">
                    <select
                        v-model="sortField"
                        class="w-full h-10 bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 text-white focus:outline-none focus:border-myclap-red"
                        @change="applyFilters"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            Trier par: {{ option.label }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <button
                        type="button"
                        class="w-full h-10 flex items-center justify-center bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 hover:bg-dark-border transition-colors"
                        @click="toggleSortDirection"
                    >
                        {{ isSortDesc ? 'Desc' : 'Asc' }}
                    </button>
                </div>

                <div class="md:col-span-2">
                    <select
                        v-model.number="localFilters.limit"
                        class="w-full h-10 bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 text-white focus:outline-none focus:border-myclap-red"
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
                {{ attempts.total || 0 }} entrées
                <span v-if="attempts.from && attempts.to"> • affichage {{ attempts.from }}–{{ attempts.to }}</span>
            </div>

            <!-- Attempts list -->
            <div v-if="attemptItems.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <div
                    v-for="attempt in attemptItems"
                    :key="attempt.id"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors cursor-pointer"
                    @click="router.visit(`/manager/videos/v/${attempt.video.token}`)"
                >
                    <img
                        :src="attempt.video.thumbnail_urls?.['120'] || attempt.video.thumbnail_url"
                        :alt="attempt.video.name"
                        class="w-24 h-14 object-cover rounded shrink-0"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ attempt.video.name }}</div>
                        <div class="text-sm text-gray-400 mt-1 flex items-center gap-1.5 flex-wrap">
                            <span>{{ formatDateTime(attempt.started_on) }}</span>
                            <template v-if="elapsedSeconds(attempt) !== null">
                                <span class="text-gray-600">•</span>
                                <span>{{ formatDuration(elapsedSeconds(attempt)) }}</span>
                            </template>
                            <template v-if="attempt.action_label">
                                <span class="text-gray-600">•</span>
                                <span>{{ attempt.action_label }}</span>
                            </template>
                        </div>
                        <p v-if="attempt.status === 5 && attempt.error" class="text-sm text-red-400 mt-1">
                            {{ attempt.error }}
                        </p>
                        <p v-else-if="attempt.reason && attempt.action !== 'skip'" class="text-sm text-gray-500 mt-1">
                            {{ attempt.reason }}
                        </p>
                    </div>
                    <div class="shrink-0">
                        <div v-if="attempt.status === 2 && attempt.progress != null" class="flex items-center gap-2 w-32">
                            <div class="flex-1 h-1.5 bg-dark-border rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 transition-all" :style="{width: `${attempt.progress}%`}"></div>
                            </div>
                            <span class="text-xs text-blue-400 w-8 text-right">{{ attempt.progress }}%</span>
                        </div>
                        <span v-else :class="['px-2 py-1 rounded text-xs whitespace-nowrap', statusClass(attempt.status)]">
                            {{ attempt.status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>{{ emptyStateMessage }}</p>
            </div>

            <div v-if="attempts.last_page > 1" class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <button
                    type="button"
                    class="px-3 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded disabled:opacity-40 disabled:cursor-not-allowed"
                    :disabled="attempts.current_page <= 1"
                    @click="goToPage(attempts.current_page - 1)"
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
                            item === attempts.current_page
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
                    :disabled="attempts.current_page >= attempts.last_page"
                    @click="goToPage(attempts.current_page + 1)"
                >
                    Suivant
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
