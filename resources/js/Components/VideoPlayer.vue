<script setup>
import {onUnmounted, ref} from 'vue'
import '@videojs/html/video/player'
import '@videojs/html/video/neutral-skin'
import axios from 'axios'
import {chaptersToVtt, parseChapters} from '@/utils/chapters'

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

const chapters = parseChapters(props.video.description)
const chaptersUrl = chapters.length
    ? URL.createObjectURL(new Blob([chaptersToVtt(chapters)], {type: 'text/vtt'}))
    : null

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

async function sendMetricsUpdate() {
    if (!playbackSid) return

    try {
        await axios.post(`/api/stats/${props.video.token}/update`, {
            playback_sid: playbackSid,
            watch_time: playerElement.value.currentTime
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
        if (wasPlaying) media.play().catch(() => {
        })
    }, {once: true})
    media.src = url.toString()
}

function onPlay() {
    pausedByViewer = false
    initTracking()
}

function seekTo(seconds) {
    const media = playerElement.value
    // A time past the end is prose ("RDV à 12:30"), not a position in this video.
    if (seconds >= media.duration) return

    media.currentTime = seconds
    media.scrollIntoView({block: 'nearest'})
    media.play().catch(() => {
    })
}

defineExpose({seekTo})

onUnmounted(() => {
    clearInterval(updateInterval)
    URL.revokeObjectURL(chaptersUrl)
})
</script>

<template>
    <div class="video-player w-full">
        <video-player>
            <video-neutral-skin>
                <video
                    ref="playerElement"
                    :poster="video.thumbnail_url"
                    playsinline
                    @error="reloadAfterFileSwap"
                    @play="onPlay"
                    @pause="pausedByViewer = true"
                    @ended="sendMetricsUpdate"
                >
                    <source :src="video.video_url" type="video/mp4" @error="reloadAfterFileSwap">
                    <track v-if="chaptersUrl" kind="chapters" label="Chapitres" default :src="chaptersUrl">
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

video-neutral-skin {
    display: block;
    border-radius: 0.5rem;
    overflow: hidden;
}

video-neutral-skin video {
    aspect-ratio: auto;
    max-height: 80vh;
}
</style>
