<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  claim: Object,
  dropdowns: Object,
  requiredFieldsFilled: Boolean,
});

const emit = defineEmits(['update']);

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

const claimStatusForm = useForm({
  claim_status_id: props.claim?.claim_status_id || '',
});

const statusOptions = computed(() => {
  return (
    props.dropdowns.claimStatuses?.map(status => ({
      value: status.id,
      label: status.text,
    })) || []
  );
});

const updateClaimStatus = isValid => {
  console.log('updateClaimStatus');
  claimStatusForm.post(route('claims.update.status', props.claim?.uuid), {
    preserveScroll: true,
    onSuccess: response => {
      console.log('response', response);
      router.visit(route('claims.show', props.claim?.uuid), {
        preserveScroll: true,
      });
      emit('update', response);
    },
    onError: errors => {
      Object.keys(errors).forEach(function (key) {
        notification.error({
          title: errors[key],
          position: 'top',
        });
      });
    },
  });
};

const disableClaimStatusUpdate = computed(() => {
  return (
    !props.requiredFieldsFilled ||
    !canAny([permissionsEnum.CLAIMS_STATUS_UPDATE])
  );
});
</script>

<template>
  <div
    v-if="canAny([permissionsEnum.CLAIMS_STATUS_UPDATE])"
    class="p-4 rounded shadow mb-6 bg-white"
  >
    <Collapsible :expanded="true">
      <template #header>
        <div>
          <h3 class="font-semibold text-primary-800 text-lg">Claim Status</h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <x-form :form="claimStatusForm" @submit="updateClaimStatus">
          <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div
              v-if="can(permissionsEnum.CLAIMS_STATUS_UPDATE)"
              class="w-full md:w-1/2"
            >
              <div class="flex flex-col gap-4">
                <x-select
                  v-model="claimStatusForm.claim_status_id"
                  label="Claim Status"
                  :error="claimStatusForm.errors.claim_status_id"
                  :options="statusOptions"
                  :disabled="disableClaimStatusUpdate"
                  placeholder="Claim Status"
                  class="w-full uppercase"
                  filterable
                />
              </div>
            </div>
          </div>

          <x-divider class="mt-4" />
          <div class="flex justify-end">
            <x-button
              :disabled="disableClaimStatusUpdate"
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="claimStatusForm.processing"
              type="submit"
            >
              Update
            </x-button>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
