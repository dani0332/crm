<script setup>
defineProps({
  reportsData: Array,
  quoteTypeIdEnum: Array,
  allowedLobs: Object,
});

const page = usePage();

const quoteTypeIdEnum = page.props.quoteTypeIdEnum;
const loader = reactive({
  table: false,
});
let availableFilters = {
  date_assigned: '',
  car_type_insurance_id: '',
  lead_source: '',
  lob: '',
  type_of_plan: '',
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    if (filters.lob) {
      router.visit('/reports/revival-conversion', {
        method: 'get',
        data: filters,
        preserveState: true,
        preserveScroll: true,
        onBefore: () => (loader.table = true),
        onSuccess: () => (loader.table = false),
      });
    }
  } else {
    console.log('Invalid');
  }
}
const tabsArray = ref([
  {
    index: 0,
    label: 'Conversion Rate',
    tooltip:
      'It indicates the effectiveness of revival mails which helps converting revival leads into customers with payment status captured',
    lob: [quoteTypeIdEnum.Car],
  },
  {
    index: 1,
    label: 'Auth To Capture Rate',
    tooltip:
      'This rate offers insight into the efficiency of converting authorized transactions into completed payments',
    lob: [quoteTypeIdEnum.Car],
  },
  {
    index: 2,
    label: 'Response Rate Of Customer',
    tooltip:
      'It offers insight into the effectiveness of your communication outreach efforts, reflecting the responsiveness of the customer',
    lob: [quoteTypeIdEnum.Car, quoteTypeIdEnum.Health],
  },
  {
    index: 3,
    label: 'Transaction Approved Rate',
    tooltip:
      'It indicates the effectiveness of contacting revival leads as it is the percentage of customers who have been converted (lead transaction status as approved) from the number of revival leads (lead source as revived) contacted ',
    lob: [quoteTypeIdEnum.Health],
  },
]);

const tabs = computed(() => {
  const data = tabsArray.value
    .filter(item => item.lob.includes(parseInt(filters.lob)))
    .map((item, index) => ({
      ...item,
      index: index,
    }));

  console.log('data', data);
  return data;
});

function onReset() {
  router.visit('/reports/revival-conversion', {
    method: 'get',
    data: {},
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}
const conversionRateTableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
    tooltip:
      'Each batch contains set of leads receiving email quotes over a period of 7 days',
  },
  {
    text: 'Total number of payments captured',
    value: 'conversion_captured',
    tooltip:
      'It represents the cumulative count of customers whose payment was captured',
  },
  {
    text: 'Total number of Revived',
    value: 'total_revived',
    tooltip: 'It denotes the overall count of leads contacted for revival',
  },
  {
    text: 'Ratio',
    value: 'ratio',
    tooltip:
      '(Total no. of payments captured / Total no. of revival leads uploaded) * 100',
  },
];

const tableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
    tooltip:
      'Each batch contains set of leads receiving email quotes over a period of 7 days',
  },
  {
    text: 'Total number of payments captured',
    value: 'captured',
    tooltip:
      'It represents the cumulative count of customers whose payment was captured',
  },
  {
    text: 'Total number of payments authorized',
    value: 'authorized',
    tooltip:
      'It signifies the total number of payments that have been authorized by the customer but not captured',
  },
  {
    text: 'Auth to Capture Rate',
    value: 'ratio',
    tooltip:
      '(Total number of payment status: Captured / Total number of payments authorized) * 100',
  },
];

const emailConversionReportTableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
    tooltip:
      'Each batch contains set of leads receiving email quotes over a period of 7 days',
  },
  {
    text: 'Total Replied',
    value: 'reply_received_count',
    tooltip:
      'It indicates the count of responses (requested for an advisor or replied to the mail) received from the revival lead to our revival mails',
  },
  {
    text: 'Total Sent',
    value: 'email_sent_count',
    tooltip: 'It represents the total count of revival leads contacted',
  },
  {
    text: 'Response rate of customer',
    value: 'ratio',
    tooltip: '(Total number of replies /Total number of emails sent) x 100',
  },
];
const transactionApprovedRateTableHeader = [
  {
    text: 'Batch',
    value: 'quote_batch_id',
    tooltip:
      'Each batch contains set of leads receiving email quotes over a period of 7 days',
  },
  {
    text: 'Total Number of Transations Approved',
    value: 'transaction_approved',
    tooltip:
      'It refers to the total count of customers that have successfully paid and their payment is approved',
  },
  {
    text: 'Total Number of revived',
    value: 'email_sent_count',
    tooltip:
      'It indicates the count of responses (requested for an advisor or replied to the mail) received from the leads having lead source as revival',
  },
  {
    text: 'Ratio',
    value: 'ratio',
    tooltip:
      'Total number of transaction approved/ Total number of revived) * 100',
  },
];
</script>

