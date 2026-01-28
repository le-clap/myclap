<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import Topbar from './Topbar.vue'
import Leftbar from './Leftbar.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

defineProps({
    leftbarActive: {
        type: String,
        default: 'accueil'
    }
})
</script>

<template>
    <div class="min-h-screen bg-dark-bg text-white">
        <Topbar/>

        <div class="flex pt-14">
            <Leftbar :active="leftbarActive"/>

            <main class="flex-1 ml-16 lg:ml-56 p-4 lg:p-6">
                <slot/>
            </main>
        </div>
    </div>
</template>
