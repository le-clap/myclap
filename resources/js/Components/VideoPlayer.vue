<script setup>
import {onMounted, onUnmounted, ref} from 'vue'
import '@videojs/html/video/player'
import '@videojs/html/video/neutral-skin'
import axios from 'axios'

const props = defineProps({
    video: {
        type: Object,
        required: true
    },
    viewSource: {
        type: String,
        default: null
    }
})

const playerElement = ref(null)
let playbackSid = null
let updateInterval = null
let trackingInitialized = false

function detectDeviceType() {
    const ua = navigator.userAgent.toLowerCase()

    if (/ipad/.test(ua)) return 'tablet'
    if (/mobile|iphone|ipod|android.*mobile/.test(ua)) return 'mobile'
    if (/android/.test(ua)) return 'tablet'
    if (window.innerWidth < 768) return 'mobile'

    return 'desktop'
}

function detectBrowser() {
    const ua = navigator.userAgent

    if (ua.includes('Firefox/')) return 'firefox'
    if (ua.includes('Edg/')) return 'edge'
    if (ua.includes('Chrome/') && !ua.includes('Chromium/')) return 'chrome'
    if (ua.includes('Safari/') && !ua.includes('Chrome/')) return 'safari'
    if (ua.includes('Opera/') || ua.includes('OPR/')) return 'opera'

    return null
}

function detectOS() {
    const ua = navigator.userAgent

    if (ua.includes('Windows')) return 'windows'
    if (ua.includes('Mac OS')) return 'macos'
    if (ua.includes('Linux') && !ua.includes('Android')) return 'linux'
    if (ua.includes('Android')) return 'android'
    if (ua.includes('iPhone') || ua.includes('iPad')) return 'ios'

    return null
}

function getWatchTime() {
    const media = playerElement.value
    if (!media) return null

    return {watch_time: media.currentTime || 0}
}

async function sendMetricsUpdate() {
    if (!playbackSid) return

    const metrics = getWatchTime()
    if (!metrics) return

    try {
        await axios.post(`/api/stats/${props.video.token}/update`, {
            playback_sid: playbackSid,
            ...metrics
        })
    } catch (error) {
        console.error('Stat update failed:', error)
    }
}

async function initTracking() {
    if (trackingInitialized) return
    trackingInitialized = true

    try {
        const {data} = await axios.post(`/api/stats/${props.video.token}/init`, {
            view_source: props.viewSource,
            device_type: detectDeviceType(),
            browser: detectBrowser(),
            os: detectOS()
        })
        playbackSid = data.playback_sid

        if (playbackSid) {
            updateInterval = setInterval(sendMetricsUpdate, 30_000)
        }
    } catch (error) {
        console.error('Stat init failed:', error)
    }
}

let reloadedAfterError = false
let pausedByViewer = true

// Reload the current file once and resume where the viewer was.
function reloadAfterFileSwap() {
    if (reloadedAfterError) return
    reloadedAfterError = true

    const media = playerElement.value
    const time = media.currentTime
    const wasPlaying = !pausedByViewer
    const url = new URL(props.video.video_url, window.location.origin)
    url.searchParams.delete('v')
    url.searchParams.set('r', Date.now())

    media.addEventListener('loadedmetadata', () => {
        media.currentTime = time
        if (wasPlaying) media.play().catch(() => {})
    }, {once: true})
    media.src = url.toString()
}

onMounted(() => {
    playerElement.value.addEventListener('error', reloadAfterFileSwap)

    playerElement.value.addEventListener('play', () => {
        pausedByViewer = false
        initTracking()
    })
    playerElement.value.addEventListener('pause', () => {
        pausedByViewer = true
    })
    playerElement.value.addEventListener('ended', sendMetricsUpdate)
})

onUnmounted(() => {
    if (updateInterval) {
        clearInterval(updateInterval)
    }
})
</script>

<template>
    <div class="video-player w-full">
        <video-player>
            <video-neutral-skin>
                <video ref="playerElement" :poster="video.thumbnail_url" playsinline>
                    <source :src="video.video_url" type="video/mp4" @error="reloadAfterFileSwap">
                    Votre navigateur ne supporte pas la lecture de vidéos.
                </video>
            </video-neutral-skin>
        </video-player>
    </div>
</template>

<style>
video-player {
    --media-accent-color: #ff0502;
    --media-border-radius: 0.5rem;
}

video-compat-skin {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 0.5rem;
    overflow: hidden;
}
</style>
