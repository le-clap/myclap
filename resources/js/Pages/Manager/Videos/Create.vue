<script setup>
import {Head, router, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'

const props = defineProps({
    categories: {
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
    created_on: new Date().toISOString().split('T')[0],
    categories: [],
    access: 3,
    thumbnail: null,
})

function submit() {
    form.post('/manager/videos', {
        forceFormData: true,
    })
}

function toggleCategory(slug) {
    const idx = form.categories.indexOf(slug)
    if (idx === -1) {
        form.categories.push(slug)
    } else {
        form.categories.splice(idx, 1)
    }
}
</script>

<template>
    <Head title="Ajouter une vidéo"/>
    <ManagerLayout leftbar-active="videos">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-6">Ajouter une vidéo</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium mb-2">Nom de la vidéo *</label>
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
                    <div v-if="form.errors.description" class="text-red-400 text-sm mt-1">{{
                            form.errors.description
                        }}
                    </div>
                </div>

                <!-- Created on -->
                <div>
                    <label class="block text-sm font-medium mb-2">Date de création *</label>
                    <DatePicker
                        v-model="form.created_on"
                        :required="true"
                    />
                    <p class="text-sm text-gray-500 mt-1">Cette date servira de référence pour le tri</p>
                </div>

                <!-- Categories -->
                <div>
                    <label class="block text-sm font-medium mb-2">Catégories</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="cat in categories"
                            :key="cat.slug"
                            type="button"
                            @click="toggleCategory(cat.slug)"
                            :class="[
                                'px-3 py-1 rounded-full text-sm transition-colors',
                                form.categories.includes(cat.slug)
                                    ? 'bg-myclap-red text-white'
                                    : 'bg-dark-border text-gray-300 hover:bg-[#3a3a3a]'
                            ]"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
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

                <!-- Thumbnail -->
                <div>
                    <label class="block text-sm font-medium mb-2">Miniature</label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png"
                        @change="form.thumbnail = $event.target.files[0]"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white"
                    />
                    <p class="text-sm text-gray-500 mt-1">PNG ou JPEG, 1920x1080 recommandé</p>
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Création...' : 'Créer la vidéo' }}
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/manager/videos')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </ManagerLayout>
</template>
