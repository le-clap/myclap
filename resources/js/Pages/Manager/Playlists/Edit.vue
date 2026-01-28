<script setup>
import {Head, router, useForm} from '@inertiajs/vue3'
import {ref} from 'vue'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
    playlist: {
        type: Object,
        required: true
    },
    playlistVideos: {
        type: Array,
        default: () => []
    },
    typeOptions: {
        type: Array,
        default: () => []
    },
    accessOptions: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    name: props.playlist.name || '',
    description: props.playlist.description || '',
    type: props.playlist.type || 0,
    access: props.playlist.access || 'Public',
    pinned: props.playlist.pinned || false,
    videos: props.playlistVideos.map(v => v.token),
})

const selectedVideos = ref([...props.playlistVideos])
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
                limit: 10,
                exclude: JSON.stringify(form.videos)
            }
        })
        searchResults.value = data.videos
    } catch (error) {
        console.error('Search failed:', error)
    } finally {
        searching.value = false
    }
}

function addVideo(video) {
    form.videos.push(video.token)
    selectedVideos.value.push(video)
    searchResults.value = searchResults.value.filter(v => v.token !== video.token)
    searchQuery.value = ''
}

function removeVideo(token) {
    form.videos = form.videos.filter(t => t !== token)
    selectedVideos.value = selectedVideos.value.filter(v => v.token !== token)
}

function moveVideo(index, direction) {
    const newIndex = index + direction
    if (newIndex < 0 || newIndex >= form.videos.length) return

    const video = selectedVideos.value[index]
    selectedVideos.value.splice(index, 1)
    selectedVideos.value.splice(newIndex, 0, video)

    const token = form.videos[index]
    form.videos.splice(index, 1)
    form.videos.splice(newIndex, 0, token)
}

function submit() {
    form.put(`/manager/playlists/s/${props.playlist.slug}`, {
        onSuccess: () => router.visit('/manager/playlists')
    })
}

function deletePlaylist() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette playlist ?')) {
        router.delete(`/manager/playlists/s/${props.playlist.slug}`)
    }
}
</script>

<template>
    <Head :title="`Modifier '${playlist.name}'`"/>
    <ManagerLayout leftbar-active="playlists">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-6">Modifier la playlist</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left column - Form -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Nom *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                maxlength="75"
                                class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Type</label>
                            <select
                                v-model="form.type"
                                class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white"
                            >
                                <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Accès</label>
                            <select
                                v-model="form.access"
                                class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white"
                            >
                                <option v-for="opt in accessOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Mise en avant</label>
                            <button
                                type="button"
                                @click="form.pinned = !form.pinned"
                                :class="[
                                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm transition-colors',
                                    form.pinned
                                        ? 'bg-myclap-red text-white'
                                        : 'bg-dark-border text-gray-300 hover:bg-[#3a3a3a]'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M16 4a1 1 0 0 1 .117 1.993L16 6v4.379l1.707 1.707a1 1 0 0 1 .293.707V14a1 1 0 0 1-.883.993L17 15h-4v5a1 1 0 0 1-1.993.117L11 20v-5H7a1 1 0 0 1-.993-.883L6 14v-1.207a1 1 0 0 1 .293-.707L8 10.379V6a1 1 0 0 1-.117-1.993L8 4h8z"/>
                                </svg>
                                Épingler en haut de la liste
                            </button>
                        </div>
                    </div>

                    <!-- Right column - Videos -->
                    <div>
                        <label class="block text-sm font-medium mb-2">Vidéos de la playlist</label>

                        <!-- Search -->
                        <div class="relative mb-4">
                            <input
                                v-model="searchQuery"
                                @input="searchVideos"
                                type="text"
                                placeholder="Ajouter une vidéo..."
                                class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                            <div v-if="searchResults.length > 0"
                                 class="absolute z-10 w-full mt-1 bg-dark-surface border border-[#3a3a3a] rounded-lg max-h-48 overflow-y-auto">
                                <button
                                    v-for="video in searchResults"
                                    :key="video.token"
                                    type="button"
                                    @click="addVideo(video)"
                                    class="w-full text-left px-4 py-2 hover:bg-dark-border flex items-center gap-2"
                                >
                                    <img :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                                         class="w-16 h-9 object-cover rounded"/>
                                    <span class="truncate">{{ video.name }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Video list -->
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            <div
                                v-for="(video, idx) in selectedVideos"
                                :key="video.token"
                                class="flex items-center gap-2 bg-dark-surface rounded-lg p-2"
                            >
                                <span class="text-gray-500 w-6 text-center">{{ idx + 1 }}</span>
                                <img :src="video.thumbnail_urls?.['120'] || video.thumbnail_url"
                                     class="w-16 h-9 object-cover rounded"/>
                                <span class="flex-1 truncate text-sm">{{ video.name }}</span>
                                <div class="flex gap-1">
                                    <button type="button" @click="moveVideo(idx, -1)"
                                            class="p-1 hover:bg-[#3a3a3a] rounded">
                                        ↑
                                    </button>
                                    <button type="button" @click="moveVideo(idx, 1)"
                                            class="p-1 hover:bg-[#3a3a3a] rounded">
                                        ↓
                                    </button>
                                    <button type="button" @click="removeVideo(video.token)"
                                            class="p-1 hover:bg-red-500/20 rounded text-red-400">
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <div v-if="selectedVideos.length === 0" class="text-center py-8 text-gray-500">
                                Aucune vidéo dans cette playlist
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-4 pt-4 border-t border-dark-border">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Sauvegarde...' : 'Sauvegarder' }}
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/manager/playlists')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Retour
                    </button>
                </div>
            </form>

            <!-- Delete -->
            <div class="mt-12 pt-6 border-t border-dark-border">
                <button
                    @click="deletePlaylist"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                >
                    Supprimer la playlist
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
