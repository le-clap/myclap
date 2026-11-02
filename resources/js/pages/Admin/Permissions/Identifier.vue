<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import {ref} from 'vue'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'

const props = defineProps({
    identifier: {
        type: String,
        required: true
    },
    permissions: {
        type: Array,
        default: () => []
    }
})

const newUsername = ref('')

const grantForm = useForm({
    username: '',
    identifier: props.identifier
})

function grantPermission() {
    if (!newUsername.value.trim()) return
    grantForm.username = newUsername.value.trim()
    grantForm.post('/admin/permissions/grant', {
        preserveScroll: true,
        onSuccess: () => {
            newUsername.value = ''
        }
    })
}

function revokePermission(username) {
    if (!confirm(`Êtes-vous sûr de vouloir révoquer cette permission pour ${username} ?`)) return

    useForm({
        username: username,
        identifier: props.identifier
    }).post('/admin/permissions/revoke', {
        preserveScroll: true
    })
}
</script>

<template>
    <Head :title="`Admin - Permission ${identifier}`"/>
    <AdminLayout leftbar-active="permissions">
        <div class="w-full">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/admin/permissions" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div>
                    <h1 class="text-3xl font-bebas tracking-wide">Permission</h1>
                    <p class="text-gray-400 font-mono">{{ identifier }}</p>
                </div>
            </div>

            <!-- Ajouter un utilisateur -->
            <div class="bg-dark-surface rounded-lg p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Accorder cette permission</h2>
                <div class="flex gap-2">
                    <input
                        v-model="newUsername"
                        type="text"
                        placeholder="Username"
                        class="flex-1 bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                        @keyup.enter="grantPermission"
                    />
                    <button
                        @click="grantPermission"
                        :disabled="!newUsername.trim() || grantForm.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ grantForm.processing ? 'Ajout...' : 'Ajouter' }}
                    </button>
                </div>
                <div v-if="grantForm.errors.username" class="text-red-400 text-sm mt-2">
                    {{ grantForm.errors.username }}
                </div>
            </div>

            <!-- Liste des utilisateurs avec cette permission -->
            <div class="bg-dark-surface rounded-lg p-6">
                <h2 class="text-lg font-bold mb-4">
                    Utilisateurs avec cette permission
                    <span class="text-gray-400 font-normal">({{ permissions.length }})</span>
                </h2>

                <div v-if="permissions.length > 0" class="divide-y divide-dark-border">
                    <div
                        v-for="perm in permissions"
                        :key="perm.id"
                        class="flex items-center justify-between py-3"
                    >
                        <Link
                            :href="`/admin/permissions/user/${perm.username}`"
                            class="font-mono text-blue-400 hover:text-blue-300"
                        >
                            {{ perm.username }}
                        </Link>
                        <button
                            @click="revokePermission(perm.username)"
                            class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-sm transition-colors"
                        >
                            Révoquer
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-gray-400">
                    Aucun utilisateur n'a cette permission
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
