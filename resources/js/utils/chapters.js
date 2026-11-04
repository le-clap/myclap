// 1:23 or 01:02:03, with minutes and seconds under 60.
const STAMP = String.raw`(?:\d{1,2}:)?[0-5]?\d:[0-5]\d`
// A timestamp in running text, not glued to other digits/colons.
const TIMESTAMP = new RegExp(String.raw`(?<![\d:])(${STAMP})(?![\d:])`)
// A chapter line: "0:00 Intro", "- 1:23 - Title", "[01:02:03] Title".
const CHAPTER_LINE = new RegExp(String.raw`^[\s\-•*[(]*(${STAMP})(?!:\d)[\s\])\-–—:.]+(\S.*)$`)

function toSeconds(stamp) {
    return stamp.split(':').reduce((total, part) => total * 60 + Number(part), 0)
}

// Chapters = description lines starting with a timestamp; need at least 2, in order.
export function parseChapters(description) {
    const chapters = (description || '').split('\n')
        .map(line => CHAPTER_LINE.exec(line.trim()))
        .filter(Boolean)
        .map(([, stamp, title]) => ({time: toSeconds(stamp), title}))

    const ordered = chapters.every((c, i) => i === 0 || c.time > chapters[i - 1].time)
    return chapters.length >= 2 && ordered ? chapters : []
}

// Split text into plain strings and clickable {time, label} parts.
export function splitTimestamps(text) {
    return text.split(TIMESTAMP).map((part, i) => i % 2 ? {time: toSeconds(part), label: part} : part)
}

function vttTime(seconds) {
    return new Date(seconds * 1000).toISOString().slice(11, 23) // HH:MM:SS.mmm (videos < 24h)
}

// The last cue is open-ended (23:59:59): the player clamps cues to the media duration.
export function chaptersToVtt(chapters) {
    const cues = chapters
        .map((c, i, all) => `${vttTime(c.time)} --> ${vttTime(all[i + 1]?.time ?? 86399)}\n${c.title.replace(/-->/g, '→')}`)
    return `WEBVTT\n\n${cues.join('\n\n')}\n`
}
