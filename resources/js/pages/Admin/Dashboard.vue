<script setup>
import {Head, Link} from '@inertiajs/vue3'
import AdminLayout from '@/Components/Layout/AdminLayout.vue'
</script>

<template>
    <Head title="Admin - Dashboard"/>
    <AdminLayout leftbar-active="dashboard">
        <div class="w-full">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">Administration myCLAP</h1>

            <div class="bg-dark-surface rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Bienvenue dans l'espace d'administration</h2>
                <p class="text-gray-400 mb-4">
                    Cet espace vous permet de gérer les permissions des utilisateurs et d'accéder aux fonctionnalités
                    avancées de la plateforme.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link href="/admin/permissions"
                      class="bg-dark-surface hover:bg-[#222] rounded-lg p-6 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-myclap-red/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-myclap-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold">Permissions</h3>
                            <p class="text-sm text-gray-400">Gérer les droits utilisateurs</p>
                        </div>
                    </div>
                </Link>

                <Link href="/manager" class="bg-dark-surface hover:bg-[#222] rounded-lg p-6 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-600/20 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold">Manager</h3>
                            <p class="text-sm text-gray-400">Gestion des contenus</p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
