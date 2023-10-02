<script setup>
const props = defineProps({
  policy: Object,
});

const moveToImcrmModal = ref(false);
const itemCount = ref(false);

const getS3TempUrl = async file => {
  try {
    const response = await axios.post('/legacy-policy/get-s3-temp-url', {
      fileName: file,
    });
    // Check if the request was successful and the response contains the URL
    if (response.status === 200 && response.data.url) {
      // Open the URL in a new tab
      window.open(response.data.url, '_blank');
    } else {
      notification.error({
        title: response.data.error,
        position: 'top',
      });
    }
  } catch (error) {
    notification.error({
      title: error,
      position: 'top',
    });
    console.error('An error occurred:', error);
  }
};

const selectedLead = ref(null);

const setSelectedLead = document => {
  selectedLead.value = document;
};

const submitLead = policy => {
  if (selectedLead.value) {
    // Open the URL in a new tab
    if (selectedLead.value.link != 'new') {
      window.open(selectedLead.value.link, '_blank');
    } else {
      moveToImcrm(policy.policy?.policy_no, false);
      moveToImcrmModal.value = false;
    }

    console.log('Link URL:', selectedLead.value.link);
    console.log('Selected Document:', selectedLead.value.code);
    // Add any additional logic for submitting the lead here
  } else {
    console.log('No document selected. Cannot submit lead.');
  }
};

const notification = useNotifications('toast');
const single = ref(true);
const lobLink = ref('');
const lobCode = ref('');
const data = ref([]);

const dynamicTableHeader = computed(() => {
  const defaultTableHeader = [
    { text: 'Id', value: 'id' },
    { text: 'Ref-ID', value: 'uuid' },
    { text: 'Customer name', value: 'name' },
    { text: 'Make', value: 'make' },
    { text: 'Model', value: 'model' },
    { text: 'Model Year', value: 'model_Year' },
    { text: 'Destination', value: 'destination' },
    { text: 'Salary band', value: 'salary_band' },
    { text: 'Landlord or Tenant', value: 'landlord_or_tenant' },
    { text: 'Apartment or Villa', value: 'apartment_or_villa' },
    { text: 'Breed', value: 'breed' },
    { text: 'Type of Insurance', value: 'business_type_of_insurance.code' },
    { text: 'Advisor', value: 'advisor' },
  ];
  console.log(props.policy.quoteType);

  // Exclude columns according if quote type is car
  if (props.policy.quoteType === 'Car') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'salary_band' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'breed' &&
        column.value !== 'business_type_of_insurance.code',
    );
  }

  // Exclude columns according if quote type is Travel
  if (props.policy.quoteType === 'Travel') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'model_Year' &&
        column.value !== 'salary_band' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'breed' &&
        column.value !== 'business_type_of_insurance.code' &&
        column.value !== 'make' &&
        column.value !== 'model',
    );
  }

  // Exclude columns according if quote type is Health
  if (props.policy.quoteType === 'Health') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'model_Year' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'breed' &&
        column.value !== 'business_type_of_insurance.code' &&
        column.value !== 'make' &&
        column.value !== 'model',
    );
  }

  // Exclude columns according if quote type is Home
  if (props.policy.quoteType === 'Home') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'salary_band' &&
        column.value !== 'model_Year' &&
        column.value !== 'advisor' &&
        column.value !== 'breed' &&
        column.value !== 'business_type_of_insurance.code' &&
        column.value !== 'make' &&
        column.value !== 'model',
    );
  }

  // Exclude columns according if quote type is Pet
  if (props.policy.quoteType === 'Pet') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'salary_band' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'model' &&
        column.value !== 'business_type_of_insurance.code' &&
        column.value !== 'model_Year' &&
        column.value !== 'make',
    );
  }

  // Exclude columns according if quote type is Cycle / Bike
  if (props.policy.quoteType === 'Cycle' || props.policy.quoteType === 'Bike') {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'salary_band' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'breed' &&
        column.value !== 'business_type_of_insurance.code',
    );
  }

  // Exclude columns according if quote type is Business / Life
  if (
    props.policy.quoteType === 'Business' ||
    props.policy.quoteType === 'Life'
  ) {
    return defaultTableHeader.filter(
      column =>
        column.value !== 'destination' &&
        column.value !== 'salary_band' &&
        column.value !== 'landlord_or_tenant' &&
        column.value !== 'apartment_or_villa' &&
        column.value !== 'breed' &&
        column.value !== 'model' &&
        column.value !== 'model_Year' &&
        column.value !== 'make',
    );
  }
  return defaultTableHeader;
});

const moveToImcrm = async (policyNumber, validateAll = true) => {
  try {
    const response = await axios.post('/legacy-policy/move-to-imcrm', {
      policyNumber: policyNumber,
      validateAll: validateAll,
      isInertia: true,
    });
    console.log(response);
    if (response?.data.status == 201) {
      notification.success({
        title: response.data.message,
        position: 'top',
      });
      router.reload({
        preserveScroll: true,
      });
    } else {
      if (response?.data.type == 'policy_number') {
        console.log(response?.data.data[0].code);
        moveToImcrmModal.value = true;
        lobLink.value = response?.data.data[0].link;
        lobCode.value = response?.data.data[0].code;
        single.value = true;
      } else {
        single.value = false;
        data.value = response.data.data;
        moveToImcrmModal.value = true;
      }
    }
  } catch (err) {}
};
</script>

