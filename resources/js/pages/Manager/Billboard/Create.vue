<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

const props = defineProps({
    colorOptions: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    title: '',
    button: '',
    url: '',
    icon: '',
    color: 'gradient-dark-red',
})

function submit() {
    form.post('/manager/presentation')
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
    <Head title="Ajouter une annonce"/>
    <ManagerLayout leftbar-active="design">
        <div class="w-full">
            <div class="flex items-center gap-4 mb-6">
                <Link href="/manager/presentation" class="text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-2xl font-bold">Ajouter une annonce</h1>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Formulaire -->
                <div class="bg-dark-surface rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Titre de l'encart *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                maxlength="60"
                                class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                            <div v-if="form.errors.title" class="text-red-400 text-sm mt-1">{{
                                    form.errors.title
                                }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Texte du bouton *</label>
                            <input
                                v-model="form.button"
                                type="text"
                                required
                                maxlength="30"
                                class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                            <div v-if="form.errors.button" class="text-red-400 text-sm mt-1">{{
                                    form.errors.button
                                }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Lien de l'encart *</label>
                            <input
                                v-model="form.url"
                                type="url"
                                required
                                maxlength="255"
                                placeholder="https://my.le-clap.fr/..."
                                class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                            <div v-if="form.errors.url" class="text-red-400 text-sm mt-1">{{ form.errors.url }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Icône (optionnel)</label>
                            <input
                                v-model="form.icon"
                                type="text"
                                maxlength="30"
                                placeholder="fa-bolt-lightning"
                                class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            />
                            <p class="text-sm text-gray-500 mt-1">
                                Nom de l'icône FontAwesome (ex: fa-video, fa-star). Voir
                                <a href="https://fontawesome.com/icons" target="_blank"
                                   class="text-myclap-red hover:underline">fontawesome.com</a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Couleur du fond *</label>
                            <select
                                v-model="form.color"
                                required
                                class="w-full bg-dark-border border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                            >
                                <option v-for="opt in colorOptions" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                            >
                                {{ form.processing ? 'Création...' : 'Créer l\'annonce' }}
                            </button>
                            <Link
                                href="/manager/presentation"
                                class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                            >
                                Annuler
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Aperçu -->
                <div>
                    <h3 class="text-sm font-medium text-gray-400 mb-3">Aperçu</h3>
                    <div
                        class="rounded-lg p-8 text-center"
                        :style="{ background: getGradientStyle(form.color) }"
                    >
                        <h2 class="font-bebas-xl mb-4 text-white">{{ form.title || 'Titre de l\'annonce' }}</h2>
                        <span
                            class="inline-flex items-center gap-2 px-6 py-2 bg-white/90 text-gray-900 rounded-lg font-medium">
                            <i v-if="form.icon" :class="['fas', form.icon]"></i>
                            {{ form.button || 'Bouton' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </ManagerLayout>
</template>
