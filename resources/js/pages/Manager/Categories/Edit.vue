<script setup>
import {Head, router, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

const props = defineProps({
    category: {
        type: Object,
        required: true
    }
})

const form = useForm({
    label: props.category.label || '',
    description: props.category.description || '',
})

function submit() {
    form.put(`/manager/categories/s/${props.category.slug}`, {
        onSuccess: () => router.visit('/manager/categories')
    })
}

function deleteCategory() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?')) {
        router.delete(`/manager/categories/s/${props.category.slug}`)
    }
}
</script>

<template>
    <Head :title="`Modifier '${category.label}'`"/>
    <ManagerLayout leftbar-active="categories">
        <div class="w-full">
            <h1 class="text-2xl font-bold mb-6">Modifier la catégorie</h1>

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
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="500"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white focus:outline-none focus:border-myclap-red"
                    ></textarea>
                </div>

                <div class="flex gap-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'Sauvegarde...' : 'Sauvegarder' }}
                    </button>
                    <button
                        type="button"
                        @click="router.visit('/manager/categories')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Retour
                    </button>
                </div>
            </form>

            <div class="mt-12 pt-6 border-t border-dark-border">
                <button
                    @click="deleteCategory"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                >
                    Supprimer la catégorie
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
