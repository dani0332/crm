<script setup>
import QuoteDocuments from '../PersonalQuote/Partials/QuoteDocuments';
import QuoteStatus from '../PersonalQuote/Partials/QuoteStatus';
import QuotePayments from '../PersonalQuote/Partials/QuotePayments';
import QuoteActivities from '../PersonalQuote/Partials/QuoteActivities';
import QuotePolicy from '../PersonalQuote/Partials/QuotePolicy';
import LeadHistory from '../PersonalQuote/Partials/LeadHistory';
import MemberDetails from "../../Components/MemberDetails.vue";

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
  customerTypeEnum: Object,
  nationalities: Array,
  memberRelations: Array,
  membersDetails: Array
});

const page = usePage();
const notification = useToast();
const hasAnyRole = roles => useHasAnyRole(roles);
const modals = reactive({
  duplicate: false,
});

const leadDuplicateForm = useForm({
  modelType: 'cycle',
  parentType: 'cycle',
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

const historyLoading = ref(false);

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    route('getLeadHistory', {
      modelType: 'health',
      recordId: page.props.quote.id,
    }),
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Notes', value: 'NewNotes' },
  { text: 'Lead Status', value: 'NewStatus' },
];
const { isRequired } = useRules();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const isProfileUpdateAllow = computed(() => {
    return !hasAnyRole([
        page.props.rolesEnum.PA,
        page.props.rolesEnum.OE,
        page.props.rolesEnum.NRA
    ]);
});

const customerProfileForm = useForm({
    customer_id: page.props.quote.customer_id,
    insured_first_name: page.props.quote?.customer.insured_first_name || '',
    insured_last_name: page.props.quote?.customer.insured_last_name || '',
    emirates_id_number: page.props.quote?.customer.emirates_id_number || null,
    emirates_id_expiry_date: page.props.quote?.customer.emirates_id_expiry_date || null,
});

const updateProfileDetails = isValid => {
    if (!isValid) return;

    customerProfileForm.post(route('update-customer-profile'), {
        preserveScroll: true,
        onSuccess: () => {
            notification.success({
                title: 'Customer profile details update Successfully',
                position: 'top',
            });
        },
        onError: errors => {
            Object.keys(errors).forEach(function(key) {
                notification.error({
                    title: errors[key],
                    position: 'top',
                });
            });
        },
    });
}

</script>

<template>
  <div>
    <Head title="Cycle Quotes" />
    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Cycle Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>
        <Link
          v-if="can(permissionsEnum.CycleQuotesList)"
          :href="route('cycle-quotes-list')"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div">
            Cycle Quotes
          </x-button>
        </Link>
        <Link
          v-if="can(permissionsEnum.CycleQuotesEdit)"
          :href="route('cycle-quotes-edit', quote.uuid)"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
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
                <dt class="font-medium">CUSTOMER TYPE</dt>
                <dd>{{ quote.customer_type }}</dd>
            </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor?.name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED BY</dt>
            <dd>{{ quote?.created_by?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">UPDATED BY</dt>
            <dd>{{ quote?.updated_by?.email }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.quote_detail?.lost_reason?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
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

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CYCLE MAKE</dt>
            <dd>{{ quote?.cycle_quote?.cycle_make }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CYCLE MODEL</dt>
            <dd>{{ quote?.cycle_quote?.cycle_model }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">YEAR OF MANUFACTURE</dt>
            <dd>{{ quote?.cycle_quote?.year_of_manufacture?.text }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PURCHASED OF VALUE(AED)</dt>
            <dd>{{ quote?.asset_value }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ACCESSORIES</dt>
            <dd>{{ quote?.cycle_quote?.accessories }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">HAS ACCIDENT</dt>
            <dd>{{ quote?.cycle_quote?.has_accident ? 'YES' : 'NO' }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">HAS GOOD CONDITION</dt>
            <dd>{{ quote?.cycle_quote?.has_good_condition ? 'YES' : 'NO' }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ quote.previous_policy_expiry_date }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PRICE</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ quote.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY START DATE</dt>
            <dd>{{ quote.policy_start_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY END DATE</dt>
            <dd>{{ quote.policy_issuance_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PRICE</dt>
            <dd>{{ quote.premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote?.quote_detail?.transapp_code }}</dd>
          </div>
        </dl>
      </div>
    </div>

      <div class="p-4 rounded shadow mb-6 bg-white">
          <div>
              <h3 class="font-semibold text-primary-800 text-lg">{{ quote.customer_type == page.props.customerTypeEnum.Individual ? 'Customer ' : 'Entity '}} Profile</h3>
              <x-divider class="mb-4 mt-1" />
          </div>
          <x-form @submit="updateProfileDetails" :auto-focus="false">
              <div class="text-sm">
                  <dl v-if="quote.customer_type === page.props.customerTypeEnum.Individual" class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">FIRST NAME</dt>
                          <dd>{{ quote.first_name }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">LAST NAME</dt>
                          <dd>{{ quote.last_name }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">INSURED FIRST NAME</dt>
                          <dd>
                              <x-input
                                  v-model="customerProfileForm.insured_first_name"
                                  :rules="[isRequired]"
                                  placeholder="INSURED FIRST NAME"
                                  class="w-full"
                                  :disabled="!isProfileUpdateAllow"
                              />
                          </dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">INSURED LAST NAME</dt>
                          <dd>
                              <x-input
                                  v-model="customerProfileForm.insured_last_name"
                                  :rules="[isRequired]"
                                  placeholder="INSURED LAST NAME"
                                  class="w-full"
                                  :disabled="!isProfileUpdateAllow"
                              />
                          </dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">MOBILE NUMBER</dt>
                          <dd>{{ quote.mobile_no }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">EMAIL</dt>
                          <dd>{{ quote.email }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">NATIONALITY</dt>
                          <dd>{{ quote.nationality_id_text }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">DATE OF BIRTH</dt>
                          <dd>{{ quote.dob }}</dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">EMIRATES ID NUMBER</dt>
                          <dd>
                              <x-input
                                  v-model="customerProfileForm.emirates_id_number"
                                  :rules="[isRequired]"
                                  placeholder="EMIRATES ID NUMBER"
                                  class="w-full"
                                  :disabled="!isProfileUpdateAllow"
                              />
                          </dd>
                      </div>
                      <div class="grid sm:grid-cols-2">
                          <dt class="font-medium">EMIRATES ID EXPIRY DATE</dt>
                          <dd>
                              <DatePicker
                                  v-model="customerProfileForm.emirates_id_expiry_date"
                                  :rules="[isRequired]"
                                  placeholder="EMIRATES ID EXPIRY DATE"
                                  :disabled="!isProfileUpdateAllow"
                                  :min-date="new Date()"
                              />
                          </dd>
                      </div>
                  </dl>
                  <div class="flex justify-end">
                      <x-button
                          v-if="isProfileUpdateAllow"
                          class="mt-4"
                          color="emerald"
                          size="sm"
                          :loading="customerProfileForm.processing"
                          type="submit"
                      >
                          Update Profile
                      </x-button>
                  </div>
              </div>
          </x-form>
      </div>

      <MemberDetails
          :quote="quote"
          :membersDetails="membersDetails"
          :nationalities="nationalities"
          :memberRelations="memberRelations"
          :quote_type=quoteType
      />

    <QuoteActivities
      :can="can"
      :quote="quote"
      :activities="activities"
      :advisors="advisors"
      :quote-type="quoteType"
    />

    <QuotePayments
      v-if="isBetaUser"
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
      :quote-status-enum="quoteStatusEnum"
    />

    <QuoteDocuments
      :document-types="documentTypes"
      :quote-documents="quote.documents || []"
      :storageUrl="storageUrl"
      :quote="quote"
    />

    <QuotePolicy
      :quote="quote"
      :can="can"
      :quoteStatusesEnum="quoteStatusesEnum"
    />

    <EmbeddedProducts
      :data="embeddedProducts"
      :link="quote.uuid"
      :code="quote.code"
    />

    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />

    <LeadHistory :quote="$page.props.quote" />
  </div>
</template>
