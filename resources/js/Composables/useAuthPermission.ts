import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export default function useAuthPermission() {
    const page = usePage();

    const authUser = computed(() => page.props.auth.user);

    const userRoles = computed<string[]>(() => {
        return authUser.value?.roles || [];
    });

    const userPermissions = computed<string[]>(() => {
        return authUser.value?.permissions || [];
    });

    const hasAnyRole = (rolesToCheck: string[]): boolean => {
        if (!userRoles.value) return false;
        return rolesToCheck.some(role => 
            userRoles.value.includes(role)
        );
    };

    const hasPermission = (permission: string): boolean => {
        if (!userPermissions.value) return false;
        return userPermissions.value.includes(permission);
    };

    return {
        userRoles,
        userPermissions,
        hasAnyRole,
        hasPermission
    };
}