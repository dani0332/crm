<script setup>
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import LeadStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import AdditionalContacts from '../PersonalQuote/Partials/AdditionalContacts.vue';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory.vue';

defineProps({
  quote: Object,
  documentTypes: Object,
  quoteStatuses: Object,
  paymentMethods: Object,
  insuranceProviders: Object,
  personalPlans: Object,
  isBetaUser: Boolean,
  storageUrl: String,
  quoteType: String,
  can: Object,
  activities: Object,
  advisors: Object,
  lostReasons: Object,
  quoteStatusEnum: Object,
  duplicateAllowedLobs: Array,
  embeddedProducts: Array,
  canAddBatchNumber: Boolean,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const historyLoading = ref(false);

const { isRequired } = useRules();
const notification = useNotifications('toast');

const modals = reactive({
  duplicate: false,
});

const leadDuplicateForm = useForm({
  modelType: 'Pet',
  parentType: 'Pet',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post(route('createDuplicate'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};
</script>

<template>
  <div>
    <Head title="Pet Quotes" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Pet Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>
        <Link
          v-if="can(permissionsEnum.PetQuotesEdit)"
          :href="route('pet-quotes-edit', quote.uuid)"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link
          v-if="can(permissionsEnum.PetQuotesList)"
          :href="route('pet-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"> Pet Quotes </x-button>
        </Link>
      </div>

      <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
        <template #header> Duplicate Lead </template>
        <x-form @submit="onCreateDuplicate" :auto-focus="false">
          <div class="grid gap-4">
            <x-field label="LOBs" required>
              <x-select
                v-model="leadDuplicateForm.lob_team"
                :options="
                  duplicateAllowedLobs.map(lob => ({
                    value: lob,
                    label: lob,
                  }))
                "
                :rules="[isRequired]"
                placeholder="Select LOB For Duplication"
                class="w-full"
                multiple
              />
            </x-field>
            <x-field label="Reason" required>
              <x-select
                v-model="leadDuplicateForm.lob_team_sub_selection"
                :rules="[isRequired]"
                class="w-full"
                :options="[
                  { value: 'new_enquiry', label: 'New enquiry' },
                  { value: 'record_only', label: 'Record purposes only' },
                ]"
              />
            </x-field>
            <x-button
              color="orange"
              type="submit"
              :loading="leadDuplicateForm.processing"
            >
              Create Duplicate
            </x-button>
          </div>
        </x-form>
      </x-modal>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Ref-ID
                </label>
                <template #tooltip> Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.code }}</div>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote?.mobile_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote?.advisor?.name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PRICE</dt>
            <dd>{{ quote?.pet_quote?.premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF PET</dt>
            <dd>{{ quote?.pet_quote?.pet_type?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">BREED OF PET</dt>
            <dd>{{ quote?.pet_quote?.breed_of_pet1 }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">AGE OF PET</dt>
            <dd>{{ quote?.pet_quote?.pet_age?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS NEUTERED</dt>
            <dd>{{ quote?.pet_quote?.is_neutered ? 'Yes' : 'No' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS MICROCHIPPED</dt>
            <dd>{{ quote?.pet_quote?.is_microchipped ? 'Yes' : 'No' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MICROCHIP NO</dt>
            <dd>{{ quote?.pet_quote?.microchip_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS MIXED BREED</dt>
            <dd>{{ quote?.pet_quote?.is_mixed_breed ? 'Yes' : 'No' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">HAS INJURY</dt>
            <dd>{{ quote?.pet_quote?.has_injury ? 'Yes' : 'No' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ quote?.pet_quote?.gender }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ACCOMMODATION TYPE</dt>
            <dd>{{ quote?.pet_quote?.accomodation_type?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POSSESION TYPE</dt>
            <dd>{{ quote?.pet_quote?.possession_type?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.quote_detail?.transapp_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.quote_detail?.lost_reason?.text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <div>
              <x-tooltip position="bottom">
                <label
                  class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                >
                  Parent Ref-ID
                </label>
                <template #tooltip> Parent Reference ID </template>
              </x-tooltip>
            </div>
            <div>{{ quote.parent_duplicate_quote_id }}</div>
          </div>
        </dl>
      </div>
    </div>

    <LastYearPolicyDetail
      v-if="
        quote.source == $page.props.leadSource.RENEWAL_UPLOAD ||
        quote.source == $page.props.leadSource.INSLY
      "
      modelType="Pet"
      :quote="quote"
      :canAddBatchNumber="canAddBatchNumber"
    />

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />

    <QuotePayments
      :can="can"
      :payments="quote.payments"
      :quote-type="quoteType"
      :payment-methods="paymentMethods"
      :insurance-providers="insuranceProviders"
      :is-beta-user="isBetaUser"
      :personal-plans="personalPlans"
    />

    <AdditionalContacts :quote="quote" :quote-type="quoteType" />

    <QuoteStatus
      :quote="quote"
      :quote-type="quoteType"
      :quote-statuses="quoteStatuses"
      :lost-reasons="lostReasons"
      :quoteStatusEnum="quoteStatusEnum"
    />

    <QuoteDocuments
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
    />

    <QuotePolicy :quote="quote" :can="can" :quoteStatusEnum="quoteStatusEnum" />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
      :quote="quote"
      :modelType="quoteType"
    />

    <LeadHistory :quote="quote" />

    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />
  </div>
</template>
