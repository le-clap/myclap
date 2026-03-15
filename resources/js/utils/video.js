export function formatFileSize(bytes) {
    if (bytes === null || bytes === undefined || Number.isNaN(bytes)) return '-'

    if (bytes >= 1024 * 1024 * 1024) {
        return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} Gio`
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} Mio`
}

export function computeBitrate(fileSizeBytes, durationSeconds) {
    if (
        !Number.isFinite(fileSizeBytes)
        || !Number.isFinite(durationSeconds)
        || fileSizeBytes <= 0
        || durationSeconds <= 0
    ) {
        return null
    }

    // Standard bitrate is expressed in bits per second.
    return (fileSizeBytes * 8) / durationSeconds
}

export function formatBitrate(bitsPerSecond) {
    if (bitsPerSecond === null || bitsPerSecond === undefined || Number.isNaN(bitsPerSecond)) return '-'

    if (bitsPerSecond >= 1_000_000) {
        return `${(bitsPerSecond / 1_000_000).toFixed(2)} Mbit/s`
    }

    return `${(bitsPerSecond / 1_000).toFixed(2)} kbit/s`
}
