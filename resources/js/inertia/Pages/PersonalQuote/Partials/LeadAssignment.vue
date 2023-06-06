<script setup>
const props = defineProps({
  selected: {
    type: Array,
    default: () => [],
  },
  advisors: {
    type: Array,
    default: () => [],
  },
});

const { isRequired } = useRules();

const assignForm = useForm({
  assigned_to_id_new: null,
  manual_assignment_email_flag: '1',
  modelType: 'Home',
  selectTmLeadId: '',
});

function onAssignLead(isValid) {
  if (isValid) {
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${props.selected}`,
      }))
      .post('/quotes/home/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          quotesSelected.value = [];
          notification.success({
            title: 'Home Leads Assigned',
            position: 'top',
          });
        },
      });
  }
}
</script>

<template>
  <section class="mb-4">
    <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
      <h3 class="font-semibold text-primary-800">Assign Leads</h3>
      <x-divider class="mb-4 mt-1" />
      <x-form @submit="onAssignLead" :auto-focus="false">
        <div class="w-full flex flex-col md:flex-row gap-4">
          <ComboBox
            v-model="assignForm.assigned_to_id_new"
            label="Assign Advisor"
            :options="props.advisors"
            placeholder="Select Advisor"
            class="flex-1 w-auto"
            single
          />
          <x-select
            v-model="assignForm.manual_assignment_email_flag"
            label="Assignment Type"
            :options="[
              { value: '1', label: 'Without Email' },
              { value: '2', label: 'With Email' },
            ]"
            placeholder="Select Type"
            class="flex-1 w-auto"
            :rules="[isRequired]"
          />
          <div class="mb-3 md:pt-6">
            <x-button
              color="orange"
              size="sm"
              type="submit"
              :loading="assignForm.processing"
            >
              Assign
            </x-button>
          </div>
        </div>
      </x-form>
    </div>
  </section>
</template>
