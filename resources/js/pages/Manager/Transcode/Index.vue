<script setup>
import {computed, reactive, watch} from 'vue'
import {Head, Link, router, usePoll} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import {formatDateTime, formatDuration} from '@/utils/date'
import {TRANSCODE_STATUS_CLASS} from '@/utils/video'

const props = defineProps({
    attempts: {
        type: Object,
        default: () => ({data: [], links: [], last_page: 1, total: 0, from: null, to: null})
    },
    filters: {
        type: Object,
        default: () => ({q: '', status: 'all'})
    },
    statusOptions: {
        type: Array,
        default: () => []
    }
})

const localFilters = reactive({
    q: props.filters.q || '',
    status: props.filters.status || 'all',
})

watch(() => props.filters, (nextFilters) => {
    localFilters.q = nextFilters.q || ''
    localFilters.status = nextFilters.status || 'all'
})

const attemptItems = computed(() => props.attempts?.data || [])

const hasInFlightAttempts = computed(() => attemptItems.value.some(a => [1, 2].includes(a.status)))

const poll = usePoll(2500, {only: ['attempts']}, {autoStart: false})

watch(hasInFlightAttempts, (inFlight) => inFlight ? poll.start() : poll.stop(), {immediate: true})

// Laravel's paginator links, minus its own untranslated prev/next entries.
const pageLinks = computed(() => (props.attempts?.links || []).slice(1, -1))

function elapsedSeconds(attempt) {
    if (!attempt.finished_on) {
        return null
    }

    return Math.round((new Date(attempt.finished_on) - new Date(attempt.started_on)) / 1000)
}

function applyFilters() {
    router.get('/manager/transcodage', {q: localFilters.q || undefined, status: localFilters.status}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function setStatus(status) {
    localFilters.status = status
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

const showEmptyCheck = computed(() => !localFilters.q && ['processing', 'failed'].includes(localFilters.status))
</script>

<template>
    <Head title="Transcodage"/>
    <ManagerLayout leftbar-active="transcode">
        <div class="w-full">
            <div class="mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Transcodage</h1>
                <p class="text-sm text-gray-400 mt-1">Historique des transcodages. Pour relancer une vidéo précise, ouvrez sa fiche.</p>
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

            <form class="bg-dark-surface rounded-lg p-4 mb-4" @submit.prevent="applyFilters">
                <input
                    v-model="localFilters.q"
                    type="search"
                    placeholder="Rechercher une vidéo..."
                    aria-label="Rechercher une vidéo"
                    class="w-full h-10 bg-[#1f1f1f] border border-[#3a3a3a] rounded-lg px-3 text-white focus:outline-none focus:border-myclap-red"
                />
            </form>

            <div class="text-sm text-gray-400 mb-4">
                {{ attempts.total || 0 }} entrées
                <span v-if="attempts.from && attempts.to"> • affichage {{ attempts.from }}–{{ attempts.to }}</span>
            </div>

            <!-- Attempts list -->
            <div v-if="attemptItems.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="attempt in attemptItems"
                    :key="attempt.id"
                    :href="`/manager/videos/v/${attempt.video.token}`"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                >
                    <img
                        :src="attempt.video.thumbnail_urls['120']"
                        alt=""
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
                            <div
                                role="progressbar"
                                :aria-valuenow="attempt.progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-label="Progression de l'encodage"
                                class="flex-1 h-1.5 bg-dark-border rounded-full overflow-hidden"
                            >
                                <div class="h-full bg-blue-500 transition-all" :style="{width: `${attempt.progress}%`}"></div>
                            </div>
                            <span class="text-xs text-blue-400 w-8 text-right">{{ attempt.progress }}%</span>
                        </div>
                        <span v-else :class="['px-2 py-1 rounded text-xs whitespace-nowrap', TRANSCODE_STATUS_CLASS[attempt.status]]">
                            {{ attempt.status_label }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <svg v-if="showEmptyCheck" class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>{{ emptyStateMessage }}</p>
            </div>

            <nav v-if="attempts.last_page > 1" aria-label="Pagination" class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <component
                    :is="attempts.prev_page_url ? Link : 'span'"
                    :href="attempts.prev_page_url"
                    preserve-scroll
                    :class="['px-3 py-2 bg-dark-border rounded', attempts.prev_page_url ? 'hover:bg-[#3a3a3a]' : 'opacity-40']"
                >
                    Précédent
                </component>

                <template v-for="(link, index) in pageLinks" :key="index">
                    <span v-if="!link.url" class="px-2 text-gray-400">{{ link.label }}</span>
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        :aria-current="link.active ? 'page' : undefined"
                        :class="[
                            'px-3 py-2 rounded transition-colors',
                            link.active ? 'bg-myclap-red text-white' : 'bg-dark-border hover:bg-[#3a3a3a]'
                        ]"
                    >
                        {{ link.label }}
                    </Link>
                </template>

                <component
                    :is="attempts.next_page_url ? Link : 'span'"
                    :href="attempts.next_page_url"
                    preserve-scroll
                    :class="['px-3 py-2 bg-dark-border rounded', attempts.next_page_url ? 'hover:bg-[#3a3a3a]' : 'opacity-40']"
                >
                    Suivant
                </component>
            </nav>
        </div>
    </ManagerLayout>
</template>
