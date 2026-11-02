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

            <VideoUploader
                :video-token="video.token"
                :upload-progress="uploadProgress"
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
