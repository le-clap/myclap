<script setup>
import {Head, Link, usePage} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoPlayer from '@/Components/VideoPlayer.vue'
import axios from 'axios'

const props = defineProps({
    playlist: {
        type: Object,
        required: true
    },
    video: {
        type: Object,
        required: true
    },
    videos: {
        type: Array,
        default: () => []
    },
    currentIndex: {
        type: Number,
        default: 0
    },
    userDidLike: {
        type: Boolean,
        default: false
    }
})

const page = usePage()
const user = computed(() => page.props.auth.user)

const liked = ref(props.userDidLike)
const likeCount = ref(props.video.reactions || 0)
const likeLoading = ref(false)

const prevVideo = computed(() => props.currentIndex > 0 ? props.videos[props.currentIndex - 1] : null)
const nextVideo = computed(() => props.currentIndex < props.videos.length - 1 ? props.videos[props.currentIndex + 1] : null)

async function toggleLike() {
    if (!user.value) {
        window.location.href = '/login'
        return
    }

    if (likeLoading.value) return

    likeLoading.value = true
    try {
        const {data} = await axios.post(`/api/reaction/${props.video.token}`)
        liked.value = data.active
        likeCount.value += liked.value ? 1 : -1
    } catch (error) {
        console.error('Like toggle failed:', error)
    } finally {
        likeLoading.value = false
    }
}
</script>

<template>
    <Head :title="video.name"/>
    <AppLayout leftbar-active="playlists">
        <div class="flex gap-4 max-w-7xl mx-auto">
            <!-- Main content -->
            <div class="flex-1 min-w-0">
                <!-- Video Player -->
                <div class="mb-4">
                    <VideoPlayer :video="video" view-source="playlist"/>
                </div>

                <!-- Video Info -->
                <div class="space-y-4">
                    <h1 class="text-xl lg:text-2xl font-bold">{{ video.name }}</h1>

                    <div class="flex flex-wrap items-center gap-4 text-gray-400">
                        <span>{{ video.views?.toLocaleString() || 0 }} vues</span>
                        <span>Vidéo {{ currentIndex + 1 }}/{{ videos.length }}</span>
                    </div>

                    <!-- Navigation buttons -->
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="prevVideo"
                            :href="`/playlists/${playlist.slug}/${prevVideo.token}`"
                            class="flex items-center gap-2 px-4 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 19l-7-7 7-7"/>
                            </svg>
                            Précédent
                        </Link>
                        <Link
                            v-if="nextVideo"
                            :href="`/playlists/${playlist.slug}/${nextVideo.token}`"
                            class="flex items-center gap-2 px-4 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                        >
                            Suivant
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4 py-4 border-y border-dark-border">
                        <button
                            @click="toggleLike"
                            :disabled="likeLoading"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-full transition-colors',
                                liked
                                    ? 'bg-pink-500/20 text-pink-500'
                                    : 'bg-dark-border hover:bg-[#3a3a3a] text-white'
                            ]"
                        >
                            <svg class="w-5 h-5" :fill="liked ? 'currentColor' : 'none'" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span>{{ likeCount }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Playlist sidebar -->
            <div class="hidden lg:block w-80 shrink-0">
                <div class="bg-dark-surface rounded-lg p-4">
                    <h2 class="font-bold mb-2">{{ playlist.name }}</h2>
                    <p class="text-sm text-gray-400 mb-4">{{ videos.length }} vidéos</p>

                    <div class="space-y-2 max-h-[60vh] overflow-y-auto">
                        <Link
                            v-for="(v, idx) in videos"
                            :key="v.token"
                            :href="`/playlists/${playlist.slug}/${v.token}`"
                            :class="[
                                'flex gap-2 p-2 rounded-lg transition-colors',
                                v.token === video.token
                                    ? 'bg-dark-border'
                                    : 'hover:bg-dark-border'
                            ]"
                        >
                            <span class="text-gray-500 w-6 text-center">{{ idx + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <img
                                    :src="v.thumbnail_urls?.['120'] || v.thumbnail_url"
                                    :alt="v.name"
                                    class="w-full aspect-video object-cover rounded mb-1"
                                />
                                <p class="text-sm line-clamp-2">{{ v.name }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
