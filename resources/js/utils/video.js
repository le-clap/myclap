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
