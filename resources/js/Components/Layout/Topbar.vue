<script setup>
import {computed, ref} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import NavIcon from './NavIcon.vue'
import TopbarSearch from './TopbarSearch.vue'
import {usePermissions} from '@/composables/usePermissions'

const props = defineProps({
    section: {
        type: String,
        required: true
    }
})

const page = usePage()
const {allows} = usePermissions()

const sections = {
    app: {
        home: '/',
        logo: '/static/myclap/myclap.png',
        alt: 'myCLAP',
        links: [
            {href: '/manager/videos/ajouter', icon: 'plus', title: 'Ajouter une vidéo', permission: 'manager.video.upload'},
            {href: '/manager', icon: 'gear', title: 'myCLAP Manager', group: 'manager'},
        ],
    },
    manager: {
        home: '/manager',
        logo: '/static/myclap/myclap-manager.png',
        alt: 'myCLAP Manager',
        links: [
            {href: '/admin', icon: 'gear', title: 'Administration', permission: 'admin'},
            {href: '/', icon: 'globe', title: 'Retour myCLAP'},
        ],
    },
    admin: {
        home: '/admin',
        logo: '/static/myclap/myclap-admin.png',
        alt: 'myCLAP Admin',
        links: [
            {href: '/manager', icon: 'video', title: 'Retour au Manager'},
            {href: '/', icon: 'globe', title: 'Retour myCLAP'},
        ],
    },
}

const current = computed(() => sections[props.section])
const links = computed(() => current.value.links.filter(allows))
const mobileSearchOpen = ref(false)
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 h-14 bg-dark-surface border-b border-dark-border z-50 flex items-center px-4">
        <!-- Logo -->
        <div class="flex items-center gap-4">
            <Link :href="current.home" class="flex items-center gap-2">
                <img :src="current.logo" :alt="current.alt" class="h-10"/>
            </Link>
        </div>

        <TopbarSearch v-if="section === 'app'" v-model:mobile-open="mobileSearchOpen"/>

        <!-- Right buttons -->
        <div class="flex items-center gap-2 ml-auto">
            <button
                v-if="section === 'app'"
                @click="mobileSearchOpen = !mobileSearchOpen"
                class="md:hidden p-2 hover:bg-[#3a3a3a] rounded-full"
                aria-label="Rechercher"
            >
                <NavIcon name="search" class="w-5 h-5"/>
            </button>

            <template v-if="page.props.auth.user">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="p-2 hover:bg-[#3a3a3a] rounded-full"
                    :title="link.title"
                    :aria-label="link.title"
                >
                    <NavIcon :name="link.icon" class="w-5 h-5"/>
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    aria-label="Se déconnecter"
                    class="p-2 hover:bg-[#3a3a3a] rounded-full"
                    title="Se déconnecter"
                >
                    <NavIcon name="logout" class="w-5 h-5"/>
                </Link>
            </template>

            <a
                v-else
                href="/login"
                class="px-4 py-2 bg-myclap-red hover:bg-[#cc0402] rounded-full text-white font-medium text-sm"
            >
                Se connecter
            </a>
        </div>
    </header>
</template>