<template>
  <div>
    <Head title="Revival Conversion Report" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.lob"
          label="LOB"
          placeholder="Select LOB"
          :options="
            Object.keys(allowedLobs).map(key => ({
              value: key,
              label: allowedLobs[key],
            }))
          "
          class="w-full"
          :single="true"
        />
        <x-select
          v-model="filters.lead_source"
          label="Lead Source"
          placeholder="Lead Source"
          :options="[
            { value: 'REVIVAL', label: 'Revival' },
            { value: 'REVIVAL_REPLIED', label: 'Revival Replied' },
            { value: 'REVIVAL_PAID', label: 'Revival Paid' },
          ]"
          class="w-full"
        />
        <x-select
          v-model="filters.car_type_insurance_id"
          label="Type Of Car Insurance"
          placeholder="Type Of Car Insurance"
          :options="[
            { value: '1', label: 'Comprehensive' },
            { value: '2', label: 'TPL' },
          ]"
          class="w-full"
          :disabled="filters.lob != 1"
        />
        <DatePicker
          v-model="filters.date_assigned"
          name="created_at"
          label="Date Assigned"
          class="w-full"
        />

        <x-select
          v-model="filters.type_of_plan"
          label="Type Of Plan"
          placeholder="Type Of Plan"
          :options="[
            { value: '1', label: 'Entry Level' },
            { value: '2', label: 'Good' },
            { value: '3', label: 'Best' },
          ]"
          :disabled="filters.lob != 3"
          class="w-full"
        />
      </div>

      <div class="flex justify-end gap-3 mb-4">
        <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
        <x-button size="sm" color="primary" @click.prevent="onReset">
          Reset
        </x-button>
      </div>
    </x-form>

    <TabGroup>
      <TabList
        class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full justify-center"
      >
        <Tab
          v-for="{ index, label, tooltip } in tabs"
          :key="index"
          v-slot="{ selected }"
        >
          <x-tooltip placement="top">
            <button
              :class="[
                'underline decoration-dotted rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                selected
                  ? 'bg-white shadow text-primary-600'
                  : 'hover:bg-white/50',
              ]"
            >
              {{ label }}
            </button>
            <template #tooltip>
              <span>{{ tooltip }}</span>
            </template>
          </x-tooltip>
        </Tab>
      </TabList>

      <TabPanels class="mt-2">
        <!-- conversion rate  -->
        <TabPanel v-if="filters.lob == quoteTypeIdEnum.Car">
          <DataTable
            table-class-name="tablefixed overflow-hidden-table"
            :loading="loader.table"
            :headers="conversionRateTableHeader"
            :items="reportsData.conversionRate || []"
            border-cell
            hide-rows-per-page
          >
            <template #header-quote_batch_id="header">
              <x-tooltip placement="top">
                <span class="underline decoration-dotted">{{
                  header.text
                }}</span>
                <template #tooltip>
                  <span
                    class="whitespace-break-spaces !normal-case"
                    style="margin-top: 50px"
                  >
                    {{ header.tooltip }}
                  </span>
                </template>
              </x-tooltip>
            </template>

            <template #header-conversion_captured="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-total_revived="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-ratio="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
          </DataTable>
        </TabPanel>

        <!-- auth to capture rate -->
        <TabPanel v-if="filters.lob == quoteTypeIdEnum.Car">
          <DataTable
            table-class-name="tablefixed overflow-hidden-table"
            :loading="loader.table"
            :headers="tableHeader"
            :items="reportsData.leadConversionReport || []"
            border-cell
            hide-rows-per-page
          >
            <template #header-quote_batch_id="header">
              <x-tooltip placement="top">
                <span class="underline decoration-dotted">{{
                  header.text
                }}</span>
                <template #tooltip>
                  <span
                    class="whitespace-break-spaces !normal-case"
                    style="margin-top: 50px"
                  >
                    {{ header.tooltip }}
                  </span>
                </template>
              </x-tooltip>
            </template>

            <template #header-captured="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-authorized="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-ratio="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
          </DataTable>
        </TabPanel>

        <!-- response rate of customer car-->
        <TabPanel v-if="filters.lob == quoteTypeIdEnum.Car">
          <DataTable
            table-class-name="tablefixed overflow-hidden-table"
            :loading="loader.table"
            :headers="emailConversionReportTableHeader"
            :items="reportsData.emailConversionReportCar || []"
            border-cell
            hide-rows-per-page
          >
            <template #header-quote_batch_id="header">
              <x-tooltip placement="top">
                <span class="underline decoration-dotted">{{
                  header.text
                }}</span>
                <template #tooltip>
                  <span
                    class="whitespace-break-spaces !normal-case"
                    style="margin-top: 50px"
                  >
                    {{ header.tooltip }}
                  </span>
                </template>
              </x-tooltip>
            </template>

            <template #header-reply_received_count="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-email_sent_count="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
            <template #header-ratio="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
          </DataTable>
        </TabPanel>
        <!-- response rate of customer Health -->
        <TabPanel v-if="filters.lob == quoteTypeIdEnum.Health">
          <DataTable
            table-class-name="tablefixed overflow-hidden-table"
            :loading="loader.table"
            :headers="emailConversionReportTableHeader"
            :items="reportsData.emailConversionReportHealth || []"
            border-cell
            hide-rows-per-page
          >
            <template #header-quote_batch_id="header">
              <x-tooltip placement="top">
                <span class="underline decoration-dotted">{{
                  header.text
                }}</span>
                <template #tooltip>
                  <span
                    class="whitespace-break-spaces !normal-case"
                    style="margin-top: 50px"
                  >
                    {{ header.tooltip }}
                  </span>
                </template>
              </x-tooltip>
            </template>

            <template #header-reply_received_count="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-email_sent_count="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
            <template #header-ratio="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
          </DataTable>
        </TabPanel>
        <!-- transaction approved  -->
        <TabPanel v-if="filters.lob == quoteTypeIdEnum.Health">
          <DataTable
            table-class-name="tablefixed overflow-hidden-table"
            :loading="loader.table"
            :headers="transactionApprovedRateTableHeader"
            :items="reportsData.transactionApprovedReport || []"
            border-cell
            hide-rows-per-page
          >
            <template #header-quote_batch_id="header">
              <x-tooltip placement="top">
                <span class="underline decoration-dotted">{{
                  header.text
                }}</span>
                <template #tooltip>
                  <span
                    class="whitespace-break-spaces !normal-case"
                    style="margin-top: 50px"
                  >
                    {{ header.tooltip }}
                  </span>
                </template>
              </x-tooltip>
            </template>

            <template #header-transaction_approved="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>

            <template #header-email_sent_count="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
            <template #header-ratio="header">
              <div class="customize-header underline">
                <x-tooltip placement="bottom" class="underline">
                  <span
                    class="font-semibold tracking-widest uppercase underline decoration-dotted decoration-primary-600"
                  >
                    {{ header.text }}
                  </span>
                  <template #tooltip>
                    <div class="whitespace-normal normal-case">
                      {{ header.tooltip }}
                    </div>
                  </template>
                </x-tooltip>
              </div>
            </template>
          </DataTable>
        </TabPanel>
      </TabPanels>
    </TabGroup>
  </div>
</template>
