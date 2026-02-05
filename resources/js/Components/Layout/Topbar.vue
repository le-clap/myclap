<script setup>
import {computed, onMounted, onUnmounted, ref, watch} from 'vue'
import {Link, router, usePage} from '@inertiajs/vue3'
import axios from 'axios'

const page = usePage()
const user = computed(() => page.props.auth.user)
const permissions = computed(() => page.props.auth.permissions || [])

const searchQuery = ref('')
const showMobileSearch = ref(false)
const searchResults = ref([])
const showResults = ref(false)
const isSearching = ref(false)
let searchTimeout = null

function hasPermission(perm) {
    return permissions.value.includes(perm) || permissions.value.includes('admin')
}

function hasPermissionGroup(group) {
    return permissions.value.some(p => p.startsWith(group + '.')) || permissions.value.includes('admin')
}

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
    <header
        class="fixed top-0 left-0 right-0 h-14 bg-dark-surface border-b border-dark-border z-50 flex items-center px-4">
        <!-- Logo -->
        <div class="flex items-center gap-4">
            <Link href="/" class="flex items-center gap-2">
                <img src="/static/myclap/myclap.png" alt="myCLAP" class="h-10"/>
            </Link>
        </div>

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
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
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
                            :src="video.thumbnail_urls?.['120'] || video.thumbnail_url || '/static/myclap/thumbnail/placeholder.png'"
                            :alt="video.title"
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

        <!-- Right buttons -->
        <div class="flex items-center gap-2 ml-auto">
            <!-- Mobile search -->
            <button
                @click="showMobileSearch = !showMobileSearch"
                class="md:hidden p-2 hover:bg-[#3a3a3a] rounded-full"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <template v-if="user">
                <!-- Add video -->
                <Link
                    v-if="hasPermission('manager.video.upload')"
                    href="/manager/videos/ajouter"
                    class="p-2 hover:bg-[#3a3a3a] rounded-full"
                    title="Ajouter une vidéo"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </Link>

                <!-- Manager -->
                <Link
                    v-if="hasPermissionGroup('manager')"
                    href="/manager"
                    class="p-2 hover:bg-[#3a3a3a] rounded-full"
                    title="myCLAP Manager"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </Link>

                <!-- Logout -->
                <Link
                    href="/logout"
                    class="p-2 hover:bg-[#3a3a3a] rounded-full"
                    title="Se déconnecter"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </Link>
            </template>

            <template v-else>
                <a
                    href="/login"
                    class="px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-full text-white font-medium text-sm"
                >
                    Se connecter
                </a>
            </template>
        </div>
    </header>

    <!-- Mobile search overlay -->
    <div
        v-if="showMobileSearch"
        class="fixed inset-0 bg-dark-bg z-50 pt-4 px-4 md:hidden"
    >
        <form @submit.prevent="submitSearch" class="flex gap-2">
            <button
                type="button"
                @click="showMobileSearch = false"
                class="p-2"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
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
