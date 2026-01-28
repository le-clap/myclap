<script setup>
import {Head, router} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import VideoUploader from '@/Components/VideoUploader.vue'

const props = defineProps({
    video: {
        type: Object,
        required: true
    },
    uploadProgress: {
        type: Object,
        default: null
    }
})

function onUploadComplete() {
    router.visit(`/manager/videos/v/${props.video.token}`)
}
</script>

<template>
    <Head title="Mise en ligne"/>
    <ManagerLayout leftbar-active="videos">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-2">Envoyer la vidéo</h1>
            <p class="text-gray-400 mb-6">{{ video.name }}</p>

            <!-- Resume progress info -->
            <div v-if="uploadProgress" class="bg-dark-surface rounded-lg p-4 mb-6">
                <h3 class="font-medium mb-2">Upload en cours</h3>
                <p class="text-sm text-gray-400">{{ uploadProgress.fileName }}</p>
                <div class="relative h-2 bg-dark-border rounded-full overflow-hidden mt-2">
                    <div
                        class="absolute inset-y-0 left-0 bg-blue-500"
                        :style="{ width: uploadProgress.percentage + '%' }"
                    ></div>
                </div>
                <p class="text-sm text-gray-400 mt-1">
                    {{ (uploadProgress.uploadedSize / (1024 * 1024)).toFixed(2) }} MB /
                    {{ (uploadProgress.fileSize / (1024 * 1024)).toFixed(2) }} MB
                    ({{ uploadProgress.percentage }}%)
                </p>
            </div>

            <VideoUploader
                :video-token="video.token"
                @complete="onUploadComplete"
            />

            <div class="mt-8">
                <button
                    @click="router.visit(`/manager/videos/v/${video.token}`)"
                    class="px-4 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                >
                    Retour
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
