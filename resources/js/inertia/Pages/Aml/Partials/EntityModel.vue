<script setup>
import UBODetailsModels from './UBODetailsModels.vue';
import MemberDetailsModel from './MemberDetailsModel.vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  quoteType: Object,
  quoteDetails: Object,
  entityDetails: Object,
  nationalities: Object,
  membersDetails: Object,
  uboDetails: Object,
  memberRelations: Object,
  uboRelations: Object,
  customerTypeEnum: Object,
  lookups: Object,
  legalStructure: Object,
  entities: Array,
  quoteAmlStatus: Number,
  customerDetails: Object
});

const loader = ref({
  search: false,
  link: false,
});
const emit = defineEmits(['update:modelValue', 'loaded']);
const notification = useToast();
const showModal = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

const modals = reactive({
  insuredDetailConfirmation: false,
  individualView: false,
});

const entityFound = ref(false);
const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const insuredFormDetails = useForm({
  customer_id: props.quoteDetails.customer_id,
  customer_type: props.customerTypeEnum.Entity,
  quote_type: props.quoteType.code,

  insured_first_name: props.quoteDetails?.customer?.insured_first_name ?? null,
  insured_last_name: props.quoteDetails?.customer?.insured_last_name ?? null,
  nationality_id: props.quoteDetails?.customer.nationality_id ?? null,
  dob: props.quoteDetails?.customer.dob ?? null,

  entity_id: props.entityDetails?.entity?.id,
  trade_license_no: props.entityDetails?.entity?.trade_license_no,
  company_name: props.entityDetails?.entity?.company_name,
  company_address: props.entityDetails?.entity?.company_address,
  entity_type_code: props.entityDetails?.entity?.entity_type_code,
  industry_type_code: props.entityDetails?.entity?.industry_type_code,
  emirate_of_registration_id:
    props.entityDetails?.entity?.emirate_of_registration_id,
});

