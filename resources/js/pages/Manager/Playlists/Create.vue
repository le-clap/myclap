<script setup>
import {Head, router, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

const props = defineProps({
    typeOptions: {
        type: Array,
        default: () => []
    },
    accessOptions: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    name: '',
    description: '',
    type: 0,
    access: 3,
    videos: [],
})

function submit() {
    form.post('/manager/playlists')
}
</script>

<template>
    <Head title="Créer une playlist"/>
    <ManagerLayout leftbar-active="playlists">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-6">Créer une playlist</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium mb-2">Nom de la playlist *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="75"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    />
                    <div v-if="form.errors.name" class="text-red-400 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="1000"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    ></textarea>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium mb-2">Type *</label>
                    <select
                        v-model="form.type"
                        required
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    >
                        <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Access -->
                <div>
                    <label class="block text-sm font-medium mb-2">Contrôle d'accès *</label>
                    <select
                        v-model="form.access"
                        required
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    >
                        <option v-for="opt in accessOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Création...' : 'Créer la playlist' }}
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/manager/playlists')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </ManagerLayout>
</template>
