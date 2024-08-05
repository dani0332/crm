<script setup>
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
  idDocumentType: Array,
  modeOfContact: Array,
  employmentSectors: Array,
  residentialStatus: Array,
  companyPosition: Array,
  legalStructure: Array,
  issuancePlace: Array,
  issuingAuthority: Array,
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
};

onMounted(() => {
  if (props.quote.kyc_decision === 'Complete') {
    modals.buttonType = true;
  } else {
    modals.buttonType = false;
  }
});
</script>

<template>
  <x-button
    @click.prevent="kycDocModal(true)"
    size="sm"
    color="primary"
    v-if="!modals.buttonType"
  >
    KYC - Pending
  </x-button>
  <x-button size="sm" color="orange" v-else> KYC - Complete </x-button>

  <x-modal
    size="xl"
    v-model="modals.kycDocModal"
    title="KYC Individual Form"
    show-close
    backdrop
    v-if="props.quote.customer_type == 'Individual'"
  >
    <KycIndividualModal
      :roles="props.roles"
      :quote="props.quote"
      :status="kycDocModal"
      :buttonStatus="changeButtonType"
      :country-list="props.countryList"
      :aml-quote-status="props.amlQuoteStatus"
      :nationalities="props.nationalities"
      :modelType="props.modelType"
      :id-document-type="props.idDocumentType"
      :mode-of-contact="props.modeOfContact"
      :employment-sectors="props.employmentSectors"
      :residential-status="props.residentialStatus"
      :company-position="props.companyPosition"
    />
  </x-modal>

  <x-modal
    size="xl"
    v-model="modals.kycDocModal"
    title="KYC Entity Form"
    show-close
    backdrop
    v-else
  >
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
      :id-document-type="props.idDocumentType"
      :legal-structure="props.legalStructure"
      :issuance-place="props.issuancePlace"
      :issuing-authority="props.issuingAuthority"
    />
  </x-modal>
</template>
