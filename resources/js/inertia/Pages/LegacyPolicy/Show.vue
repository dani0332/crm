<script setup>
import NProgress from 'nprogress';
import LegacyCard from '../LegacyPolicy/Partials/LegacyCard';

const props = defineProps({
  policy: Object,
});

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const notification = useNotifications('toast');
const moveToImcrmModal = ref(false);
const can = permission => useCan(permission);
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
      moveToImcrm(policy.policy?.policy_oid, false);
      moveToImcrmModal.value = false;
    }
    // Add any additional logic for submitting the lead here
  } else {
    notification.warning({
      title: 'Select Lead or Check Radio Button to proceed!',
      position: 'top',
    });
  }
};

const single = ref(true);
const lobLink = ref('');
const lobCode = ref('');
const data = ref([]);

const dynamicTableHeader = computed(() => {
  const defaultTableHeader = [
    { text: 'Id', value: 'id', key: 'id' },
    { text: 'Ref-ID', value: 'uuid', key: 'uuid' },
    { text: 'Customer name', value: 'name', key: 'name' },
    { text: 'Make', value: 'make', key: 'make' },
    { text: 'Model', value: 'model', key: 'model' },
    { text: 'Model Year', value: 'model_Year', key: 'model_Year' },
    { text: 'Destination', value: 'destination', key: 'destination' },
    { text: 'Salary band', value: 'salary_band', key: 'salary_band' },
    {
      text: 'Landlord or Tenant',
      value: 'landlord_or_tenant',
      key: 'landlord_or_tenant',
    },
    {
      text: 'Apartment or Villa',
      value: 'apartment_or_villa',
      key: 'apartment_or_villa',
    },
    { text: 'Breed', value: 'breed', key: 'breed' },
    {
      text: 'Type of Insurance',
      value: 'type_of_insurance',
      key: 'business_type_of_insurance',
    },
    { text: 'Advisor', value: 'advisor_name', key: 'advisor' },
  ];

  // Exclude columns according if quote type is car
  if (props.policy.quoteType === 'Car') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'salary_band' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'breed' &&
        column.key !== 'business_type_of_insurance',
    );
  }

  // Exclude columns according if quote type is Travel
  if (props.policy.quoteType === 'Travel') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'model_Year' &&
        column.key !== 'salary_band' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'breed' &&
        column.key !== 'business_type_of_insurance' &&
        column.key !== 'make' &&
        column.key !== 'model',
    );
  }

  // Exclude columns according if quote type is Health
  if (props.policy.quoteType === 'Health') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'model_Year' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'breed' &&
        column.key !== 'business_type_of_insurance' &&
        column.key !== 'make' &&
        column.key !== 'model',
    );
  }

  // Exclude columns according if quote type is Home
  if (props.policy.quoteType === 'Home') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'salary_band' &&
        column.key !== 'model_Year' &&
        column.key !== 'advisor' &&
        column.key !== 'breed' &&
        column.key !== 'business_type_of_insurance' &&
        column.key !== 'make' &&
        column.key !== 'model',
    );
  }

  // Exclude columns according if quote type is Pet
  if (props.policy.quoteType === 'Pet') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'salary_band' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'model' &&
        column.key !== 'business_type_of_insurance' &&
        column.key !== 'model_Year' &&
        column.key !== 'make',
    );
  }

  // Exclude columns according if quote type is Cycle / Bike
  if (props.policy.quoteType === 'Cycle' || props.policy.quoteType === 'Bike') {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'salary_band' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'breed' &&
        column.key !== 'business_type_of_insurance',
    );
  }

  // Exclude columns according if quote type is Business / Life
  if (
    props.policy.quoteType === 'Business' ||
    props.policy.quoteType === 'Life'
  ) {
    return defaultTableHeader.filter(
      column =>
        column.key !== 'destination' &&
        column.key !== 'salary_band' &&
        column.key !== 'landlord_or_tenant' &&
        column.key !== 'apartment_or_villa' &&
        column.key !== 'breed' &&
        column.key !== 'model' &&
        column.key !== 'model_Year' &&
        column.key !== 'make',
    );
  }
  return defaultTableHeader;
});

