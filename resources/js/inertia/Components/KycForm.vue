<script setup>

import KycIndividualModal from "@/inertia/Components/KycIndividualModal.vue";
import KycEntityModal from "@/inertia/Components/KycEntityModal.vue";

const props = defineProps({
  kycType: String,
  roles: Array,
  quote: Object,
  status: Function,
  buttonStatus: Function,
  countryList: Array,
  amlQuoteStatus: String,
  nationalities: Array,
  modelType: String,
  entities: Array,
});

const modals = reactive({
  kycDocModal: false,
  buttonType: false,
});

const kycDocModal = val => {
  modals.kycDocModal = val;
};

const changeButtonType = val => {
  modals.buttonType = val;
}

onMounted(() => {
  if (props.quote.kyc_decision === 'Complete') {
    modals.buttonType = true
  } else {
    modals.buttonType = false
  }
});

</script>

<template>
  <x-button @click.prevent="kycDocModal(true)" size="sm" color="primary" v-if="!modals.buttonType">
    KYC - Pending
  </x-button>
  <x-button size="sm" color="orange" v-else>
    KYC - Complete
  </x-button>

  <x-modal size="xl" v-model="modals.kycDocModal" show-close backdrop v-if="props.quote.customer_type == 'Individual'">
    <template #header>
      KYC Individual Form
    </template>
    <KycIndividualModal
        :roles="props.roles"
        :quote="props.quote"
        :status="kycDocModal"
        :buttonStatus="changeButtonType"
        :country-list="props.countryList"
        :aml-quote-status="props.amlQuoteStatus"
        :nationalities="props.nationalities"
        :modelType="props.modelType"
    />
  </x-modal>

  <x-modal size="xl" v-model="modals.kycDocModal" show-close backdrop v-else>
    <template #header>
      KYC Entity Form
    </template>
    <KycEntityModal
        :roles="props.roles"
        :quote="props.quote"
        :status="kycDocModal"
        :buttonStatus="changeButtonType"
        :country-list="props.countryList"
        :aml-quote-status="props.amlQuoteStatus"
        :nationalities="props.nationalities"
        :modelType="props.modelType"
        :entities="props.entities"
    />
  </x-modal>
</template>
