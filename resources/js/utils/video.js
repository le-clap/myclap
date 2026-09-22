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

export function getDisplayStatus(video) {
    if (video.upload_status === 2) {
        return {label: 'Non uploadée', class: 'bg-gray-500/20 text-gray-400', needsAttention: false}
    }
    if (video.upload_status === 1) {
        return {label: 'Upload en cours', class: 'bg-yellow-500/20 text-yellow-400', needsAttention: false}
    }
    if (video.upload_status === 3) {
        return {label: 'Traitement en cours', class: 'bg-blue-500/20 text-blue-400', needsAttention: false}
    }

    // upload_status === 0 (UPLOAD_END, published)

    const attemptStatus = video.latest_transcode_attempt?.status

    if (attemptStatus === 5) {
        return {label: 'À vérifier', class: 'bg-red-500/20 text-red-400', needsAttention: true}
    }
    if (attemptStatus === 1 || attemptStatus === 2) {
        return {label: 'Traitement en cours', class: 'bg-blue-500/20 text-blue-400', needsAttention: false}
    }

    return {label: 'Publiée', class: 'bg-green-500/20 text-green-400', needsAttention: false}
}