const kycDetails = computed(() => {
  return props.policy.customer?.profile_data?.map(item => {
    if (item.title.includes('Profession or Job Title')) {
      return { ...item, title: 'KYC Requirement - Profession or Job Title' };
    }
    if (item.title.includes('Name of Organization')) {
      return { ...item, title: 'KYC Requirement - Name of Organization' };
    } else if (item.title.includes('Name of Organisation')) {
      return { ...item, title: 'KYC Requirement - Name of Organization' };
    }
    if (item.title.includes('Exact Job Title')) {
      return { ...item, title: 'KYC Requirement - Exact Job Title' };
    }
  });
});
/* payments start */
const calculateSum = (items, propertyName) => {
  if (!items || items.length === 0) {
    return 0;
  }
  return items.reduce((acc, item) => {
    return acc + (item[propertyName] || 0);
  }, 0);
};
const calculateTotalCustomerPayable = computed(() => {
  return calculateSum(props.policy.installments, 'customer_payable');
});
const calculateTax = computed(() => {
  return calculateSum(props.policy.installments, 'tax');
});
const calculateGrossPremium = computed(() => {
  return calculateSum(props.policy.installments, 'gross_premium');
});
const installmentsTableHeader = [
  { text: 'Description', value: 'comment' },
  { text: 'Gross Premium', value: 'gross_premium' },
  { text: 'Date From', value: 'date_from' },
  { text: 'Date To', value: 'date_to' },
  { text: 'Due Date', value: 'due_date' },
  { text: 'Collects', value: 'collects' },
  { text: 'COMM', value: 'comm' },
  { text: 'Commision Sum', value: 'commission_sum' },
  { text: 'Discount', value: 'discount' },
  { text: 'Tax', value: 'tax' },
  { text: 'Customer Payable', value: 'customer_payable' },
];
/* payments ends */
const moveToImcrm = async (policy_oid, validateAll = true) => {
  try {
    NProgress.start();
    const response = await axios.post('/legacy-policy/move-to-imcrm', {
      policy_oid: policy_oid,
      validateAll: validateAll,
      isInertia: true,
    });
    NProgress.done();
    if (response?.data.status == 201) {
      notification.success({
        title: response.data.message,
        position: 'top',
      });
      router.reload({
        preserveScroll: true,
      });
    } else if (response?.data.status == 400) {
      notification.error({
        title: response.data.message,
        position: 'top',
      });
      router.reload({
        preserveScroll: true,
      });
    } else {
      if (response?.data.type == 'policy_number') {
        moveToImcrmModal.value = true;
        lobLink.value = response?.data.data[0].link;
        lobCode.value = response?.data.data[0].code;
        single.value = true;
      } else if (response?.data.type == 'email') {
        single.value = false;
        data.value = response.data.data;
        moveToImcrmModal.value = true;
      } else {
        notification.success({
          title: response.data,
          position: 'top',
        });
      }
    }
  } catch (err) {}
};
const dateFormat = date => {
  if (date) {
    if (date.$date && date.$date.$numberLong) {
      date = formatDate(date);
    }
    return useDateFormat(date, 'DD-MM-YYYY').value;
  }
  return null;
};
</script>

