<script setup>
import {Head, router, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

const form = useForm({
    label: '',
    description: '',
})

function submit() {
    form.post('/manager/categories')
}
</script>

<template>
    <Head title="Créer une catégorie"/>
    <ManagerLayout leftbar-active="categories">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-6">Nouvelle catégorie</h1>

            <form @submit.prevent="submit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Nom de la catégorie *</label>
                    <input
                        v-model="form.label"
                        type="text"
                        required
                        maxlength="75"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    />
                    <div v-if="form.errors.label" class="text-red-400 text-sm mt-1">{{ form.errors.label }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="500"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    ></textarea>
                    <div v-if="form.errors.description" class="text-red-400 text-sm mt-1">{{
                            form.errors.description
                        }}
                    </div>
                </div>

                <div class="flex gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Création...' : 'Créer' }}
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/manager/categories')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </ManagerLayout>
</template>
