<script setup>
const page = usePage();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
  canAddBatchNumber: Boolean,
  modelType: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  inslyId: String,
});

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const leadSource = page.props.leadSource;

const dateFormat = date => {
  try {
    console.log('date', date);
    if (!date || date == '' || date == null) {
      return '-';
    }

    if (date.includes(':') && !date.includes(' ')) {
      return date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
    }

    const formattedDate = parseDate(date);
    return formattedDate;
  } catch (error) {
    console.error(`Error parsing date "${date}": ${error.message}`);
  }
};

const allowEdit = computed(() => {
  if (
    (props.quote.renewal_batch === '' || props.quote.renewal_batch == null) &&
    props.canAddBatchNumber == true &&
    props.modelType == 'Car'
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
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Last Year's Policy Details
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="flex gap-2 mb-4 justify-end">
          <Link
            v-if="inslyId && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
            :href="`/legacy-policy/${inslyId}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
          <Link
            v-else-if="
              quote.source == leadSource.RENEWAL_UPLOAD &&
              quote.previous_quote_policy_number != null &&
              can(permissionsEnum.VIEW_LEGACY_DETAILS)
            "
            :href="
              route(
                'view-legacy-policy.renewal-uploads',
                quote.previous_quote_policy_number,
              )
            "
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
        </div>

        <x-form @submit="onSubmit" :auto-focus="false">
          <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="text-sm">
              <div class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="grid sm:grid-cols-2">
                  <div class="font-medium">Renewal Batch Number</div>
                  <div>{{ props?.quote?.renewal_batch }}</div>
                </div>

                <div class="grid sm:grid-cols-2">
                  <div class="font-medium">Previous Policy Number</div>
                  <div>{{ props?.quote?.previous_quote_policy_number }}</div>
                </div>

                <div class="grid sm:grid-cols-2">
                  <div class="font-medium">Previous Policy Expiry Date</div>
                  <div>
                    {{ dateFormat(props?.quote?.previous_policy_expiry_date) }}
                  </div>
                </div>

                <div class="grid sm:grid-cols-2">
                  <div class="font-medium">Previous Policy Premium</div>
                  <div>{{ props?.quote?.previous_quote_policy_premium }}</div>
                </div>

                <div class="grid sm:grid-cols-2">
                  <div class="font-medium">Previous Policy Start Date</div>
                  <div>{{ dateFormat(props?.quote?.policy_start_date) }}</div>
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
                  <div class="font-medium">Policy Expiry Date</div>
                  <div>
                    {{ dateFormat(props?.quote?.previous_policy_expiry_date) }}
                  </div>
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
            <x-field v-if="allowEdit" label="Renewal batch" required>
              <x-input
                v-model="policyForm.renewal_batch"
                type="tel"
                class="w-full md:w-64"
                :rules="[isRequired]"
                :error="policyForm.errors.renewal_batch"
              />
            </x-field>
            <div v-if="readOnlyMode.isDisable === true">
              <x-button v-if="allowEdit" color="primary" type="submit">
                Update
              </x-button>
            </div>
          </div>
        </x-form>
      </template>
    </Collapsible>
  </div>
</template>
