<script setup>
const notification = useNotifications('toast');
const page = usePage();

const props = defineProps({
  quote: {
    type: Object,
    default: {},
  },
});

const policyForm = useForm({
  model: props.model,
  renewal_batch: props?.quote?.renewal_batch || null,

  modelType: page.props.modelType,
  quote_id: page.props.quote.id,
});

function onSubmit(isValid) {
  if (isValid) {
    policyForm
      .transform(data => ({
        renewal_batch: data.renewal_batch,
        modelType: data.modelType,
        quote_id: data.quote_id,
        isInertia: true,
      }))
      .post(`/quotes/${page.props.modelType}/update-quote-policy`, {
        preserveScroll: true,
        onSuccess: () => {},
        onFinish: () => {},
      });
  }
}
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between gap-4 items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Last Year's Policy Details
      </h3>
    </div>
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
            <x-field label="Renewal batch" required>
              <x-input
                v-model="policyForm.renewal_batch"
                type="tel"
                class="w-full"
                :disabled="props?.quote?.renewal_batch > 0"
                :error="policyForm.errors.renewal_batch"
              />
            </x-field>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Policy Number</dt>
              <dd>{{ props?.quote?.previous_quote_policy_number }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Policy Expiry Date</dt>
              <dd>{{ quote?.previous_policy_expiry_date }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Policy Price</dt>
              <dd>{{ quote?.previous_quote_policy_premium }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Start Date</dt>
              <dd>{{ quote?.policy_start_date }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Previous Advisor</dt>
              <dd>
                {{ quote?.previous_advisor_id_text }}
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Policy Number</dt>
              <dd>{{ quote?.policy_number }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Renewal Expiry Date</dt>
              <dd>{{ quote?.renewal_expiry_date }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Lost reason</dt>
              <dd>
                {{ quote?.lost_reason }}
              </dd>
            </div>
          </dl>
        </div>
      </div>
      <div
        class="flex justify-end gap-3 mb-4"
        v-if="!props?.quote?.renewal_batch > 0"
      >
        <x-button color="primary" type="submit">Update </x-button>
      </div>
    </x-form>
  </div>
</template>
