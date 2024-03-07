<script setup>
import LegacyCard from '../LegacyPolicy/Partials/LegacyCard';
import DocumentListing from './Partials/DocumentListing.vue';
import {formatDate} from '../../Composables/utilities.js';
const props = defineProps({
  policy: Object,
});

const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const moveToImcrmModal = ref(false);
const can = permission => useCan(permission);

const notification = useNotifications('toast');
const single = ref(true);
const lobLink = ref('');
const lobCode = ref('');
const data = ref([]);
const tableHeader = [
  { text: 'Ref-ID', value: 'uuid' },
  { text: 'Customer name', value: 'name' },
  { text: 'Make', value: 'make' },
  { text: 'Model', value: 'model' },
  { text: 'Model Year', value: 'model_Year' },
  { text: 'Salary band', value: 'salary_band' },
  { text: 'Landlord or Tenant', value: 'landlord_or_tenant' },
  { text: 'Apartment or Villa', value: 'apartment_or_villa' },
  { text: 'Breed', value: 'breed' },
  { text: 'Type of Insurance', value: 'business_type_of_insurance.code' },
  { text: 'Advisor', value: 'advisor' },
];
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
const moveToImcrm = async policyNumber => {
  try {
    const response = await axios.post('/legacy-policy/move-to-imcrm', {
      policyNumber: policyNumber,
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
        <x-button
          v-show="false"
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
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Reference Id</dt>
            <dd v-if="policy?.imcrm_link">
              <Link
                :href="`${policy?.imcrm_link}`"
                class="text-primary-500 hover:underline"
              >
                Ref
              </Link>
            </dd>
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
            <dt class="font-medium">Insurer</dt>
            <dd>{{ policy?.policy?.insurer }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">POLICY NUMBER</dt>
            <dd>{{ policy.policy?.policy_no }}</dd>
          </div>

          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy Start Date</dt>
            <dd>{{ formatDate(policy.policy?.start_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Policy End Date</dt>
            <dd>{{ formatDate(policy.policy?.end_date) }}</dd>
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
          fixed-checkbox
        >
          <template #item-comment="{ comment }">
            {{ comment ? comment : 'null' }}
          </template>
          <template #item-date_from="{ date_from }">
              {{ formatDate(date_from) }}
          </template>
          <template #item-date_to="{ date_to }">
              {{ formatDate(date_to) }}
          </template>
          <template #item-due_date="{ due_date }">
              {{ formatDate(due_date) }}
          </template>
          <template #item-tax="{ tax }">
              {{ tax }} AED
          </template>
          <template #item-comm="{ comm }">
              {{ comm }}%
          </template>
          <template #item-commission_sum="{ commission_sum }">
              {{ commission_sum }} AED
          </template>
          <template #item-discount="{ discount }">
              {{ discount }} AED
          </template>
          <template #item-gross_premium="{ gross_premium }">
              {{ gross_premium }} AED
          </template>
          <template #item-customer_payable="{ customer_payable }">
              {{ customer_payable }} AED
          </template>
        </DataTable>
        <!-- Display the total customer payable outside the DataTable -->
      <table>
          <tr>
            <th>Total Gross Premium:</th>
            <td class="custom-table">{{ calculateGrossPremium }} AED</td>
            <th>Total Tax:</th>
            <td class="custom-table">{{ calculateTax }} AED</td>
            <th>Total Customer Payable:</th>
            <td class="custom-table">{{ calculateTotalCustomerPayable }} AED</td>
          </tr>
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
        :headers="tableHeader"
        :items="data || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
        v-if="!single"
      >
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

      <div class="flex justify-end my-4 gap-3 mb-4">
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          @click="onPlanFiltersSubmit"
        >
          Apply
        </x-button>
        <x-button size="sm" color="primary" @click.prevent="onPlanFiltersReset">
          Reset
        </x-button>
      </div>
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
