<script setup>
import {onMounted, ref} from 'vue'
import {Head} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import VideoCard from '@/Components/VideoCard.vue'
import axios from 'axios'

const props = defineProps({
    videos: {
        type: Array,
        default: () => []
    },
    billboards: {
        type: Array,
        default: () => []
    }
})

const allVideos = ref([])
const loading = ref(false)
const hasMore = ref(true)
const currentBillboard = ref(0)

onMounted(() => {
    allVideos.value = [...props.videos]

    // Auto-rotate billboards
    if (props.billboards.length > 1) {
        setInterval(() => {
            currentBillboard.value = (currentBillboard.value + 1) % props.billboards.length
        }, 10_000)
    }
})

async function loadMore() {
    if (loading.value || !hasMore.value) return

    loading.value = true
    try {
        const {data} = await axios.get('/api/videos', {
            params: {
                offset: allVideos.value.length,
                limit: 8
            }
        })

        if (data.videos.length > 0) {
            allVideos.value.push(...data.videos)
        } else {
            hasMore.value = false
        }
    } catch (error) {
        console.error('Failed to load more videos:', error)
    } finally {
        loading.value = false
    }
}

function getGradientStyle(color) {
    const gradients = {
        'gradient-dark-red': 'linear-gradient(135deg, #8B0000 0%, #ff0502 100%)',
        'gradient-calm-darya': 'linear-gradient(135deg, #5f2c82 0%, #49a09d 100%)',
        'gradient-purple-dream': 'linear-gradient(135deg, #c471f5 0%, #fa71cd 100%)',
        'gradient-sexy-blue': 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)',
        'gradient-emerald-water': 'linear-gradient(135deg, #348f50 0%, #56b4d3 100%)',
    }
    return gradients[color] || gradients['gradient-dark-red']
}

function goToBillboard(index) {
    currentBillboard.value = index
}
</script>

<template>
    <Head title="Accueil">
    </Head>
    <AppLayout leftbar-active="accueil">
        <div class="mx-auto px-4" style="max-width: 95%;">
            <!-- Billboard / Annonces -->
            <div v-if="billboards.length > 0" class="mb-8">
                <div class="relative">
                    <a
                        v-for="(billboard, index) in billboards"
                        :key="billboard.identifier"
                        :href="billboard.url"
                        :class="[
                            'block rounded-lg p-8 md:p-12 text-center transition-opacity duration-500',
                            index === currentBillboard ? 'opacity-100' : 'opacity-0 absolute inset-0'
                        ]"
                        :style="{ background: getGradientStyle(billboard.color) }"
                    >
                        <h2 class="font-bebas-xl mb-4 text-white">{{ billboard.title }}</h2>
                        <span
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white/90 hover:bg-white text-gray-900 rounded-lg font-medium transition-colors">
                            <i v-if="billboard.icon" :class="['fas', billboard.icon]"></i>
                            {{ billboard.button }}
                        </span>
                    </a>
                </div>

                <!-- Indicators -->
                <div v-if="billboards.length > 1" class="flex justify-center gap-2 mt-4">
                    <button
                        v-for="(_, index) in billboards"
                        :key="index"
                        @click="goToBillboard(index)"
                        :class="[
                            'w-2 h-2 rounded-full transition-colors',
                            index === currentBillboard ? 'bg-white' : 'bg-white/30 hover:bg-white/50'
                        ]"
                    ></button>
                </div>
            </div>

            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Nouveautés</h1>
            </div>

            <!-- Video grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <VideoCard
                    v-for="video in allVideos"
                    :key="video.token"
                    :video="video"
                />
            </div>

            <!-- Load more (infinite scroll placeholder) -->
            <div
                v-if="allVideos.length >= 12"
                class="mt-8 text-center"
            >
                <button
                    @click="loadMore"
                    :disabled="loading"
                    class="px-6 py-2 bg-[#2a2a3a] hover:bg-[#3a3a3a] rounded-full text-white disabled:opacity-50"
                >
                    {{ loading ? 'Chargement...' : 'Voir plus' }}
                </button>
            </div>

            <!-- Empty state -->
            <div v-if="allVideos.length === 0" class="text-center py-12 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <p>Aucune vidéo pour le moment</p>
            </div>
        </div>
    </AppLayout>
</template>
