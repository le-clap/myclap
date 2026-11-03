// Timestamps like 1:23 or 01:02:03, not glued to other digits/colons.
const TIMESTAMP = /(?<![\d:])(?:(\d{1,2}):)?(\d{1,2}):(\d{2})(?![\d:])/g
// A chapter line: "0:00 Intro", "- 1:23 - Title", "[01:02:03] Title".
const CHAPTER_LINE = /^[\s\-•*[(]*((?:\d{1,2}:)?\d{1,2}:\d{2})[\s\])\-–—:.]*(\S.*)$/

function toSeconds(stamp) {
    return stamp.split(':').reduce((total, part) => total * 60 + Number(part), 0)
}

// Chapters = description lines starting with a timestamp; need at least 2, in order.
export function parseChapters(description) {
    const chapters = (description || '').split('\n')
        .map(line => CHAPTER_LINE.exec(line.trim()))
        .filter(Boolean)
        .map(([, stamp, title]) => ({time: toSeconds(stamp), title: title.trim()}))

    const ordered = chapters.every((c, i) => i === 0 || c.time > chapters[i - 1].time)
    return chapters.length >= 2 && ordered ? chapters : []
}

// Split text into plain strings and clickable {time, label} parts.
export function splitTimestamps(text) {
    const parts = []
    let last = 0
    for (const match of text.matchAll(TIMESTAMP)) {
        if (match.index > last) parts.push(text.slice(last, match.index))
        parts.push({time: toSeconds(match[0]), label: match[0]})
        last = match.index + match[0].length
    }
    if (last < text.length) parts.push(text.slice(last))
    return parts
}

function vttTime(seconds) {
    const iso = new Date(seconds * 1000).toISOString() // HH:MM:SS.mmm (videos < 24h)
    return iso.slice(11, 23)
}

export function chaptersToVtt(chapters, duration) {
    const cues = chapters
        .filter(c => c.time < duration)
        .map((c, i, all) => `${vttTime(c.time)} --> ${vttTime(all[i + 1]?.time ?? duration)}\n${c.title.replace(/-->/g, '→')}`)
    return `WEBVTT\n\n${cues.join('\n\n')}\n`
}
