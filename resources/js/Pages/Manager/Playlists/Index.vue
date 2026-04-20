<script setup>
import {Head, Link} from '@inertiajs/vue3'
import {computed, ref, watch} from 'vue'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import axios from 'axios'

const props = defineProps({
    playlists: {
        type: Array,
        default: () => []
    }
})

const orderedPlaylists = ref([...props.playlists])

watch(
    () => props.playlists,
    (nextPlaylists) => {
        orderedPlaylists.value = [...nextPlaylists]
    }
)

function getTypeClass(type) {
    return type === 1 ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400'
}

const canMoveUp = computed(() =>
    orderedPlaylists.value.map((playlist, index) => index > 0 && orderedPlaylists.value[index - 1].type === playlist.type)
)

const canMoveDown = computed(() =>
    orderedPlaylists.value.map(
        (playlist, index) =>
            index < orderedPlaylists.value.length - 1 && orderedPlaylists.value[index + 1].type === playlist.type
    )
)

async function movePlaylist(index, direction) {
    const playlist = orderedPlaylists.value[index]
    const canMove = direction === 'up' ? canMoveUp.value[index] : canMoveDown.value[index]
    if (!playlist || !canMove) {
        return
    }

    const targetIndex = direction === 'up' ? index - 1 : index + 1
    const previousState = [...orderedPlaylists.value]

    const movedPlaylist = orderedPlaylists.value[index]
    orderedPlaylists.value.splice(index, 1)
    orderedPlaylists.value.splice(targetIndex, 0, movedPlaylist)

    try {
        await axios.post(`/manager/playlists/s/${playlist.slug}/move`, {direction})
    } catch (error) {
        console.error('Playlist reorder failed:', error)
        orderedPlaylists.value = previousState
    }
}
</script>

<template>
    <Head title="Playlists"/>
    <ManagerLayout leftbar-active="playlists">
        <div class="w-full">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Playlists</h1>
                <Link
                    href="/manager/playlists/ajouter"
                    class="flex items-center gap-2 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter
                </Link>
            </div>

            <div v-if="orderedPlaylists.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="(playlist, index) in orderedPlaylists"
                    :key="playlist.slug"
                    :href="`/manager/playlists/s/${playlist.slug}`"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                >
                    <div class="flex flex-col gap-1">
                        <button
                            type="button"
                            @click.stop.prevent="movePlaylist(index, 'up')"
                            :disabled="!canMoveUp[index]"
                            class="w-7 h-7 rounded border border-dark-border text-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-[#303030] transition-colors"
                        >
                            ↑
                        </button>
                        <button
                            type="button"
                            @click.stop.prevent="movePlaylist(index, 'down')"
                            :disabled="!canMoveDown[index]"
                            class="w-7 h-7 rounded border border-dark-border text-sm disabled:opacity-30 disabled:cursor-not-allowed hover:bg-[#303030] transition-colors"
                        >
                            ↓
                        </button>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium">
                            {{ playlist.name }}
                        </div>
                        <div class="text-sm text-gray-400 mt-1">
                            {{ playlist.videos_count }} vidéos • {{ playlist.access_label }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Modifiée le {{ new Date(playlist.modified_on).toLocaleDateString('fr-FR') }} par
                            {{ playlist.modified_by }}
                        </div>
                    </div>
                    <span :class="['px-2 py-1 rounded text-xs', getTypeClass(playlist.type)]">
                        {{ playlist.type_label }}
                    </span>
                </Link>
            </div>

            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <p>Aucune playlist</p>
            </div>
        </div>
    </ManagerLayout>
</template>
