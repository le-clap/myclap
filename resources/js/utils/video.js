export function formatFileSize(bytes) {
    if (bytes === null || bytes === undefined || Number.isNaN(bytes)) return '-'

    if (bytes >= 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} Gio`
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} Mio`
}

export function formatBitrate(bitsPerSecond) {
    if (bitsPerSecond === null || bitsPerSecond === undefined || Number.isNaN(bitsPerSecond)) return '-'

    if (bitsPerSecond >= 1_000_000) {
        return `${(bitsPerSecond / 1_000_000).toFixed(2)} Mbit/s`
    }

    return `${(bitsPerSecond / 1_000).toFixed(2)} kbit/s`
}

const GREEN = 'bg-green-500/20 text-green-400'
const YELLOW = 'bg-yellow-500/20 text-yellow-400'
const GRAY = 'bg-gray-500/20 text-gray-400'
const BLUE = 'bg-blue-500/20 text-blue-400'
const RED = 'bg-red-500/20 text-red-400'

export const TRANSCODE_STATUS_CLASS = {1: BLUE, 2: BLUE, 3: GREEN, 4: GREEN, 5: RED}

const UPLOAD_STATUS_CLASS = {0: GREEN, 1: YELLOW, 2: GRAY, 3: BLUE}

export function getDisplayStatus(video) {
    const attemptStatus = video.latest_transcode_attempt?.status

    if (video.upload_status === 0 && attemptStatus === 5) {
        return {label: 'Encodage échoué', class: RED, needsAttention: true}
    }
    if (video.upload_status === 0 && (attemptStatus === 1 || attemptStatus === 2)) {
        return {label: 'Traitement en cours', class: BLUE, needsAttention: false}
    }

    return {label: video.upload_status_label, class: UPLOAD_STATUS_CLASS[video.upload_status] ?? GRAY, needsAttention: false}
}
