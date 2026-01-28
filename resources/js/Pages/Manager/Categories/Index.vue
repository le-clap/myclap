<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'

defineProps({
    categories: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head title="Catégories"/>
    <ManagerLayout leftbar-active="categories">
        <div class="w-full">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bebas tracking-wide">Catégories</h1>
                <Link
                    href="/manager/categories/ajouter"
                    class="flex items-center gap-2 px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter
                </Link>
            </div>

            <div v-if="categories.length > 0" class="bg-dark-surface rounded-lg divide-y divide-dark-border">
                <Link
                    v-for="category in categories"
                    :key="category.slug"
                    :href="`/manager/categories/s/${category.slug}`"
                    class="flex items-center gap-4 p-4 hover:bg-[#222] transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <div class="font-medium">{{ category.label }}</div>
                        <div class="text-sm text-gray-400 mt-1 line-clamp-1">
                            {{ category.description }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            Créée le {{ new Date(category.created_on).toLocaleDateString('fr-FR') }} par
                            {{ category.created_by }}
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="text-center py-12 text-gray-400 bg-dark-surface rounded-lg">
                <p>Aucune catégorie</p>
            </div>
        </div>
    </ManagerLayout>
</template>
