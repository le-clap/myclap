export function formatDuration(seconds) {
    if (seconds === null || seconds === undefined) return '-';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}

// A day ("2026-10-12") is not an instant: read it as local midnight. new Date() alone
// reads it as UTC midnight, which shows the previous day west of UTC.
export function parseDay(day) {
    return new Date(day + 'T00:00');
}

export function formatDate(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);

    // Format: "26 mars 2025"
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

export function formatDateTime(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);

    // Format: "26 mars 2025 à 14:30"
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function formatRelativeDay(day) {
    if (!day) return '';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const days = Math.round((parseDay(day) - today) / 86400000); // round: DST days are 23h/25h
    const [size, unit] = [[365, 'year'], [30, 'month'], [7, 'week']].find(([n]) => Math.abs(days) >= n) ?? [1, 'day'];
    const text = new Intl.RelativeTimeFormat('fr', {numeric: unit === 'day' ? 'auto' : 'always'}).format(Math.trunc(days / size), unit);
    return text[0].toUpperCase() + text.slice(1);
}
