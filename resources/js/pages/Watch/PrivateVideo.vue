<script setup>
import {Head} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineProps({
    video: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <Head :title="video.name"/>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Video placeholder -->
            <div class="bg-dark-surface rounded-lg overflow-hidden mb-6">
                <div class="aspect-video flex items-center justify-center bg-[#0a0a0a]">
                    <div class="text-center p-8">
                        <div
                            class="w-20 h-20 mx-auto mb-6 bg-myclap-red/20 rounded-full flex items-center justify-center">
                            <svg class="w-10 h-10 text-myclap-red" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <p class="text-xl text-white mb-4">
                            Cette vidéo est privée. Vous n'êtes pas autorisé à la visionner.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Masked video info -->
            <div class="bg-dark-surface rounded-lg p-6">
                <!-- Title placeholder -->
                <div class="mb-4">
                    <div class="h-8 bg-white/20 rounded w-3/4 max-w-md animate-pulse"></div>
                </div>

                <!-- Subtitle placeholder -->
                <div class="mb-6">
                    <div class="h-4 bg-white/15 rounded w-1/2 max-w-xs animate-pulse"></div>
                </div>

                <hr class="border-dark-border mb-6">

                <!-- Description placeholders -->
                <div class="space-y-3">
                    <div class="h-3 bg-white/10 rounded w-full animate-pulse"></div>
                    <div class="h-3 bg-white/10 rounded w-full animate-pulse"></div>
                    <div class="h-3 bg-white/10 rounded w-full animate-pulse"></div>
                    <div class="h-3 bg-white/10 rounded w-1/4 animate-pulse"></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
