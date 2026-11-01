<script setup>
import {Head, Link, router} from '@inertiajs/vue3'
import {computed, ref, watch} from 'vue'
import axios from 'axios'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'

const props = defineProps({
    permissions: {
        type: Array,
        default: () => []
    }
})

const searchQuery = ref('')
const selectedTab = ref('permissions')
const userSearchQuery = ref('')
const userSearchResults = ref([])
const isSearching = ref(false)
const searchError = ref('')

const filteredPermissions = computed(() => {
    if (!searchQuery.value) return props.permissions
    return props.permissions.filter(p =>
        p.identifier.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        p.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
})

let searchTimeout = null

watch(userSearchQuery, (newVal) => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchError.value = ''

    if (!newVal || newVal.length < 2) {
        userSearchResults.value = []
        return
    }

    searchTimeout = setTimeout(async () => {
        isSearching.value = true
        try {
            const {data} = await axios.get('/admin/permissions/api/search', {
                params: {q: newVal, limit: 10}
            })
            userSearchResults.value = data.users
        } catch (error) {
            searchError.value = 'Erreur lors de la recherche'
            userSearchResults.value = []
        } finally {
            isSearching.value = false
        }
    }, 300)
})

function goToUser(username) {
    router.visit(`/admin/permissions/user/${username}`)
}
</script>

<template>
    <Head title="Admin - Permissions"/>
    <AdminLayout leftbar-active="permissions">
        <div class="w-full">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">Gestion des permissions</h1>

            <!-- Tabs -->
            <div class="flex gap-2 mb-6">
                <button
                    @click="selectedTab = 'permissions'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors',
                        selectedTab === 'permissions'
                            ? 'bg-[#272727] text-white'
                            : 'bg-dark-surface text-gray-300 hover:bg-dark-border'
                    ]"
                >
                    Liste des permissions
                </button>
                <button
                    @click="selectedTab = 'users'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors',
                        selectedTab === 'users'
                            ? 'bg-[#272727] text-white'
                            : 'bg-dark-surface text-gray-300 hover:bg-dark-border'
                    ]"
                >
                    Recherche utilisateur
                </button>
            </div>

            <!-- Permissions Tab -->
            <div v-if="selectedTab === 'permissions'">
                <div class="mb-4">
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Rechercher une permission..."
                        class="w-full md:w-96 bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    />
                </div>

                <div class="bg-dark-surface rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-dark-border">
                        <tr>
                            <th class="text-left px-4 py-3">Identifiant</th>
                            <th class="text-left px-4 py-3">Description</th>
                            <th class="px-4 py-3 w-24"></th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-border">
                        <tr v-for="permission in filteredPermissions" :key="permission.identifier"
                            class="hover:bg-[#222]">
                            <td class="px-4 py-3 font-mono text-sm">{{ permission.identifier }}</td>
                            <td class="px-4 py-3 text-gray-300">{{ permission.description }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="`/admin/permissions/identifier/${encodeURIComponent(permission.identifier)}`"
                                    class="px-3 py-1 bg-dark-border hover:bg-[#3a3a3a] rounded transition-colors text-sm"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Users Tab -->
            <div v-if="selectedTab === 'users'">
                <div class="bg-dark-surface rounded-lg p-6">
                    <label class="block text-sm font-medium mb-2">Rechercher un utilisateur</label>
                    <div class="relative">
                        <input
                            v-model="userSearchQuery"
                            type="text"
                            placeholder="Username, nom, prénom..."
                            class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                        />
                        <div v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                            <div
                                class="w-5 h-5 border-2 border-myclap-red border-t-transparent rounded-full animate-spin"></div>
                        </div>
                    </div>

                    <div v-if="searchError" class="mt-2 text-red-400 text-sm">
                        {{ searchError }}
                    </div>

                    <div v-if="userSearchQuery.length >= 2 && !isSearching && userSearchResults.length === 0"
                         class="mt-4 text-gray-400">
                        Aucun utilisateur trouvé
                    </div>

                    <div v-if="userSearchResults.length > 0" class="mt-4 bg-dark-border rounded-lg overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-dark-surface">
                            <tr>
                                <th class="text-left px-4 py-2 text-sm">Username</th>
                                <th class="text-left px-4 py-2 text-sm">Nom</th>
                                <th class="text-left px-4 py-2 text-sm">Promo</th>
                                <th class="px-4 py-2 w-24"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-[#3a3a3a]">
                            <tr v-for="user in userSearchResults" :key="user.username" class="hover:bg-[#222]">
                                <td class="px-4 py-2 font-mono text-sm">{{ user.username }}</td>
                                <td class="px-4 py-2">{{ user.first_name }} {{ user.last_name }}</td>
                                <td class="px-4 py-2">{{ user.promo }}</td>
                                <td class="px-4 py-2 text-right">
                                    <button
                                        @click="goToUser(user.username)"
                                        class="px-3 py-1 bg-myclap-red hover:bg-[#cc0402] rounded transition-colors text-sm"
                                    >
                                        Voir
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-if="userSearchQuery.length < 2" class="mt-4 text-gray-500 text-sm">
                        Tapez au moins 2 caractères pour rechercher
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
