<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import ManagerTopbar from './ManagerTopbar.vue'
import ManagerLeftbar from './ManagerLeftbar.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)
const permissions = computed(() => page.props.auth.permissions || [])

defineProps({
    leftbarActive: {
        type: String,
        default: 'dashboard'
    }
})

function hasPermission(perm) {
    return permissions.value.includes(perm) || permissions.value.includes('admin')
}

function hasPermissionGroup(group) {
    return permissions.value.some(p => p.startsWith(group + '.')) || permissions.value.includes('admin')
}
</script>

<template>
    <div class="min-h-screen bg-dark-bg text-white">
        <ManagerTopbar
            :has-permission="hasPermission"
        />

        <div class="flex pt-14">
            <ManagerLeftbar
                :active="leftbarActive"
                :has-permission="hasPermission"
                :has-permission-group="hasPermissionGroup"
            />

            <main class="flex-1 ml-16 lg:ml-56 p-4 lg:p-6">
                <div class="mx-auto" style="max-width: 95%;">
                    <slot/>
                </div>
            </main>
        </div>
    </div>
</template>