<template>
  <div>
    <Head title="Legacy Policy" />

    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Legacy Policy Detail</h2>

      <div class="flex gap-2">
        <x-tooltip placement="bottom" v-if="policy?.moved_to_imcrm">
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
            @click="moveToImcrm(policy.policy?.policy_oid)"
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
          @click="moveToImcrm(policy.policy?.policy_oid)"
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
              <dt class="font-medium">Ref-ID</dt>
              <dd v-if="policy?.imcrm_link">
                <a
                  :href="policy?.imcrm_link"
                  class="text-primary-500 hover:underline"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  {{
                    policy?.code ||
                    `REF-${policy?.imcrm_link?.match(/\/([^/]+)$/)?.[1]}`
                  }}
                </a>
              </dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Created By</dt>
              <dd>{{ policy?.moved_to_imcrm_by }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">Created Date</dt>
              <dd>{{ dateFormat(policy?.moved_to_imcrm_date) }}</dd>
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
            <dt class="font-medium">Policy Number</dt>
            <dd>{{ policy.policy?.policy_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Start Date</dt>
            <dd>{{ dateFormat(policy.policy?.start_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy End Date</dt>
            <dd>{{ dateFormat(policy.policy?.end_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Premium</dt>
            <dd>{{ policy.premium }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Sales Person</dt>
            <dd>
              {{
                policy.policy?.renewer_person == null
                  ? policy?.quote?.broker
                  : policy.policy?.renewer_person
              }}
            </dd>
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
            <dd>{{ policy?.customer?.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Mobile Number</dt>
            <dd>
              {{ policy?.customer?.mobile_phone }}
            </dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Phone Number</dt>
            <dd>
              {{ policy?.customer?.phone }}
            </dd>
          </div>
          <div v-for="profile_data in kycDetails">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">
                {{ profile_data?.title }}
              </dt>
              <dd class="break-words">{{ profile_data?.value }}</dd>
            </div>
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

      <!-- payments start -->
      <template v-if="can(permissionEnum.LEGACY_INSTALLMENTS)">
        <div class="mt-6">
          <h3 class="font-semibold text-primary-800">Installments</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <DataTable
          table-class-name="tablefixed"
          :headers="installmentsTableHeader"
          :items="policy.installments || []"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-comment="{ comment }">
            {{ comment ? comment : '' }}
          </template>
          <template #item-date_from="{ date_from }">
            {{ dateFormat(date_from) }}
          </template>
          <template #item-date_to="{ date_to }">
            {{ dateFormat(date_to) }}
          </template>
          <template #item-due_date="{ due_date }">
            {{ dateFormat(due_date) }}
          </template>
          <template #item-tax="{ tax }"> {{ tax.toFixed(2) }} AED </template>
          <template #item-comm="{ comm }"> {{ comm.toFixed(2) }}% </template>
          <template #item-commission_sum="{ commission_sum }">
            {{ commission_sum.toFixed(2) }} AED
          </template>
          <template #item-discount="{ discount }">
            {{ discount.toFixed(2) }} AED
          </template>
          <template #item-gross_premium="{ gross_premium }">
            {{ gross_premium.toFixed(2) }} AED
          </template>
          <template #item-customer_payable="{ customer_payable }">
            {{ customer_payable.toFixed(2) }} AED
          </template>
        </DataTable>
        <!-- Display the total customer payable outside the DataTable -->
        <table>
          <tbody>
            <tr>
              <th>Total Gross Premium:</th>
              <td class="custom-table">
                {{ calculateGrossPremium.toFixed(2) }} AED
              </td>
              <th>Total Tax:</th>
              <td class="custom-table">{{ calculateTax.toFixed(2) }} AED</td>
              <th>Total Customer Payable:</th>
              <td class="custom-table">
                {{ calculateTotalCustomerPayable.toFixed(2) }} AED
              </td>
            </tr>
          </tbody>
        </table>
      </template>
      <!-- Invoices -->
      <template v-if="can(permissionEnum.LEGACY_INVOICES)">
        <div class="mt-6">
          <h3 class="font-semibold text-primary-800">Invoices</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div class="row-with-scroll">
          <LegacyCard :legacy="policy.invoices" type="multiple" />
        </div>
      </template>

      <!-- Payments -->
      <template v-if="can(permissionEnum.LEGACY_PAYMENTS)">
        <x-divider class="mb-4 mt-1" />
        <div class="mt-6">
          <h3 class="font-semibold text-primary-800">Payments</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div class="row-with-scroll">
          <LegacyCard :legacy="policy.payments" type="multiple" />
        </div>
      </template>

      <!-- Other Legacy Details -->
      <template v-if="can(permissionEnum.LEGACY_OTHER_DETAILS)">
        <x-divider class="mb-4 mt-1" />
        <div class="mt-6">
          <h3 class="font-semibold text-primary-800">Other Legacy Details</h3>
          <x-divider class="mb-4 mt-1" />
        </div>
        <div class="scrollable-container">
          <LegacyCard
            v-if="policy.customer && Object.keys(policy.customer).length > 0"
            :legacy="policy.customer"
            type="single"
            title="CUSTOMER"
            :policy="policy"
          />

          <LegacyCard
            v-if="policy.quote && Object.keys(policy.quote).length > 0"
            :legacy="policy.quote"
            type="single"
            title="QUOTE"
            :policy="policy"
          />
          <LegacyCard
            v-if="policy.renewal && Object.keys(policy.renewal).length > 0"
            :legacy="policy.renewal"
            type="single"
            title="RENEWAL"
            :policy="policy"
          />
          <LegacyCard
            v-if="policy.claims && Object.keys(policy.claims).length > 0"
            :legacy="policy.claims"
            type="multiple"
            title="CLAIM"
            :policy="policy"
          />

          <LegacyCard
            v-if="policy.objects && Object.keys(policy.objects).length > 0"
            :legacy="policy.objects"
            type="multiple"
            title="OBJECT"
            :policy="policy"
          />
        </div>
      </template>
    </div>

    <x-modal
      v-model="moveToImcrmModal"
      size="lg"
      :title="`${!single ? 'Lead Detail' : ''}`"
      show-close
      backdrop
    >
      <div v-if="single">
        This policy already exists in IMCRM as REF:ID
        <Link :href="`${lobLink}`" class="text-primary-500 hover:underline">
          {{ lobCode }}
        </Link>
      </div>
      <p v-if="!single">Do you want to use existing details?</p>
      <DataTable
        v-model:items-selected="quotesSelected"
        table-class-name="tablefixed"
        :headers="dynamicTableHeader"
        :items="data || []"
        border-cell
        hide-rows-per-page
        hide-footer
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
          ><strong>No matches found. Create new IMCRM lead</strong></label
        >
      </div>

      <template #actions>
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
      </template>
    </x-modal>
  </div>
</template>
<style scoped>
.row-with-scroll {
  display: flex;
  overflow-x: auto;
  white-space: nowrap;
  width: 100%;
}
.scrollable-container {
  max-height: 400px;
  overflow-y: auto;
  display: flex;
  gap: 20px;
}

.custom-table {
  padding-right: 20px;
}
</style>