const insuredDetailsSubmit = isValid => {
  if (!isValid) return;

  insuredFormDetails.get(`${props.quoteDetails.id}/quoteUpdate`, {
    preserveScroll: true,
    onError: errors => {
      notification.error({
        title: errors.error || 'Quote not updated',
        position: 'top',
      });
    },
    onSuccess: () => {
      notification.success({
        title: 'Quote is updated',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.addContact = false;
    },
  });
};

const switchToIndividualView = () => {
  modals.insuredDetailConfirmation = false;
  showModal.value = false;
  modals.individualView = true;
  insuredFormDetails.customer_type = props.customerTypeEnum.Individual;
};

const entityDetailsFound = ref(false);
const tradeLicenseEntity = reactive({
  entity_id: null,
  trade_license: null,
  company_name: null,
  company_address: null,
});

const resetTradeEntity = () => {
  tradeLicenseEntity.entity_id = null;
  tradeLicenseEntity.trade_license = '';
  tradeLicenseEntity.company_name = '';
  tradeLicenseEntity.company_address = '';
};
const searchByTradeLicense = () => {
  loader.value.search = true;
  let url = `/kyc/aml-fetch-entity?trade_license=${insuredFormDetails.trade_license_no}`;
  axios
    .get(url)
    .then(res => {
      if (res.data.status) {
        let response = res.data.response;

        entityFound.value = true;
        tradeLicenseEntity.entity_id = response.id;
        tradeLicenseEntity.trade_license = response.trade_license_no;
        tradeLicenseEntity.company_name = response.company_name;
        tradeLicenseEntity.company_address = response.company_address;

        notification.success({
          title: res.data.message,
          position: 'top',
        });
      } else {
        notification.error({
          title: res.data.message,
          position: 'top',
        });
        resetTradeEntity();
        entityFound.value = false;
      }
    })
    .catch(err => {
      console.log(err);
    })
    .finally(() => (loader.value.search = false));
};

const linkEntity = () => {
  loader.value.link = true;
  let entityDetails = {
    quote_type_id: props.quoteType.id,
    quote_request_id: props.quoteDetails.id,
    entity_id: tradeLicenseEntity.entity_id,
  };
  axios
    .post(route('link-entity-details'), entityDetails)
    .then(res => {
      entityFound.value = false;
      notification.success({
        title: res.data.message,
        position: 'top',
      });
    })
    .catch(error => {
      notification.success({
        title: res.data.message,
        position: 'top',
      });
    })
    .finally(() => (loader.value.link = false));
};

const show = ref(true);
</script>

<template>
  <AppModal
    :showClose="true"
    :showHeader="true"
    v-model:modelValue="showModal"
    class=""
  >
    <template #header>Update and Verify</template>
    <template #default>
      <p class="text-center mb-10">
        Please confirm the Company Name, and UBO details as per the Trade
        License
      </p>
      <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
        <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 mb-5">
          <x-field label="Trade License No">
            <x-input
              v-model="insuredFormDetails.trade_license_no"
              placeholder="Trade License No"
              type="text"
              class="w-full"
            />
            <x-button
              @click.prevent="searchByTradeLicense"
              size="xs"
              color="primary"
              :loading="loader.search"
            >
              Search
            </x-button>
          </x-field>
          <x-field label="Company Name">
            <x-input
              v-model="insuredFormDetails.company_name"
              placeholder="Company Name"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Company Address">
            <x-textarea
              v-model="insuredFormDetails.company_address"
              placeholder="Company Address"
              type="text"
              class="w-full"
            />
          </x-field>
        </dl>
        <div v-if="entityFound" class="mb-5">
          <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
            <x-field label="Trade License No">
              <x-input
                v-model="tradeLicenseEntity.trade_license"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <x-field label="Company Name">
              <x-input
                v-model="tradeLicenseEntity.company_name"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <x-field label="Company Address">
              <x-input
                v-model="tradeLicenseEntity.company_address"
                type="text"
                class="w-full"
                disabled
              />
            </x-field>
            <div class="text-left space-x-4">
              <x-button size="sm" color="info"> Hide </x-button>
              <x-button
                size="sm"
                color="orange"
                @click.prevent="linkEntity"
                :loading="loader.link"
              >
                Link
              </x-button>
            </div>
          </dl>
        </div>
        <x-divider class="mb-4 mt-1" />

        <UBODetailsModels
          :quoteDetails="quoteDetails"
          :quoteType="quoteType"
          :nationalities="nationalities"
          :uboDetails="uboDetails"
          :uboRelations="uboRelations"
          :entity_id="insuredFormDetails.entity_id"
          :customerType="props.customerTypeEnum.Entity"
        />
        <x-divider class="mb-4 mt-1" />

        <div class="text-center space-x-4 mt-8">
          <x-button
            size="sm"
            color="success"
            @click.prevent="modals.insuredDetailConfirmation = true"
          >
            Submit For AML Screening
          </x-button>
        </div>

        <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">KYC Details</h3>
        </div>

        <KycEntityModal
          :roles="$page.props.rolesEnum"
          :quote="quoteDetails"
          :country-list="nationalities"
          :aml-quote-status="props.quoteAmlStatus"
          :nationalities="nationalities"
          :modelType="props.quoteType?.code"
          :entities="props.entities"
          :idDocumentType="props.lookups.id_type"
          :legal-structure="props.lookups.legal_structure"
          :issuance-place="props.lookups.issuance_place"
          :issuing-authority="props.lookups.issuing_authority"
          :ubo-relation="props.lookups.ubo_relation"
          :entity-details="props.entityDetails"
        />
      </x-form>
    </template>
  </AppModal>
  <!-- <x-modal v-model="showModal" size="xl" show-close backdrop>
    <template #header>Update and Verify</template>
    <p class="text-center mb-10">
      Please confirm the Company Name, and UBO details as per the Trade License
    </p>

    <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 mb-5">
        <x-field label="Trade License No">
          <x-input
            v-model="insuredFormDetails.trade_license_no"
            placeholder="Trade License No"
            type="text"
            class="w-full"
          />
          <x-button
            @click.prevent="searchByTradeLicense"
            size="xs"
            color="primary"
            :loading="loader.search"
          >
            Search
          </x-button>
        </x-field>
        <x-field label="Company Name">
          <x-input
            v-model="insuredFormDetails.company_name"
            placeholder="Company Name"
            type="text"
            class="w-full"
          />
        </x-field>
        <x-field label="Company Address">
          <x-textarea
            v-model="insuredFormDetails.company_address"
            placeholder="Company Address"
            type="text"
            class="w-full"
          />
        </x-field>
      </dl>
      <div v-if="entityFound" class="mb-5">
        <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
          <x-field label="Trade License No">
            <x-input
              v-model="tradeLicenseEntity.trade_license"
              type="text"
              class="w-full"
              disabled
            />
          </x-field>
          <x-field label="Company Name">
            <x-input
              v-model="tradeLicenseEntity.company_name"
              type="text"
              class="w-full"
              disabled
            />
          </x-field>
          <x-field label="Company Address">
            <x-input
              v-model="tradeLicenseEntity.company_address"
              type="text"
              class="w-full"
              disabled
            />
          </x-field>
          <div class="text-left space-x-4">
            <x-button size="sm" color="info"> Hide </x-button>
            <x-button
              size="sm"
              color="orange"
              @click.prevent="linkEntity"
              :loading="loader.link"
            >
              Link
            </x-button>
          </div>
        </dl>
      </div>
      <x-divider class="mb-4 mt-1" />

      <UBODetailsModels
        :quoteDetails="quoteDetails"
        :quoteType="quoteType"
        :nationalities="nationalities"
        :uboDetails="uboDetails"
        :uboRelations="uboRelations"
        :entity_id="insuredFormDetails.entity_id"
        :customerType="props.customerTypeEnum.Entity"
      />
      <x-divider class="mb-4 mt-1" />

      <div class="text-right space-x-4 mt-8">
        <x-button
          size="sm"
          color="success"
          @click.prevent="modals.insuredDetailConfirmation = true"
        >
          Confirm
        </x-button>
      </div>
    </x-form>
  </x-modal> -->

  <AppModal
    :actions="true"
    v-model:modelValue="modals.insuredDetailConfirmation"
    :backdrop-close="false"
  >
    <template #header>
      <p>Confirmation</p>
    </template>
    <template #default>
      <p>Are you sure you want to run AML screen for this lead as Entity?</p>
    </template>
    <template #actions>
      <div class="text-center space-x-4">
        <x-button
          size="sm"
          color="#ff5e00"
          @click.prevent="switchToIndividualView"
        >
          No
        </x-button>
        <x-button
          size="sm"
          color="success"
          @click.prevent="insuredDetailsSubmit"
          :loading="insuredFormDetails.processing"
        >
          Yes
        </x-button>
      </div>
    </template>
  </AppModal>
  <!-- <x-modal
    v-model="modals.insuredDetailConfirmation"
    show-close
    :backdrop="true"
    ref="target"
  >
    <p>Are you sure you want to run AML screen for this lead as Entity?</p>
    <template #actions>
      <div class="text-center space-x-4">
        <x-button
          size="sm"
          color="#ff5e00"
          @click.prevent="switchToIndividualView"
        >
          No
        </x-button>
        <x-button
          size="sm"
          color="success"
          @click.prevent="insuredDetailsSubmit"
          :loading="insuredFormDetails.processing"
        >
          Yes
        </x-button>
      </div>
    </template>
  </x-modal> -->

  <!-- Individual Type Insured Form -->
  <x-modal v-model="modals.individualView" size="xl" show-close backdrop>
    <template #header>Update and Verify</template>
    <p class="text-center mb-10">
      Please confirm the Name, Nationality, and Date of Birth of the insured
      person(s) as per the Emirates ID
    </p>

    <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insured First Name</dt>
          <dd>
            <x-input
              v-model="insuredFormDetails.insured_first_name"
              placeholder="Insured First Name"
              class="w-full"
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insured Last Name</dt>
          <dd>
            <x-input
              v-model="insuredFormDetails.insured_last_name"
              placeholder="Insured Last Name"
              class="w-full"
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Nationality</dt>
          <dd>
            <ComboBox
              :single="true"
              v-model="insuredFormDetails.nationality_id"
              placeholder="Select Nationality"
              :options="nationalitiesOptions"
              class="w-full"
            />
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Date of Birth</dt>
          <dd>
            <DatePicker
              v-model="insuredFormDetails.dob"
              placeholder="Date of Birth"
              class="w-full"
            />
          </dd>
        </div>
      </dl>
      <x-divider class="mb-4 mt-1" />

      <MemberDetailsModel
        :quoteDetails="quoteDetails"
        :quoteType="quoteType"
        :nationalities="nationalities"
        :membersDetails="membersDetails"
        :memberRelations="memberRelations"
        :customerType="props.customerTypeEnum.Individual"
      />
      <div class="flex justify-center my-5">
        <x-button
          size="sm"
          color="success"
          @click.prevent="insuredDetailsSubmit"
          :loading="insuredFormDetails.processing"
          class=""
        >
          Submit For AML Screening
        </x-button>
      </div>

      <x-divider class="mb-4 mt-4" />

      <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">KYC Details</h3>
      </div>

      <KycIndividualModal
        :roles="$page.props.rolesEnum"
        :quote="quoteDetails"
        :countryList="nationalities"
        :amlQuoteStatus="$page.props.amlQuoteStatus"
        :nationalities="nationalities"
        :modelType="quoteType?.code"
        :idDocumentType="props.lookups.id_type"
        :modeOfContact="props.lookups.mode_of_contact"
        :employmentSectors="props.lookups.employment_sector"
        :residentialStatus="props.lookups.resident_status"
        :companyPosition="props.lookups.company_position"
      />

      <!-- <div class="text-right space-x-4 mt-8">
        <x-button
          size="sm"
          color="red"
          @click.prevent="modals.individualView = false"
        >
          Cancel
        </x-button>
      </div> -->
    </x-form>
  </x-modal>
</template>
