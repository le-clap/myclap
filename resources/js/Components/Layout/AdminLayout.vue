<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AdminTopbar from './AdminTopbar.vue'
import AdminLeftbar from './AdminLeftbar.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

defineProps({
    leftbarActive: {
        type: String,
        default: 'dashboard'
    }
})
</script>

<template>
    <div class="min-h-screen bg-dark-bg text-white">
        <AdminTopbar/>

        <div class="flex pt-14">
            <AdminLeftbar :active="leftbarActive"/>

            <main class="flex-1 ml-16 lg:ml-56 p-4 lg:p-6">
                <div class="mx-auto" style="max-width: 95%;">
                    <slot/>
                </div>
            </main>
        </div>
    </div>
</template>
