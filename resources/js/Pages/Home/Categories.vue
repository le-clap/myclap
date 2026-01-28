<script setup>
import {Head, Link} from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineProps({
    categories: {
        type: Array,
        default: () => []
    }
})
</script>

<template>
    <Head title="Catégories"/>
    <AppLayout leftbar-active="categories">
        <div class="mx-auto px-4" style="max-width: 95%;">
            <h1 class="text-3xl font-bebas tracking-wide mb-6">Catégories</h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="category in categories"
                    :key="category.slug"
                    :href="`/categories/${category.slug}`"
                    class="block p-6 bg-dark-surface rounded-lg hover:bg-[#222] transition-colors group"
                >
                    <h3 class="font-bold text-lg group-hover:text-myclap-red transition-colors">
                        {{ category.label }}
                    </h3>
                    <p v-if="category.description" class="text-sm text-gray-400 mt-2 line-clamp-2">
                        {{ category.description }}
                    </p>
                </Link>
            </div>

            <!-- Empty state -->
            <div v-if="categories.length === 0" class="text-center py-12 text-gray-400">
                <p>Aucune catégorie pour le moment</p>
            </div>
        </div>
    </AppLayout>
</template>
