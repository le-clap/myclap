<script setup>
import {Head, usePage} from '@inertiajs/vue3'
import {computed, ref} from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoPlayer from '@/Components/VideoPlayer.vue'
import {formatDate} from '@/utils/date'
import axios from 'axios'

const props = defineProps({
    video: {
        type: Object,
        required: true
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

const categories = computed(() => {
    return props.video.categories || []
})
</script>

<template>
    <Head :title="video.name"/>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Video Player -->
            <div class="mb-4">
                <VideoPlayer :video="video" view-source="regular"/>
            </div>

            <!-- Video Info -->
            <div class="space-y-4">
                <h1 class="text-xl lg:text-2xl font-bold">{{ video.name }}</h1>

                <div class="flex flex-wrap items-center gap-4 text-gray-400">
                    <span>{{ video.views?.toLocaleString() || 0 }} vues</span>
                    <span>•</span>
                    <span>{{ formatDate(video.created_on) }}</span>
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

                    <a
                        :href="`/download/${video.token}`"
                        class="flex items-center gap-2 px-4 py-2 rounded-full bg-dark-border hover:bg-[#3a3a3a] text-white transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Télécharger</span>
                    </a>
                </div>

                <!-- Description -->
                <div v-if="video.description" class="bg-dark-surface rounded-lg p-4">
                    <p class="whitespace-pre-wrap text-gray-300">{{ video.description }}</p>
                </div>

                <!-- Categories -->
                <div v-if="categories.length > 0" class="flex flex-wrap gap-2">
                    <span
                        v-for="cat in categories"
                        :key="cat.slug"
                        class="px-3 py-1 bg-dark-border rounded-full text-sm text-gray-300"
                    >
                        {{ cat.label }}
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
