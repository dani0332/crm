import { usePage } from '@inertiajs/vue3';

export const useCan = permissions => {
  const all = usePage().props.auth.permissions;
  const enums = usePage().props.permissionsEnum;

  let hasPermission = false;

  if (permissions.length > 0) {
    permissions.forEach(permission => {
      if (all.includes(enums[permission])) {
        hasPermission = true;
      }
    });
  }

  return hasPermission;
};
