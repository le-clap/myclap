<script setup>
import {Head, Link, router, useForm} from '@inertiajs/vue3'
import ManagerLayout from '@/Components/Layout/ManagerLayout.vue'
import DatePicker from '@/Components/DatePicker.vue'
import {formatDuration} from '@/utils/date'

const props = defineProps({
    video: {
        type: Object,
        required: true
    },
    videoCategorySlugs: {
        type: Array,
        default: () => []
    },
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
    name: props.video.name || '',
    description: props.video.description || '',
    created_on: props.video.created_on ? props.video.created_on.split('T')[0] : '',
    categories: [...props.videoCategorySlugs],
    access: props.video.access ?? 3,
    thumbnail: null,
})

function submit() {
    // With forceFormData, Laravel's form.put() doesn't work, so we need to use method spoofing.
    form.transform(data => ({ ...data, _method: 'PUT' }))
        .post(`/manager/videos/v/${props.video.token}`, {
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

function deleteVideo() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette vidéo ? Cette action est irréversible.')) {
        router.delete(`/manager/videos/v/${props.video.token}`)
    }
}
</script>

<template>
    <Head :title="`Modifier '${video.name}'`"/>
    <ManagerLayout leftbar-active="videos">
        <div class="w-full">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold">Modifier la vidéo</h1>
                <div class="flex gap-2">
                    <Link
                        v-if="video.upload_status !== 0"
                        :href="`/manager/videos/v/${video.token}/envoyer`"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                    >
                        Envoyer la vidéo
                    </Link>
                    <Link
                        v-if="video.upload_status === 0"
                        :href="`/regarder/${video.token}`"
                        class="px-4 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Voir
                    </Link>
                </div>
            </div>

            <!-- Status -->
            <div class="bg-dark-surface rounded-lg p-4 mb-6">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-400">Token:</span>
                    <code class="bg-dark-border px-2 py-1 rounded">{{ video.token }}</code>
                    <span v-if="video.duration" class="text-sm text-gray-400">Durée:</span>
                    <code v-if="video.duration" class="bg-dark-border px-2 py-1 rounded">
                        {{ formatDuration(video.duration) }}
                    </code>
                    <span
                        :class="[
                            'px-2 py-1 rounded text-xs',
                            video.upload_status === 0
                                ? 'bg-green-500/20 text-green-400'
                                : 'bg-yellow-500/20 text-yellow-400'
                        ]"
                    >
                        {{ video.upload_status === 0 ? 'Publié' : 'En attente d\'upload' }}
                    </span>
                </div>
            </div>

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
                </div>

                <!-- Created on -->
                <div>
                    <label class="block text-sm font-medium mb-2">Date de création *</label>
                    <DatePicker
                        v-model="form.created_on"
                        :required="true"
                    />
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

                <div v-if="video.thumbnail_identifier">
                    <label class="block text-sm font-medium mb-2">Miniature actuelle</label>
                    <img :src="video.thumbnail_urls?.['240'] || video.thumbnail_url" alt="Miniature"
                         class="w-48 h-27 object-cover rounded"/>
                </div>

                <!-- Thumbnail -->
                <div>
                    <label class="block text-sm font-medium mb-2">Nouvelle miniature</label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png"
                        @change="form.thumbnail = $event.target.files[0]"
                        class="w-full bg-dark-surface border border-[#3a3a3a] rounded-lg px-4 py-2 text-white"
                    />
                    <p class="text-sm text-gray-500 mt-1">Laisser vide pour conserver la miniature actuelle</p>
                </div>

                <!-- Submit -->
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
                        @click="router.visit('/manager/videos')"
                        class="px-6 py-2 bg-dark-border hover:bg-[#3a3a3a] rounded-lg transition-colors"
                    >
                        Retour
                    </button>
                </div>
            </form>

            <!-- Danger zone -->
            <div class="mt-12 pt-6 border-t border-dark-border">
                <h2 class="text-lg font-bold text-red-500 mb-4">Zone de danger</h2>
                <button
                    @click="deleteVideo"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
                >
                    Supprimer la vidéo
                </button>
            </div>
        </div>
    </ManagerLayout>
</template>
