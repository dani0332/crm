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
  quoteType: {
    type: String,
  },
});
const page = usePage();
const emit = defineEmits(['success', 'error']);
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

if (!String.prototype.hasOwnProperty('capitalizeFirstChar')) {
  Object.defineProperty(String.prototype, 'capitalizeFirstChar', {
    get: function () {
      return function () {
        return this.charAt(0).toUpperCase() + this.slice(1);
      };
    },
    enumerable: false,
  });
}

const { isRequired } = useRules();

const assignForm = useForm({
  assigned_advisor_id: null,
  assigned_lead_id: '',
  manual_assignment_email_flag: '2',
  modelType: props.quoteType,
});

function onAssignLead(isValid) {
  // const postUrl =
  //   props.quoteType.toLowerCase() == 'car'
  //     ? `/quotes/car/manualLeadAssign`
  //     : `/quotes/${props.quoteType}/leadAssign`;

  const postUrl =
    props.quoteType.toLowerCase() === 'car'
      ? '/quotes/car/manualLeadAssign'
      : props.quoteType === 'tmlead'
        ? '/telemarketing/tmLeadsAssign'
        : `/quotes/${props.quoteType}/leadAssign`;

  if (isValid) {
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${props.selected}`,
        assigned_lead_id: `${props.selected}`,
        assigned_to_id_new: assignForm.assigned_advisor_id,
        assignment_type: 2,
      }))
      .post(postUrl, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          emit('success');
        },
        onError: () => {
          emit('error');
        },
      });
  }
}
const readOnlyMode = reactive({
  isDisable: true,
});

onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <section class="mb-4">
    <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
      <h3 class="font-semibold text-primary-800">Assign Leads</h3>
      <x-divider class="mb-4 mt-1" />
      <x-form @submit="onAssignLead" :auto-focus="false">
        <div class="w-full flex flex-col md:flex-row gap-4">
          <x-select
            v-model="assignForm.assigned_advisor_id"
            label="Assign Advisor"
            :options="props.advisors"
            placeholder="Select Advisor"
            class="flex-1 w-auto"
            filterable
            single
            v-if="readOnlyMode.isDisable === true"
          />
          <div class="mb-3 md:pt-6">
            <x-button
              color="orange"
              size="sm"
              type="submit"
              :loading="assignForm.processing"
              v-if="readOnlyMode.isDisable === true"
            >
              Assign
            </x-button>
          </div>
        </div>
      </x-form>
      <x-alert
        v-if="Object.keys($page.props.errors).length > 0"
        color="error"
        outlined
        light
      >
        <ul class="list-disc list-inside">
          <li v-for="error in $page.props.errors" :key="error">
            {{ error }}
          </li>
        </ul>
      </x-alert>
    </div>
  </section>
</template>
