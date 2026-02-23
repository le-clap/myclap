<script setup>
import {onMounted, onUnmounted, ref} from 'vue'
import Plyr from 'plyr'
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
let player = null
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
    if (!player) return null

    return {watch_time: player.currentTime || 0}
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

onMounted(() => {
    player = new Plyr(playerElement.value, {
        controls: [
            'play-large',
            'play',
            'progress',
            'current-time',
            'mute',
            'volume',
            'settings',
            'pip',
            'fullscreen'
        ],
        settings: ['quality', 'speed'],
    })

    player.on('play', () => {
        initTracking()
    })

    player.on('ended', () => {
        sendMetricsUpdate()
    })
})

onUnmounted(() => {
    if (updateInterval) {
        clearInterval(updateInterval)
    }

    if (player) {
        player.destroy()
    }
})
</script>

<template>
    <div class="video-player w-full">
        <video
            ref="playerElement"
            :poster="video.thumbnail_url"
            controls
            class="w-full rounded-lg"
        >
            <source :src="video.video_url" type="video/mp4">
            Votre navigateur ne supporte pas la lecture de vidéos.
        </video>
    </div>
</template>

<style>
@import 'plyr/dist/plyr.css';

.plyr--video {
    border-radius: 0.5rem;
    overflow: hidden;
}

.plyr--full-ui input[type=range] {
    color: #ff0502;
}

.plyr--video .plyr__control.plyr__tab-focus,
.plyr--video .plyr__control:hover,
.plyr--video .plyr__control[aria-expanded=true] {
    background: #ff0502;
}

.plyr__control--overlaid {
    background: rgba(255, 5, 2, 0.8);
}

.plyr__control--overlaid:hover,
.plyr__control--overlaid:focus {
    background: #ff0502;
}

.plyr--video .plyr__progress__buffer {
    color: rgba(255, 255, 255, 0.25);
}
</style>
