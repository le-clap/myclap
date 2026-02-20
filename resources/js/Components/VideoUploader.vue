<script setup>
import {ref, computed, onUnmounted} from 'vue'
import axios from 'axios'

const props = defineProps({
    videoToken: {
        type: String,
        required: true
    },
    uploadProgress: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['complete', 'error'])

const file = ref(null)
const uploading = ref(false)
const progress = ref(0)
const uploadedSize = ref(0)
const totalSize = ref(0)
const error = ref(null)
const status = ref('')
const dragOver = ref(false)
const abortController = ref(null)
const cancelled = ref(false)
const resumeFileInput = ref(null)

const CHUNK_SIZE = 2 * 1024 * 1024 // 2 Mio chunks

const hasResumableUpload = computed(() => {
    return props.uploadProgress && !cancelled.value && !uploading.value && progress.value < 100
})

function formatSize(bytes) {
    if (bytes >= 1024 * 1024 * 1024) return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' Gio'
    return (bytes / (1024 * 1024)).toFixed(2) + ' Mio'
}

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

function triggerResumeFileSelect() {
    resumeFileInput.value.click()
}

function handleResumeFileSelect(event) {
    const selectedFile = event.target.files[0]
    if (selectedFile && selectedFile.type.startsWith('video/')) {
        file.value = selectedFile
        error.value = null
        uploadFile()
    } else {
        error.value = 'Veuillez sélectionner un fichier vidéo valide'
    }
}

async function uploadFile() {
    if (!file.value) return

    uploading.value = true
    progress.value = 0
    uploadedSize.value = 0
    totalSize.value = file.value.size
    error.value = null
    status.value = 'Initialisation...'
    abortController.value = new AbortController()

    try {
        const signal = abortController.value.signal

        const {data: initData} = await axios.post(
            `/manager/videos/v/${props.videoToken}/upload/init`,
            {
                fileName: file.value.name,
                fileSize: file.value.size,
            },
            {signal}
        )

        let startIndex = initData.startIndex
        let chunkSize = initData.chunkSize || CHUNK_SIZE

        status.value = 'Envoi en cours...'
        uploadedSize.value = startIndex
        if (totalSize.value > 0) {
            progress.value = Math.min((startIndex / totalSize.value) * 100, 99)
        }

        while (startIndex < file.value.size) {
            const chunk = file.value.slice(startIndex, startIndex + chunkSize)

            const formData = new FormData()
            formData.append('fileChunk', chunk)
            formData.append('startIndex', startIndex)
            formData.append('chunkSize', chunkSize)

            const {data: processData} = await axios.post(
                `/manager/videos/v/${props.videoToken}/upload/process`,
                formData,
                {signal}
            )

            startIndex = processData.startIndex
            chunkSize = processData.chunkSize || chunkSize
            uploadedSize.value = startIndex
            progress.value = Math.min((startIndex / totalSize.value) * 100, 99)

            if (processData.completed) break
        }

        status.value = 'Finalisation...'
        await axios.post(`/manager/videos/v/${props.videoToken}/upload/end`, null, {signal})
        progress.value = 100
        uploadedSize.value = totalSize.value
        status.value = 'Upload terminé !'
        uploading.value = false
        emit('complete')

    } catch (err) {
        if (axios.isCancel(err)) return
        error.value = err.response?.data?.message || err.message || 'Erreur lors de l\'upload'
        uploading.value = false
        status.value = ''
        emit('error', error.value)
    }
}

async function cancelUpload() {
    if (abortController.value) {
        abortController.value.abort()
        abortController.value = null
    }
    uploading.value = false
    status.value = ''
    error.value = null

    try {
        await axios.post(`/manager/videos/v/${props.videoToken}/upload/reset`)
    } catch (_) {}

    file.value = null
    progress.value = 0
    uploadedSize.value = 0
    totalSize.value = 0
    cancelled.value = true
}

onUnmounted(() => {
    if (abortController.value) {
        abortController.value.abort()
        abortController.value = null
    }
    uploading.value = false
    status.value = ''
})
</script>

<template>
    <div class="video-uploader">
        <!-- File selection (idle) -->
        <div v-if="!uploading && !error && !hasResumableUpload && progress < 100" class="space-y-4">
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
                        {{ formatSize(file.size) }}
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

        <!-- Resumable upload (upload_status=1, not actively sending) -->
        <div v-if="hasResumableUpload" class="space-y-4">
            <div class="flex justify-between items-baseline">
                <span class="text-gray-400">Upload interrompu</span>
                <span class="text-gray-400 text-sm">
                    {{ formatSize(uploadProgress.uploadedSize) }} / {{ formatSize(uploadProgress.fileSize) }}
                </span>
            </div>
            <div class="relative h-4 bg-dark-border rounded-full overflow-hidden">
                <div
                    class="absolute inset-y-0 left-0 bg-myclap-red"
                    :style="{ width: uploadProgress.percentage + '%' }"
                ></div>
            </div>
            <div class="text-center text-xl font-bold">{{ Math.round(uploadProgress.percentage) }}%</div>
            <p class="text-sm text-gray-400 text-center">{{ uploadProgress.fileName }}</p>
            <div v-if="error" class="bg-red-900/20 border border-red-500 rounded-lg p-4 text-red-400">
                {{ error }}
            </div>
            <div class="flex gap-3">
                <button
                    @click="triggerResumeFileSelect"
                    class="flex-1 py-3 bg-myclap-red hover:bg-[#cc0402] text-white font-medium rounded-lg transition-colors"
                >
                    Reprendre l'upload
                </button>
                <button
                    @click="cancelUpload"
                    class="flex-1 py-3 bg-[#3a3a3a] hover:bg-[#4a4a4a] text-white font-medium rounded-lg transition-colors"
                >
                    Annuler l'upload
                </button>
            </div>
            <input
                ref="resumeFileInput"
                type="file"
                accept="video/*"
                @change="handleResumeFileSelect"
                class="hidden"
            />
        </div>

        <!-- Upload progress (actively sending) -->
        <div v-if="uploading" class="space-y-4">
            <div class="flex justify-between items-baseline">
                <span class="text-gray-400">{{ status }}</span>
                <span class="text-gray-400 text-sm">
                    {{ formatSize(uploadedSize) }} / {{ formatSize(totalSize) }}
                </span>
            </div>
            <div class="relative h-4 bg-dark-border rounded-full overflow-hidden">
                <div
                    class="absolute inset-y-0 left-0 bg-myclap-red transition-all duration-300"
                    :style="{ width: progress + '%' }"
                ></div>
            </div>
            <div class="text-center text-xl font-bold">{{ Math.round(progress) }}%</div>
            <button
                @click="cancelUpload"
                class="w-full py-3 bg-[#3a3a3a] hover:bg-[#4a4a4a] text-white font-medium rounded-lg transition-colors"
            >
                Annuler l'upload
            </button>
        </div>

        <!-- Error state (no resumable upload) -->
        <div v-if="error && !hasResumableUpload" class="space-y-4">
            <div class="bg-red-900/20 border border-red-500 rounded-lg p-4 text-red-400">
                {{ error }}
            </div>
            <button
                @click="cancelUpload"
                class="w-full py-3 bg-[#3a3a3a] hover:bg-[#4a4a4a] text-white font-medium rounded-lg transition-colors"
            >
                Annuler l'upload
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
