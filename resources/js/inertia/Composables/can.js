import { usePage } from '@inertiajs/vue3';

export const useCan = permission => {
  const permissions = usePage().props.auth.permissions;
  return permissions.includes(permission);
};

export const useHasRole = role => {
  const roles = usePage().props.auth.roles;
  return roles.includes(role);
};

export const useHasAnyRole = roles => {
  const all = usePage().props.auth.roles;

  let hasRole = false;

  if (roles.length > 0) {
    roles.forEach(role => {
      if (all.includes(role)) {
        hasRole = true;
      }
    });
  }

  return hasRole;
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

export const useHasAnyTeam = teams => {
  const allTeams = usePage().props.auth?.teams || [];

  if (!Array.isArray(teams) || teams.length === 0) {
    return false;
  }

  const teamNames = allTeams.map(t => t?.name).filter(Boolean);

  return teams.some(team => {
    return (
      team &&
      typeof team === 'object' &&
      typeof team.name === 'string' &&
      teamNames.includes(team.name)
    );
  });
};
