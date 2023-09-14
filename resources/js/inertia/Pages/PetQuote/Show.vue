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
  customerTypeEnum: Array
});

const page = usePage();
const hasRole = role => useHasRole(role);
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
  leadDuplicateForm.post('/quotes/createDuplicate', {
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

const disableCustProfFields = computed(() => {
    return (hasRole(page.props.rolesEnum.PA) || hasRole(page.props.rolesEnum.OE)) ? false : true;
});

const customerProfileForm = useForm({
    insured_first_name: page.props.quote.insured_first_name || '',
    insured_last_name: page.props.quote.insured_last_name || '',
    emirates_id_number: page.props.quote.emirates_id_number || null,
    emirates_id_expiry_date: page.props.quote.emirates_id_expiry_date || null,
});

const updateProfileDetails = () => {

    let data = {
        customer_id: page.props.quote.customer_id,
        insured_first_name: customerProfileForm.insured_first_name,
        insured_last_name: customerProfileForm.insured_last_name,
        emirates_id_number: customerProfileForm.emirates_id_number,
        emirates_id_expiry_date: customerProfileForm.emirates_id_expiry_date,
    };

    axios.post(route('update-customer-profile'), data).then(response => {
        if (response.status == 200) {
            notification.success({
                title: 'Customer profile details update successfully',
                position: 'top',
            });
        } else {
            notification.error({
                title: 'Customer profile details not updated',
                position: 'top',
            });
        }
    });
}

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
          :href="`/personal-quotes/pet/${quote.uuid}/edit`"
        >
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>

        <Link
          v-if="can(permissionsEnum.PetQuotesList)"
          href="/personal-quotes/pet"
          preserve-scroll
        >
          <x-button size="sm" color="primary" tag="div"> Pet Quotes </x-button>
        </Link>
      </div>

      <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
        <template #header> Duplicate Lead </template>
        <x-form @submit="onCreateDuplicate" :auto-focus="false">
          <div class="grid gap-4">
            <x-select
              v-model="leadDuplicateForm.lob_team"
              label="LOBs"
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
            <x-select
              v-model="leadDuplicateForm.lob_team_sub_selection"
              label="Reason"
              :rules="[isRequired]"
              class="w-full"
              :options="[
                { value: 'new_enquiry', label: 'New enquiry' },
                { value: 'record_only', label: 'Record purposes only' },
              ]"
            />

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
    </div>

      <div class="p-4 rounded shadow mb-6 bg-white">
          <div>
              <h3 class="font-semibold text-primary-800 text-lg">{{ quote.customer_type !== page.props.customerTypeEnum.Individual ? 'Customer ' : 'Entity '}} Profile</h3>
              <x-divider class="mb-4 mt-1" />
          </div>
          <div class="text-sm">
              <dl v-if="quote.customer_type !== page.props.customerTypeEnum.Individual" class="grid md:grid-cols-2 gap-x-6 gap-y-4">
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
                              placeholder="INSURED FIRST NAME"
                              class="w-full"
                              :disabled="!disableCustProfFields"
                          />
                      </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">INSURED LAST NAME</dt>
                      <dd>
                          <x-input
                              v-model="customerProfileForm.insured_last_name"
                              placeholder="INSURED LAST NAME"
                              class="w-full"
                              :disabled="!disableCustProfFields"
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
                              placeholder="EMIRATES ID NUMBER"
                              class="w-full"
                              :disabled="!disableCustProfFields"
                          />
                      </dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">EMIRATES ID EXPIRY DATE</dt>
                      <dd>
                          <DatePicker
                              v-model="customerProfileForm.emirates_id_expiry_date"
                              placeholder="EMIRATES ID EXPIRY DATE"
                              :disabled="!disableCustProfFields"
                          />
                      </dd>
                  </div>
              </dl>
              <dl v-if="quote.customer_type === page.props.customerTypeEnum.Entity" class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                  <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">FIRST NAME</dt>
                      <dd>{{ quote.first_name }}</dd>
                  </div>
                  <div class="grid sm:grid-cols-2">
                      <dt class="font-medium">LAST NAME</dt>
                      <dd>{{ quote.last_name }}</dd>
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
                      <dt class="font-medium">COMPANY NAME</dt>
                      <dd>{{ quote.email }}</dd>
                  </div>
              </dl>
              <div class="flex justify-end">
                  <x-button
                      v-if="disableCustProfFields"
                      class="mt-4"
                      color="emerald"
                      size="sm"
                      @click="updateProfileDetails"
                  >
                      Update Profile
                  </x-button>
              </div>
          </div>
      </div>

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
    />

    <LeadHistory :quote="quote" />

    <AuditLogs :quote-type="quoteType" :id="$page.props.quote.id" />
  </div>
</template>
