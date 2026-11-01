<script setup>
import {Head, Link, useForm, usePage} from '@inertiajs/vue3'
import {computed} from 'vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'

const props = defineProps({
    targetUser: {
        type: Object,
        required: true
    },
    permissions: {
        type: Array,
        default: () => []
    },
    allPermissions: {
        type: Array,
        default: () => []
    }
})

const page = usePage()

// Liste des permissions disponibles
const availablePermissions = [
    {identifier: 'manager.video', description: 'Accès à la gestion des vidéos'},
    {identifier: 'manager.video.upload', description: 'Téléversement de vidéos'},
    {identifier: 'manager.playlist', description: 'Gestion des playlists'},
    {identifier: 'manager.category', description: 'Gestion des catégories'},
    {identifier: 'manager.stat', description: 'Accès aux statistiques'},
    {identifier: 'manager.design', description: 'Gestion de la présentation'},
    {identifier: 'admin', description: 'Accès administrateur complet'},
]

const userPermissionIds = computed(() => props.permissions.map(p => p.identifier))

function hasPermission(identifier) {
    return userPermissionIds.value.includes(identifier)
}

function togglePermission(identifier) {
    if (hasPermission(identifier)) {
        revokePermission(identifier)
    } else {
        grantPermission(identifier)
    }
}

function grantPermission(identifier) {
    useForm({
        username: props.targetUser.username,
        identifier: identifier
    }).post('/admin/permissions/grant', {
        preserveScroll: true
    })
}

function revokePermission(identifier) {
    useForm({
        username: props.targetUser.username,
        identifier: identifier
    }).post('/admin/permissions/revoke', {
        preserveScroll: true
    })
}
</script>

<template>
    <Head :title="`Admin - Utilisateur ${targetUser.username}`"/>
    <AdminLayout leftbar-active="permissions">
        <div class="w-full">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/admin/permissions" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div>
                    <h1 class="text-3xl font-bebas tracking-wide">Utilisateur</h1>
                    <p class="text-gray-400 font-mono">{{ targetUser.username }}</p>
                </div>
            </div>

            <!-- Infos utilisateur -->
            <div class="bg-dark-surface rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Informations</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400">Username:</span>
                        <span class="ml-2 font-mono">{{ targetUser.username }}</span>
                    </div>
                    <div v-if="targetUser.email">
                        <span class="text-gray-400">Email:</span>
                        <span class="ml-2">{{ targetUser.email }}</span>
                    </div>
                    <div v-if="targetUser.is_admin">
                        <span class="px-2 py-1 bg-myclap-red/20 text-myclap-red rounded text-xs">Administrateur</span>
                    </div>
                </div>
            </div>

            <!-- Permissions de l'utilisateur -->
            <div class="bg-dark-surface rounded-lg p-6">
                <h2 class="text-lg font-bold mb-4">
                    Permissions
                    <span class="text-gray-400 font-normal">({{
                            permissions.length
                        }} active{{ permissions.length > 1 ? 's' : '' }})</span>
                </h2>

                <div class="space-y-2">
                    <div
                        v-for="perm in availablePermissions"
                        :key="perm.identifier"
                        class="flex items-center justify-between p-4 bg-dark-border rounded-lg"
                    >
                        <div>
                            <div class="font-mono text-sm">{{ perm.identifier }}</div>
                            <div class="text-sm text-gray-400">{{ perm.description }}</div>
                        </div>
                        <button
                            @click="togglePermission(perm.identifier)"
                            :class="[
                                'px-4 py-2 rounded-lg transition-colors text-sm font-medium',
                                hasPermission(perm.identifier)
                                    ? 'bg-green-600 hover:bg-green-700 text-white'
                                    : 'bg-[#3a3a3a] hover:bg-[#4a4a4a] text-gray-300'
                            ]"
                        >
                            {{ hasPermission(perm.identifier) ? '✓ Accordée' : 'Accorder' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
