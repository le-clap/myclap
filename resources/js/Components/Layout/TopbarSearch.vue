<script setup>
import {onMounted, onUnmounted, ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import axios from 'axios'
import NavIcon from './NavIcon.vue'

const mobileOpen = defineModel('mobileOpen', {type: Boolean, default: false})

const searchQuery = ref('')
const searchResults = ref([])
const showResults = ref(false)
const isSearching = ref(false)
let searchTimeout = null

function submitSearch() {
    if (searchQuery.value.length > 0) {
        showResults.value = false
        router.get('/search', {value: searchQuery.value})
    }
}

function performLiveSearch() {
    if (searchQuery.value.length < 2) {
        searchResults.value = []
        showResults.value = false
        return
    }

    isSearching.value = true

    axios.get('/api/search', {
        params: {q: searchQuery.value, limit: 5}
    })
        .then(response => {
            searchResults.value = response.data.videos.slice(0, 5)
            showResults.value = true
        })
        .catch(() => {
            searchResults.value = []
        })
        .finally(() => {
            isSearching.value = false
        })
}

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }

    if (searchQuery.value.length < 2) {
        searchResults.value = []
        showResults.value = false
        return
    }

    searchTimeout = setTimeout(performLiveSearch, 300)
})

function selectVideo(token) {
    showResults.value = false
    searchQuery.value = ''
    router.get(`/regarder/${token}`)
}

function handleClickOutside(event) {
    const searchContainer = document.getElementById('search-container')
    if (searchContainer && !searchContainer.contains(event.target)) {
        showResults.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    if (searchTimeout) {
        clearTimeout(searchTimeout)
    }
})
</script>

<template>
    <!-- Search - Centered -->
    <div class="hidden md:flex flex-1 justify-center px-4">
        <form
            @submit.prevent="submitSearch"
            id="search-container"
            class="relative w-full max-w-2xl"
        >
            <div class="flex w-full">
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Rechercher..."
                    class="flex-1 bg-[#121212] border border-[#3a3a3a] rounded-l-full px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-[#ff0502]"
                    @focus="searchQuery.length >= 2 && searchResults.length > 0 && (showResults = true)"
                />
                <button
                    type="submit"
                    class="px-4 py-2 bg-[#3a3a3a] hover:bg-[#4a4a4a] border border-l-0 border-[#3a3a3a] rounded-r-full transition-colors"
                    aria-label="Rechercher"
                >
                    <NavIcon name="search" class="w-5 h-5"/>
                </button>
            </div>

            <!-- Live search results dropdown -->
            <div
                v-if="showResults && searchResults.length > 0"
                class="absolute top-full mt-2 w-full bg-dark-surface border border-[#3a3a3a] rounded-lg shadow-xl overflow-hidden z-50"
            >
                <div
                    v-for="video in searchResults"
                    :key="video.token"
                    @click="selectVideo(video.token)"
                    class="flex items-center gap-3 p-3 hover:bg-dark-border cursor-pointer transition-colors"
                >
                    <img
                        :src="video.thumbnail_urls?.['120']"
                        :alt="video.name"
                        class="w-24 h-16 object-cover rounded"
                    />
                    <div class="flex-1 min-w-0">
                        <h4 class="text-white font-medium truncate">{{ video.name }}</h4>
                    </div>
                    <span class="text-gray-500 text-sm">{{ video.views }} vues</span>
                </div>
                <div class="border-t border-[#3a3a3a] p-2 text-center">
                    <button
                        @click.prevent="submitSearch"
                        class="text-myclap-red hover:text-[#cc0402] text-sm"
                    >
                        Voir tous les résultats
                    </button>
                </div>
            </div>

            <!-- Loading indicator -->
            <div
                v-if="isSearching"
                class="absolute top-full mt-2 w-full bg-dark-surface border border-[#3a3a3a] rounded-lg p-4 text-center text-gray-400"
            >
                Recherche en cours...
            </div>
        </form>
    </div>

    <!-- Mobile search overlay -->
    <div
        v-if="mobileOpen"
        class="fixed inset-0 bg-dark-bg z-50 pt-4 px-4 md:hidden"
    >
        <form @submit.prevent="submitSearch" class="flex gap-2">
            <button
                type="button"
                @click="mobileOpen = false"
                class="p-2"
                aria-label="Fermer la recherche"
            >
                <NavIcon name="arrow-left" class="w-6 h-6"/>
            </button>
            <input
                v-model="searchQuery"
                type="search"
                placeholder="Rechercher..."
                class="flex-1 bg-[#121212] border border-[#3a3a3a] rounded-full px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-myclap-red"
                autofocus
            />
        </form>
    </div>
</template>
