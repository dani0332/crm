<script setup>
const page = usePage();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  canAddBatchNumber: Boolean,
  modelType: String,
  inslyId: String,
});

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => {
  return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
};

const allowEdit = computed(() => {
  if (
    (props.quote.renewal_batch === '' || props.quote.renewal_batch == null) &&
    props.canAddBatchNumber == true
  )
    return true;

  return false;
});
const { isRequired } = useRules();

const policyForm = useForm({
  model: props.model,
  renewal_batch: props?.quote?.renewal_batch || null,
  model_type: props?.modelType,
  quote_id: props?.quote.id,
});

function onSubmit(isValid) {
  if (isValid) {
    policyForm
      .transform(data => ({
        renewal_batch: data.renewal_batch,
        model_type: data.model_type,
        quote_id: data.quote_id,
        isInertia: true,
      }))
      .post(`/quotes/update-last-year-policy`, {
        preserveScroll: true,
        onSuccess: () => {},
        onFinish: () => {},
      });
  }
}

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between gap-4 items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Last Year's Policy Details
      </h3>
        <div>
            <Link
                v-if="inslyId && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
                :href="`/legacy-policy/${inslyId}`"
                preserve-scroll
            >
                <x-button size="sm" color="#ff5e00" tag="div">
                    View Legacy policy
                </x-button>
            </Link>
        </div>
    </div>
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="text-sm">
          <div class="grid md:grid-cols-2 gap-x-6 gap-y-4">
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Renewal Batch#</div>
              <div>{{ props?.quote?.renewal_batch }}</div>
            </div>

            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Previous Policy Number</div>
              <div>{{ props?.quote?.previous_quote_policy_number }}</div>
            </div>

            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Previous Policy Expiry Date</div>
              <div>{{ props?.quote?.previous_policy_expiry_date }}</div>
            </div>

            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Previous Policy Premium</div>
              <div>{{ props?.quote?.previous_quote_policy_premium }}</div>
            </div>

            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Previous Policy Start Date</div>
              <div>{{ props?.quote?.policy_start_date }}</div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Previous Advisor</div>
              <div>
                {{ props?.quote?.previous_advisor_id_text }}
              </div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Policy Number</div>
              <div>{{ props?.quote?.policy_number }}</div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Renewal Expiry Date</div>
              <div>{{ props?.quote?.renewal_expiry_date }}</div>
            </div>
            <div class="grid sm:grid-cols-2">
              <div class="font-medium">Lost reason</div>
              <div>
                {{ props?.quote?.lost_reason }}
              </div>
            </div>
          </div>
        </div>
      </div>
      <div
        class="flex justify-between gap-3 items-center"
        v-if="canAddBatchNumber"
      >
        <x-field label="Renewal batch" required>
          <x-input
            v-model="policyForm.renewal_batch"
            type="tel"
            class="w-full md:w-64"
            :rules="[isRequired]"
            :disabled="!allowEdit"
            :error="policyForm.errors.renewal_batch"
          />
        </x-field>
        <x-button v-if="allowEdit" color="primary" type="submit">
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