<template>
  <div>
    <Head title="Legacy Policy" />

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Legacy Policy Detail</h2>

      <div class="flex gap-2">
        <x-tooltip position="bottom" v-if="policy?.moved_to_imcrm">
          <label
            class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-600 mb-0.5"
          >
          </label>
          <template #tooltip
            >Lead was already moved to IMCRM. Please click on the reference ID
            located in the IMCRM details to make changes to the lead.</template
          >
          <x-button
            v-show="true"
            size="sm"
            color="#ff5e00"
            :disabled="policy?.moved_to_imcrm"
            @click="moveToImcrm(policy.policy?.policy_no)"
          >
            Move to IMCRM
          </x-button>
        </x-tooltip>
        <x-button
          v-else
          v-show="true"
          size="sm"
          color="#ff5e00"
          :disabled="policy?.moved_to_imcrm"
          @click="moveToImcrm(policy.policy?.policy_no)"
        >
          Move to IMCRM
        </x-button>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div v-if="policy?.imcrm_link">
        <div class="mt-6">
          <h3 class="font-semibold text-primary-800">IMCRM Details</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Reference Id</dt>
              <dd v-if="policy?.imcrm_link">
                <a
                  :href="policy?.imcrm_link"
                  class="text-primary-500 hover:underline"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  REF-{{ policy?.imcrm_link.match(/\/([^/]+)$/)?.[1] }}</a
                >
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Created By</dt>
              <dd>{{ policy?.moved_to_imcrm_by }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Created Date</dt>
              <dd>{{ policy?.moved_to_imcrm_date }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Policy Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Insurer</dt>
            <dd>{{ policy?.policy?.insurer }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ policy.policy?.policy_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Start Date</dt>
            <dd>{{ policy.policy?.start_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy End Date</dt>
            <dd>{{ policy.policy?.end_date }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Premium</dt>
            <dd>{{ policy.premium }}</dd>
          </div>

          <!-- <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Sales Person</dt>
            <dd>{{ policy.policy?.renewer_person }}</dd>
          </div> -->
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Sales Person</dt>
            <dd>{{ policy.policy?.coverage }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Customer Name</dt>
            <dd>{{ policy.customer?.name }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Email</dt>
            <dd>{{ policy.customer?.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Mobile Number</dt>
            <dd>{{ policy.customer?.mobile_phone }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Phone Number</dt>
            <dd>{{ policy.customer?.phone }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">
              KYC Requirement - Profession or Job Title
            </dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">KYC Requirement - Name of Organisation</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">KYC Requirement - Exact Job Title</dt>
            <dd>{{ quote?.pet_quote?.policy_number }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Documents</h3>
        <x-divider class="mb-4 mt-1" />
        Quotes Documents
        <ul>
          <li v-for="quoteDocument in policy?.documents?.quote">
            <x-button
              size="xs"
              color="blue"
              class="mb-1"
              outlined
              @click.prevent="getS3TempUrl(quoteDocument.document_path)"
            >
              {{ quoteDocument.document_name }}
            </x-button>
          </li>
        </ul>

        <x-divider class="mb-4 mt-1" />
        Policy Documents
        <ul>
          <li v-for="quoteDocument in policy?.documents?.policy">
            <x-button
              size="xs"
              color="blue"
              class="mb-1"
              outlined
              @click.prevent="getS3TempUrl(quoteDocument.document_path)"
            >
              {{ quoteDocument.document_name }}
            </x-button>
          </li>
        </ul>
        <x-divider class="mb-4 mt-1" />
        Customer Documents
        <ul>
          <li v-for="quoteDocument in policy?.documents?.customer">
            <x-button
              size="xs"
              color="blue"
              class="mb-1"
              outlined
              @click.prevent="getS3TempUrl(quoteDocument.document_path)"
            >
              {{ quoteDocument.document_name }}
            </x-button>
          </li>
        </ul>
      </div>
      <div class="text-sm"></div>
    </div>

    <x-modal v-model="moveToImcrmModal" size="lg" show-close backdrop>
      <div v-if="single">
        This policy already exists in IMCRM as REF:ID
        <Link :href="`${lobLink}`" class="text-primary-500 hover:underline">
          {{ lobCode }}
        </Link>
      </div>

      <template #header v-if="!single"> Lead Detail </template>
      <p v-if="!single">Do you want to use existing details?</p>
      <template #actions> </template>

      <DataTable
        v-model:items-selected="quotesSelected"
        table-class-name="tablefixed"
        :headers="dynamicTableHeader"
        :items="data || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
        v-if="!single"
      >
        <template #item-id="{ id, link }">
          <input
            type="radio"
            :id="'radio-document-' + id"
            :name="'quote-document-radio'"
            class="mr-2"
            @click="setSelectedLead({ link: link })"
          />
        </template>
        <template #item-uuid="{ link, code }">
          <Link :href="`${link}`" class="text-primary-500 hover:underline">
            {{ code }}
          </Link>
        </template>
        <template #item-advisor="{ advisor }">
          {{ advisor?.name }}
        </template>
        <template #item-name="{ first_name, last_name }">
          {{ first_name + ' ' + last_name }}
        </template>
      </DataTable>
      <div>
        <input
          type="radio"
          :id="'radio-document-0'"
          :name="'quote-document-radio'"
          class="ml-4 mr-1 mt-3"
          @click="setSelectedLead({ link: 'new' })"
        />
        <label for="radio-create-new"
          ><strong>No matches found. Create new IMCRN lead</strong></label
        >
      </div>

      <div class="flex justify-end my-4 gap-3 mb-4">
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          @click="submitLead(policy)"
        >
          Continue
        </x-button>
        <x-button size="sm" color="primary" @click="moveToImcrmModal = false">
          Cancel
        </x-button>
      </div>
    </x-modal>
  </div>
</template>
