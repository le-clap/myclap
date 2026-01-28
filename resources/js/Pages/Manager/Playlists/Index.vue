<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

defineProps({
    playlists: {
        type: Array,
        default: () => []
    }
})

function getTypeLabel(type) {
    return type === 1 ? 'Diffusion' : 'Classique'
}

function getTypeClass(type) {
    return type === 1 ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400'
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

            <div v-if="playlists.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="playlist in playlists"
                    :key="playlist.slug"
                    :href="`/manager/playlists/s/${playlist.slug}`"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium flex items-center gap-2">
                            <svg v-if="playlist.pinned" class="w-4 h-4 text-myclap-red flex-shrink-0"
                                 fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M16 4a1 1 0 0 1 .117 1.993L16 6v4.379l1.707 1.707a1 1 0 0 1 .293.707V14a1 1 0 0 1-.883.993L17 15h-4v5a1 1 0 0 1-1.993.117L11 20v-5H7a1 1 0 0 1-.993-.883L6 14v-1.207a1 1 0 0 1 .293-.707L8 10.379V6a1 1 0 0 1-.117-1.993L8 4h8z"/>
                            </svg>
                            {{ playlist.name }}
                        </div>
                        <div class="text-sm text-gray-400 mt-1">
                            {{ playlist.videos_count }} vidéos • {{ playlist.access }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Modifiée le {{ new Date(playlist.modified_on).toLocaleDateString('fr-FR') }} par
                            {{ playlist.modified_by }}
                        </div>
                    </div>
                    <span :class="['px-2 py-1 rounded text-xs', getTypeClass(playlist.type)]">
                        {{ getTypeLabel(playlist.type) }}
                    </span>
                </Link>
            </div>

            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <p>Aucune playlist</p>
            </div>
        </div>
    </ManagerLayout>
</template>
