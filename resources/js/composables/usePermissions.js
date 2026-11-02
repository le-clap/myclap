import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'

/** Client-side mirror of User::hasPermission() and hasPermissionGroup(), for showing or hiding UI only. */
export function usePermissions() {
    const page = usePage()
    const permissions = computed(() => page.props.auth.permissions || [])
    const isAdmin = computed(() => permissions.value.includes('admin'))

    const can = (permission) => isAdmin.value || permissions.value.includes(permission)
    const canGroup = (group) => isAdmin.value || permissions.value.some(p => p.startsWith(`${group}.`))

    /** For nav items: `{permission}` and `{group}` are optional requirements. */
    const allows = ({permission, group}) => (!permission || can(permission)) && (!group || canGroup(group))

    return {can, canGroup, allows}
}
