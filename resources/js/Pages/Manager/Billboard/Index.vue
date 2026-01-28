<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

const props = defineProps({
    billboards: {
        type: Array,
        default: () => []
    }
})

function deleteBillboard(identifier) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')) {
        useForm({}).delete(`/manager/presentation/${identifier}`)
    }
}

function getGradientStyle(color) {
    const gradients = {
        'gradient-dark-red': 'linear-gradient(135deg, #8B0000 0%, #ff0502 100%)',
        'gradient-calm-darya': 'linear-gradient(135deg, #5f2c82 0%, #49a09d 100%)',
        'gradient-purple-dream': 'linear-gradient(135deg, #c471f5 0%, #fa71cd 100%)',
        'gradient-sexy-blue': 'linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%)',
        'gradient-emerald-water': 'linear-gradient(135deg, #348f50 0%, #56b4d3 100%)',
    }
    return gradients[color] || gradients['gradient-dark-red']
}
</script>

<template>
    <Head title="Présentation"/>
    <ManagerLayout leftbar-active="design">
        <div class="w-full">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bebas tracking-wide">Présentation</h1>
                    <p class="text-gray-400 text-sm mt-1">Gestion des annonces de la page d'accueil</p>
                </div>
                <Link
                    v-if="billboards.length < 5"
                    href="/manager/presentation/ajouter"
                    class="flex items-center gap-2 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter une annonce
                </Link>
            </div>

            <div class="bg-dark-surface rounded-lg p-6 mb-6">
                <p class="text-gray-300 mb-2">
                    Vous avez la possibilité d'afficher différentes annonces en haut de la page d'accueil pour mettre en
                    avant une vidéo, une playlist ou encore n'importe quel lien.
                </p>
                <p class="text-gray-400 text-sm">
                    Vous pouvez créer de 1 à 5 annonces qui s'afficheront sous la forme d'un diaporama. Si aucune
                    annonce n'a été créée, le bloc disparaît de la page d'accueil.
                </p>
            </div>

            <div v-if="billboards.length > 0" class="space-y-4">
                <div
                    v-for="(billboard, index) in billboards"
                    :key="billboard.identifier"
                    class="bg-dark-surface rounded-lg p-6"
                >
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold">Annonce #{{ index + 1 }}</h3>
                        <div class="flex gap-2">
                            <Link
                                :href="`/manager/presentation/${billboard.identifier}`"
                                class="px-3 py-1 bg-dark-border hover:bg-[#3a3a3a] rounded transition-colors text-sm"
                            >
                                Modifier
                            </Link>
                            <button
                                @click="deleteBillboard(billboard.identifier)"
                                class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded transition-colors text-sm"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>

                    <div
                        class="rounded-lg p-6 text-center"
                        :style="{ background: getGradientStyle(billboard.color) }"
                    >
                        <h2 class="font-bebas-xl mb-4 text-white">{{ billboard.title }}</h2>
                        <a
                            :href="billboard.url"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-6 py-2 bg-white/90 hover:bg-white text-gray-900 rounded-lg transition-colors font-medium"
                        >
                            <i v-if="billboard.icon" :class="['fas', billboard.icon]"></i>
                            {{ billboard.button }}
                        </a>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                <p>Aucune annonce créée</p>
            </div>
        </div>
    </ManagerLayout>
</template>
