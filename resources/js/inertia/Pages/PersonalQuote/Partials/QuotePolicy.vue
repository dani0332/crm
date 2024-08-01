<script setup>
import { useCan } from '../../../Composables/can';

defineProps({
  quote: Object,
  quoteStatusEnum: Object
});

const notification = useNotifications('toast');
const page = usePage();

const dateToYMD = date => {
  if (date) {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = `0${d.getMonth() + 1}`.slice(-2);
    const day = `0${d.getDate()}`.slice(-2);
    return `${year}-${month}-${day}`;
  }
  return '';
};

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const policyForm = useForm({
  premium: page.props.quote.premium,
  policy_number: page.props.quote.policy_number || '',
  policy_start_date: dateToYMD(page.props.quote.policy_start_date),
  renewal_expiry_date: dateToYMD(page.props.quote.renewal_expiry_date) || '',
  policy_issuance_date: dateToYMD(page.props.quote.policy_issuance_date) || '',
  quote_status_id: page.props.quote.quote_status_id,
  canEdit:
    page.props.quote.quote_status_id ==
      page.props.quoteStatusEnum?.TransactionApproved && !hasRole(rolesEnum.PA),
  editMode: false,
  quote_id: page.props.quote.id,
});

//todo: better validation way to remove duplicate code
const rules = {
  isRequired: v => !!v || 'This field is required',
  policy_number: v => {
    if (v) {
      return (
        v.length <= 50 || 'Policy Number should be less than 50 characters'
      );
    }
    return true;
  },
  policy_start_date: v => {
    if (v) {
      const date = new Date(v);
      return !isNaN(date.getTime());
    }
    return true;
  },
  renewal_expiry_date: v => {
    if (v) {
      const date = new Date(v);
      if (policyForm.policy_start_date) {
        const startDate = new Date(policyForm.policy_start_date);
        if (startDate >= date) {
          return 'Expiry date should be greater than Start Date';
        }
      }
      return !isNaN(date.getTime());
    }
    return true;
  },
  premium: v => {
    if (v) {
      const premium = parseFloat(v);
      if (premium < 0 || isNaN(premium)) {
        return 'Premium should be greater than 0';
      }
    }
    return true;
  },
};

const cancelPolicyFrom = () => {
  policyForm.editMode = false;
};

const submitpolicyForm = isValid => {
  if (!isValid) return;
  policyForm
    .transform(data => ({
      policy_number: data.policy_number,
      policy_start_date: data.policy_start_date,
      renewal_expiry_date: data.renewal_expiry_date,
      policy_issuance_date: data.policy_issuance_date,
      premium: data.premium,
    }))
    .patch(`/personal-quotes/${page.props.quote.id}/update-policy-details`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Policy Details Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        policyForm.editMode = false;
      },
    });
};
</script>

<template>
  <x-collapse show-icon class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
    <template #content>
      <x-divider class="mb-4 mt-1" />
      <x-form @submit="submitpolicyForm" :auto-focus="false">
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyForm.policy_number"
              :error="policyForm.errors.policy_number"
              :disabled="!policyForm.editMode"
              label="Policy Number"
              :rules="[rules.isRequired, rules.policy_number]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyForm.policy_issuance_date"
              :error="policyForm.errors.policy_issuance_date"
              :disabled="!policyForm.editMode"
              type="date"
              label="Issuance Date"
              :rules="[rules.isRequired]"
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyForm.policy_start_date"
              :error="policyForm.errors.policy_start_date"
              :disabled="!policyForm.editMode"
              type="date"
              label="Start Date"
              :rules="[rules.isRequired, rules.policy_start_date]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyForm.renewal_expiry_date"
              :disabled="!policyForm.editMode"
              type="date"
              label="Expiry Date"
              :rules="[rules.isRequired, rules.renewal_expiry_date]"
              class="w-full"
            />
          </div>
        </div>
        <div class="flex gap-6 w-full">
          <div class="w-full md:w-1/2">
            <x-input
              v-model="policyForm.premium"
              :disabled="!policyForm.editMode"
              label="Price"
              :rules="[rules.isRequired, rules.premium]"
              class="w-full"
            />
          </div>
          <div class="w-full md:w-1/2"></div>
        </div>

        <div class="text-right space-x-4 mt-12" v-if="policyForm.canEdit">
          <x-button
            color="#007bff"
            size="sm"
            v-show="policyForm.editMode"
            @click.prevent="cancelPolicyFrom"
            >Cancel</x-button
          >

          <x-button
            color="#26B99A"
            type="submit"
            size="sm"
            v-show="policyForm.editMode"
            >Update</x-button
          >

          <x-button
            color="#007bff"
            size="sm"
            type="submit"
            v-show="!policyForm.editMode"
            @click.prevent="policyForm.editMode = true"
            >Edit</x-button
          >
        </div>
      </x-form>
    </template>
  </x-collapse>
  <!-- <div class="p-4 rounded shadow mb-6 bg-white">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <x-form @submit="submitpolicyForm" :auto-focus="false">
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyForm.policy_number"
            :error="policyForm.errors.policy_number"
            :disabled="!policyForm.editMode"
            label="Policy Number"
            :rules="[rules.isRequired, rules.policy_number]"
            class="w-full"
          />
        </div>
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyForm.policy_issuance_date"
            :error="policyForm.errors.policy_issuance_date"
            :disabled="!policyForm.editMode"
            type="date"
            label="Issuance Date"
            :rules="[rules.isRequired]"
            class="w-full"
          />
        </div>
      </div>
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyForm.policy_start_date"
            :error="policyForm.errors.policy_start_date"
            :disabled="!policyForm.editMode"
            type="date"
            label="Start Date"
            :rules="[rules.isRequired, rules.policy_start_date]"
            class="w-full"
          />
        </div>
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyForm.renewal_expiry_date"
            :disabled="!policyForm.editMode"
            type="date"
            label="Expiry Date"
            :rules="[rules.isRequired, rules.renewal_expiry_date]"
            class="w-full"
          />
        </div>
      </div>
      <div class="flex gap-6 w-full">
        <div class="w-full md:w-1/2">
          <x-input
            v-model="policyForm.premium"
            :disabled="!policyForm.editMode"
            label="Price"
            :rules="[rules.isRequired, rules.premium]"
            class="w-full"
          />
        </div>
        <div class="w-full md:w-1/2"></div>
      </div>

      <div class="text-right space-x-4 mt-12" v-if="policyForm.canEdit">
        <x-button
          color="#007bff"
          size="sm"
          v-show="policyForm.editMode"
          @click.prevent="cancelPolicyFrom"
          >Cancel</x-button
        >

        <x-button
          color="#26B99A"
          type="submit"
          size="sm"
          v-show="policyForm.editMode"
          >Update</x-button
        >

        <x-button
          color="#007bff"
          size="sm"
          type="submit"
          v-show="!policyForm.editMode"
          @click.prevent="policyForm.editMode = true"
          >Edit</x-button
        >
      </div>
    </x-form>
  </div> -->
</template>
