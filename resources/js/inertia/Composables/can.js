import { usePage } from '@inertiajs/vue3';

export const useCan = permission => {
  const permissions = usePage().props.auth.permissions;
  return permissions.includes(permission);
};


export const useCanAny = permissions => {
    const all = usePage().props.auth.permissions;

    let hasPermission = false;

    if (permissions.length > 0) {
        permissions.forEach(permission => {
            if (all.includes(permission)) {
                hasPermission = true;
            }
        });
    }

    return hasPermission;
};
