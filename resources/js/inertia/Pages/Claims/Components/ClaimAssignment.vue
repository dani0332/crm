<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  expanded: {
    type: Boolean,
    default: true,
    required: false,
  },
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

const assignForm = useForm({
  manager_id: props.claim?.manager_id ?? '',
});

const managerOptions = computed(() => {
  return (
    props.dropdowns?.claimsManagers?.map(manager => ({
      value: manager.id,
      label: manager.name,
    })) ?? []
  );
});

const assignClaim = () => {
  assignForm.post(route('claims.assign', props.claim?.uuid), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Claim assigned successfully.',
        position: 'top',
      });
      router.visit(route('claims.show', props.claim?.uuid), {
        preserveScroll: true,
      });
    },
    onError: errors => {
      Object.keys(errors).forEach(key => {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};
</script>

<template>
  <div
    v-if="can(permissionsEnum.CLAIMS_MANUAL_ASSIGN)"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <Collapsible :expanded="expanded">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">
            Manual Allocation (Claims Lead)
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            Current: {{ claim?.manager?.name || 'Unassigned' }}
          </p>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form :form="assignForm" @submit="assignClaim">
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-1/2">
              <x-select
                v-model="assignForm.manager_id"
                label="Assign to Claims Manager"
                :error="assignForm.errors.manager_id"
                :options="managerOptions"
                placeholder="Select claims manager"
                class="w-full"
                filterable
                filterPlaceholder="Filter managers..."
              />
            </div>
          </div>
          <x-divider class="mt-4" />
          <div class="flex justify-end">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="assignForm.processing"
              type="submit"
            >
              Assign
            </x-button>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
