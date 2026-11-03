<script setup>
import Topbar from './Topbar.vue'
import Leftbar from './Leftbar.vue'

defineProps({
    // Which part of the site this page belongs to: 'app', 'manager' or 'admin'
    section: {
        type: String,
        default: 'app'
    },
    leftbarActive: {
        type: String,
        default: 'accueil'
    }
})
</script>

<template>
    <div class="min-h-screen bg-dark-bg text-white">
        <Topbar :section="section"/>

        <div class="flex pt-14">
            <Leftbar :section="section" :active="leftbarActive"/>

            <main class="flex-1 ml-16 lg:ml-56 p-4 lg:p-6">
                <div :class="{ 'mx-auto max-w-[95%]': section !== 'app' }">
                    <slot/>
                </div>
            </main>
        </div>
    </div>
</template>
