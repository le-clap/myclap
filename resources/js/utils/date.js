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

export function formatDateShort(dateString) {
    if (!dateString) return '-';

    const date = new Date(dateString);

    // Format: "26/03/2025"
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

export function formatRelativeTime(dateString) {
    if (!dateString) return '';

    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };

    if (diffInSeconds < 60) {
        return "Il y a quelques secondes";
    }

    for (const [unit, seconds] of Object.entries(intervals)) {
        const interval = Math.floor(diffInSeconds / seconds);

        if (interval >= 1) {
            if (unit === 'year') {
                return interval === 1 ? 'Il y a 1 an' : `Il y a ${interval} ans`;
            } else if (unit === 'month') {
                return interval === 1 ? 'Il y a 1 mois' : `Il y a ${interval} mois`;
            } else if (unit === 'week') {
                return interval === 1 ? 'Il y a 1 semaine' : `Il y a ${interval} semaines`;
            } else if (unit === 'day') {
                return interval === 1 ? 'Il y a 1 jour' : `Il y a ${interval} jours`;
            } else if (unit === 'hour') {
                return interval === 1 ? 'Il y a 1 heure' : `Il y a ${interval} heures`;
            } else if (unit === 'minute') {
                return interval === 1 ? 'Il y a 1 minute' : `Il y a ${interval} minutes`;
            }
        }
    }

    return '';
}
