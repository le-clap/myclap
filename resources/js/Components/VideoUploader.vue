<script setup>
import {ref} from 'vue'
import axios from 'axios'

const props = defineProps({
    videoToken: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['complete', 'error'])

const file = ref(null)
const uploading = ref(false)
const progress = ref(0)
const error = ref(null)
const status = ref('')
const dragOver = ref(false)

const CHUNK_SIZE = 2 * 1024 * 1024 // 2 MB chunks

function handleFileSelect(event) {
    const selectedFile = event.target.files[0]
    if (selectedFile && selectedFile.type.startsWith('video/')) {
        file.value = selectedFile
        error.value = null
    } else {
        error.value = 'Veuillez sélectionner un fichier vidéo valide'
    }
}

function handleDragOver(event) {
    event.preventDefault()
    event.stopPropagation()
    dragOver.value = true
}

function handleDragLeave(event) {
    event.preventDefault()
    event.stopPropagation()
    dragOver.value = false
}

function handleDrop(event) {
    event.preventDefault()
    event.stopPropagation()
    dragOver.value = false

    const droppedFile = event.dataTransfer.files[0]
    if (droppedFile && droppedFile.type.startsWith('video/')) {
        file.value = droppedFile
        error.value = null
    } else {
        error.value = 'Veuillez déposer un fichier vidéo valide'
    }
}

async function uploadFile() {
    if (!file.value) return

    uploading.value = true
    progress.value = 0
    error.value = null
    status.value = 'Initialisation...'

    try {
        // 1. Init upload
        const {data: initData} = await axios.post(
            `/manager/videos/v/${props.videoToken}/upload/init`,
            {
                fileName: file.value.name,
                fileSize: file.value.size,
            }
        )

        let startIndex = initData.startIndex
        let chunkSize = initData.chunkSize || CHUNK_SIZE

        status.value = 'Envoi en cours...'

        // 2. Upload chunks
        while (startIndex < file.value.size) {
            const chunk = file.value.slice(startIndex, startIndex + chunkSize)

            const formData = new FormData()
            formData.append('fileChunk', chunk)
            formData.append('startIndex', startIndex)
            formData.append('chunkSize', chunkSize)

            const {data: processData} = await axios.post(
                `/manager/videos/v/${props.videoToken}/upload/process`,
                formData
            )

            startIndex = processData.startIndex
            chunkSize = processData.chunkSize || chunkSize
            progress.value = Math.min((startIndex / file.value.size) * 100, 99)

            if (processData.completed) {
                break
            }
        }

        // 3. Finalize
        status.value = 'Finalisation...'
        const {data: endData} = await axios.post(`/manager/videos/v/${props.videoToken}/upload/end`)
        progress.value = 100
        status.value = 'Upload terminé !'
        uploading.value = false
        emit('complete')

    } catch (err) {
        error.value = err.response?.data?.message || err.message || 'Erreur lors de l\'upload'
        uploading.value = false
        status.value = ''
        emit('error', error.value)
    }
}

async function resetUpload() {
    try {
        await axios.post(`/manager/videos/v/${props.videoToken}/upload/reset`)
        file.value = null
        progress.value = 0
        error.value = null
        status.value = ''
    } catch (err) {
        error.value = 'Erreur lors de la réinitialisation'
    }
}
</script>

<template>
    <div class="video-uploader">
        <!-- File selection -->
        <div v-if="!uploading && !error" class="space-y-4">
            <div
                @dragover="handleDragOver"
                @dragleave="handleDragLeave"
                @drop="handleDrop"
                :class="['border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                         dragOver ? 'border-myclap-red bg-red-900/10' : 'border-[#3a3a3a] hover:border-myclap-red']">
                <input
                    type="file"
                    accept="video/*"
                    @change="handleFileSelect"
                    class="hidden"
                    id="video-file"
                />
                <label for="video-file" class="cursor-pointer block">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-gray-400">
                        <span v-if="file">{{ file.name }}</span>
                        <span v-else>{{ dragOver ? 'Déposez votre vidéo ici' : 'Cliquez ou déposez une vidéo' }}</span>
                    </p>
                    <p v-if="file" class="text-sm text-gray-500 mt-2">
                        {{ (file.size / (1024 * 1024)).toFixed(2) }} MB
                    </p>
                </label>
            </div>

            <button
                v-if="file"
                @click="uploadFile"
                class="w-full py-3 bg-myclap-red hover:bg-[#cc0402] text-white font-medium rounded-lg transition-colors"
            >
                Envoyer la vidéo
            </button>
        </div>

        <!-- Upload progress -->
        <div v-if="uploading" class="space-y-4">
            <div class="text-center text-gray-400">{{ status }}</div>
            <div class="relative h-4 bg-dark-border rounded-full overflow-hidden">
                <div
                    class="absolute inset-y-0 left-0 bg-myclap-red transition-all duration-300"
                    :style="{ width: progress + '%' }"
                ></div>
            </div>
            <div class="text-center text-xl font-bold">{{ Math.round(progress) }}%</div>
        </div>

        <!-- Error state -->
        <div v-if="error" class="space-y-4">
            <div class="bg-red-900/20 border border-red-500 rounded-lg p-4 text-red-400">
                {{ error }}
            </div>
            <button
                @click="resetUpload"
                class="w-full py-3 bg-[#3a3a3a] hover:bg-[#4a4a4a] text-white font-medium rounded-lg transition-colors"
            >
                Réessayer
            </button>
        </div>

        <!-- Success state -->
        <div v-if="progress === 100 && !uploading && !error" class="space-y-4">
            <div class="bg-green-900/20 border border-green-500 rounded-lg p-4 text-green-400 text-center">
                ✓ Upload terminé avec succès !
            </div>
        </div>
    </div>
</template>
