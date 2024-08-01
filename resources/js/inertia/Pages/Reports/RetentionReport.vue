<script setup>

const props = defineProps({
  filterOptions: Object,
  filtersByLob: Object,
  reportData: Object,
  productName: String,
  monthNames: Array,
  retentionReportTooltipEnum: Array,
  isShowBatchColumn: Boolean
});

const page = usePage();
const isDirty = ref(false);
const isMounted = ref(false);
const advisorOptions = ref([]);
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const notification = useToast();
const subteamOptions = ref([]);

const getFiltersObject = () => {
  return {
    lob: props.productName,
    displayBy: '',
    policyExpiryDate: [],
    teams: [],
    advisors: [],
    month: 1,
    page: 1,
    sub_teams: [],
    insurance_type: "",
    type: '',
    advisor_id: ''
  }
};

const displayBy = ref([
  { label: 'Month', value: 'month' },
  { label: 'Batch', value: 'batch' },
]);

const teamOptions = ref([]);

let filters = reactive(getFiltersObject());

const quoteTypesOptions = computed(() => {
  const quoteTypesOptions = [...Object.keys(page.props.filterOptions.lob).map(text => ({
    label: text,
    value: page.props.filterOptions.lob[text],
  })), ];

  return quoteTypesOptions
});

const monthOptions = computed(() => {
  const quoteTypesOptions = [...Object.values(page.props.monthNames).map((text, index) =>({
    label: text,
    value: index+1,
  }))];

  return quoteTypesOptions
});

function onReset() {

}

const canShow = (element) => {
  if(page.props.filtersByLob &&
  page.props.filtersByLob[element]) {
      const lobs = page.props.filtersByLob[element]['lobs'] ?? [];
      if((lobs.length == 0 ||
      (lobs.length != 0 && Object.values(lobs).includes(filters.lob)))) {
          return true;
      }

      return false;
  }

  return true;
}

const loaders = reactive({
  table: false,
  advisorLeadTable: false,
  teamsOptions: false,
  subteamOptions: false,
  advisorOptions: false,
});

const loadTeams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;

  }

  if(!isMounted) {
      filters.advisors = [];
      advisorOptions.value = [];
  }

  loaders.teamsOptions = true;

  axios
    .post(`/reports/fetch-teams-by-lob`, {
      lob: e,
    })
    .then(res => {
      if (res.data.length > 0) {
        teamOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.teamsOptions = false;
    });
};

const loadAdvisorsByLob = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisors-by-lob`, {
      lob: e,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

const onTeamChange = (e, isOnMounted = false) => {
  if(!isOnMounted) {
      filters.advisors = [];
      advisorOptions.value = [];
  }
  loadSubTeams(e);
  loadAdvisors(e);
};


const onLobChange = (e, isOnMounted = false) => {
  if(!isOnMounted) {
      filters.teams = [];
      filters.advisors = [];
      advisorOptions.value = [];
      filters.displayBy= '';
      filters.policyExpiryDate=[];
      filters.teams=[];
      filters.advisors= [];
      filters.month='';
      filters.page=1;
      filters.sub_teams=[];
      filters.insurance_type="";
    }

  if([quoteTypeCodeEnum.Car,
      quoteTypeCodeEnum.Health,
      quoteTypeCodeEnum.CORPLINE,
      quoteTypeCodeEnum.GroupMedical
  ].includes(filters.lob)) {
      loadTeams(e);
  } else {
      loadAdvisorsByLob(e);
  }

  // onSubmit(false)
};

const isDisabled = (element) => {
    if(page.props.filtersByLob &&
      page.props.filtersByLob[element] &&
      filters.lob) {
        const canView = page.props.filtersByLob[element]['can_view'][filters.lob] ?? true;

        if(canView) {
            return true;
        }

        return false;
    }

    return true;
}

const getAdvisorLabel = () => {
    let label = 'Advisors'
    if ([quoteTypeCodeEnum.Car,
        quoteTypeCodeEnum.Health,
        quoteTypeCodeEnum.CORPLINE,
        quoteTypeCodeEnum.GroupMedical].includes(filters.lob) &&
    (!filters.teams || filters.teams.length == 0)) {
        label = 'Advisors (select teams first)';
    }

    return label;
}

const loadSubTeams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.subteamOptions = true;

  axios
    .post(`/reports/fetch-subteams-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        subteamOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.subteamOptions = false;
    });
};

const loadAdvisors = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisor-by-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};

watch(
  () => filters.displayBy,
  (newValue, oldValue) => {
    filters.policyExpiryDate = []
    filters.month=''
  },
);

onMounted(() => {
  const queryParams = new URLSearchParams(window.location.search)
  filters.lob = queryParams.get('lob') || '';
  if (filters.lob == ''){
    filters.lob = props.productName
  }
  if (filters.lob !== ''){
    onLobChange(filters.lob, true);
  }

});

const cleanFilters = filters => {
  filters = removeUnusedFilters(filters);
  Object.keys(filters).forEach(
    key => (filters[key] === '' ||
    filters[key] == null ||
    filters[key].length == 0) &&
    delete filters[key],
  );
  return filters;
};

const removeUnusedFilters = filters => {
    const filtersByLob = page.props.filtersByLob;
    Object.keys(filtersByLob).forEach(key => {
        if(filtersByLob[key]['lobs'] && !filtersByLob[key]['lobs'].includes(filters.lob)) {
            delete filters[key];
        }
    });
    return filters;
};

function setQueryStringFilters() {
  for (const [key] of Object.entries(params)) {
    if (key.includes('[]')) {
      filters[key.substring(0, key.length - 2)] = params[key];
    } else {
      filters[key] = params[key];
    }
  }
}

function onSubmit(isValid=true) {
  if(isValid){
    if (filters.lob == ''){
      notification.error({
        title: 'Please select Line of Business',
        position: 'top',
      });
      return
    }
    if (filters.displayBy == ''){
      notification.error({
        title: 'Please select display by filter',
        position: 'top',
      });
      return
    }
    if (filters.policyExpiryDate && filters.policyExpiryDate.length === 0 && filters.month === ''){
      notification.error({
        title: 'Enter values in any one filter [ View by Month or Policy expiry date ]',
        position: 'top',
      });
      return
    }
  }
  filters.page = 1;
  const payLoad = cleanFilters(filters);

  router.visit('/reports/retention-report', {
      method: 'get',
      data: {
        ...payLoad,
      },
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loaders.table = true),
      onFinish: () => {
        loaders.table = false;
      },
    });
  }

const onSubTeamChange = (e, isOnMounted = false) => {

if(!isOnMounted) {
    filters.advisors = [];
}

advisorOptions.value = [];

if (e.length == 0 &&
    [quoteTypeCodeEnum.Car, quoteTypeCodeEnum.GroupMedical].includes(filters.lob) &&
    filters.teams.length > 0) {

    loadAdvisors(filters.teams);
} else {
    loadAdvisorsBySubteams(e);
}
};

const loadAdvisorsBySubteams = e => {
  if (e.length == 0) {
    return;
  }

  if (isMounted.value) {
    isDirty.value = true;
  }

  loaders.advisorOptions = true;

  axios
    .post(`/reports/fetch-advisor-by-sub-team`, {
      teamIds: Array.isArray(e) ? e : [e],
      lob: filters.lob,
    })
    .then(res => {
      if (res.data.length > 0) {
        advisorOptions.value = Object.keys(res.data).map(key => ({
          value: res.data[key].id.toString(),
          label: res.data[key].name,
        }));
      }
    })
    .finally(() => {
      loaders.advisorOptions = false;
    });
};
const insuranceTypeOptions = computed(() => {
    const types = page.props.filterOptions.insurance_type;
    if(types[filters.lob]) {
        return types[filters.lob].map(option => ({
            value: option.value.toString(),
            label: option.label,
        }));
    }

  return [];
});


const totalLeads = reactive({
  modal: false,
  loader: false,
  filters: {
    leadType: '',
    quote_batch_id: null,
    advisorId: null,
  },
  data: {},
  current: '',
  tableHeader: [
    {
      text: 'Ref-ID',
      value: 'code',
    },
    {
      text: 'Customer Name',
      value: 'fullName',
    },
    {
      text: 'Lead Status',
      value: 'quoteStatusName',
    },
    {
      text: 'Expiry Date',
      value: 'policy_expiry_date',
    },
    {
      text: 'Price',
      value: 'price',
    },
  ],
});

function onFetchLeadsInfo(advisor_id, type, page=1){
  totalLeads.data = [];
  totalLeads.modal = true;
  totalLeads.loader = true;
  filters.type = type;
  filters.page = page
  filters.advisor_id = advisor_id;
  const payLoad = cleanFilters(filters);
  axios
    .get(`/reports/fetch-retention-leads-data`, {
      params: {
        ...payLoad,
      }
    })
    .then(response => {
      totalLeads.data = response.data;
    })
    .catch(error => {
      console.log(error);
    })
    .finally(() => {
      loaders.advisorLeadTable = false;
      totalLeads.loader = false;
    });
}
const setPageTable = page => {
  onFetchLeadsInfo(filters.advisor_id, filters.type ,page);
};

</script>

<template>
  <div>
    <Head title="Advisor Retention Report" />
    <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
      Advisor Retention Report
    </h1>

    <x-divider class="my-4" />

    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <ComboBox
          v-model="filters.lob"
          label="Line of Business"
          placeholder="Select Line of Business"
          :options="quoteTypesOptions"
          class="w-full"
          :single="true"
          @update:modelValue="onLobChange"
        />

        <ComboBox
          v-model="filters.displayBy"
          placeholder="Search by Group"
          label="Display by"
          :options="displayBy"
          class="w-full"
          :single="true"
        />

        <DatePicker
          v-if="filters.displayBy == 'batch'"
          v-model="filters.policyExpiryDate"
          label="Policy Expiry Date"
          placeholder="Select Start & End Date"
          range
          size="sm"
          model-type="yyyy-MM-dd"
        />
        <ComboBox
          v-if="filters.displayBy == 'month'"
          v-model="filters.month"
          label="Select month"
          placeholder="Select month"
          :options="monthOptions"
          class="w-full"
          :single="true"
        />

        <ComboBox
          v-if="canShow('teams')"
          :disabled="!isDisabled('teams')"
          :class="{
              'opacity-50': !isDisabled('teams'),
          }"
          v-model="filters.teams"
          label="Teams"
          placeholder="Search by Teams"
          :options="teamOptions"
          @update:model-value="onTeamChange"
          :loading="loaders.teamsOptions"
        />

        <ComboBox
          v-if="canShow('sub_teams')"
          :disabled="!isDisabled('sub_teams')"
          :class="{
              'opacity-50': !isDisabled('sub_teams'),
          }"
          v-model="filters.sub_teams"
          label="SubTeams"
          placeholder="Search by SubTeams"
          :options="subteamOptions"
          @update:model-value="onSubTeamChange"
          :loading="loaders.subteamOptions"
        />

        <ComboBox
          v-if="canShow('advisors')"
          :disabled="!isDisabled('advisors')"
          :class="{
              'opacity-50': !isDisabled('advisors'),
          }"
          v-model="filters.advisors"
          :label="getAdvisorLabel()"
          :options="advisorOptions"
          :loading="loaders.advisorOptions"
        />
        <x-select
          v-if="canShow('insurance_type')"
          v-model="filters.insurance_type"
          label="Insurance Type"
          placeholder="Select insurance type"
          :options="[ { value: '', label: 'Select insurance type' }, ...insuranceTypeOptions ]"
          class="w-full"
        />
      </div>
      <div class="flex justify-between gap-3 mb-4 items-center">
        <div class="flex-1">
          <p v-if="isDirty" class="text-xs text-red-500 text-center font-bold">
            Please click search, to show updated records based on the selected
            filters
          </p>
        </div>
        <div class="flex gap-3">
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="onReset">
            Reset
          </x-button>
        </div>
      </div>
    </x-form>

    <div class="vue3-easy-data-table tablefixed custom-height">
      <div
        class="vue3-easy-data-table__main fixed-header hoverable border-cell custom-height manage-payment-table-parent-div"
      >
        <table>
          <thead class="vue3-easy-data-table__header">
            <tr>
              <th class="inner-th-class" >
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Month</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.MONTH_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class" v-if="isShowBatchColumn" >
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Batch</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.BATCH_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class" v-if="isShowBatchColumn" >
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Start Date</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.START_DATE_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class" v-if="isShowBatchColumn" >
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">End Date</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.END_DATE_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Advisor Name</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.ADVISOR_NAME_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Total</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.TOTAL_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Lost</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.LOST_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Invalid</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.INVALID_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Sales</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.POLICIES_BOOKED_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Volume Gross Retention</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.VOLUME_GROSS_RETENTION_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">Volume Net Retention</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.VOLUME_NET_RETENTION_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
              <th class="inner-th-class">
                <x-tooltip position="right bootom">
                  <span class="border-b border-dotted">RELATIVE RETENTION</span>
                  <template #tooltip>
                    <span class="custom-tooltip-content">{{
                      retentionReportTooltipEnum.RELATIVE_RETENTION_HEADING
                    }}</span>
                  </template>
                </x-tooltip>
              </th>
            </tr>
          </thead>

          <tbody class="vue3-easy-data-table__body">
            <template v-for="(item, index) in reportData.data" :key="item.code">
              <tr>
                <td>{{ item.month }}</td>
                <td v-if="isShowBatchColumn" >{{ item.batch }}</td>
                <td v-if="isShowBatchColumn" >{{ item.start_date }}</td>
                <td v-if="isShowBatchColumn" >{{ item.end_date }}</td>
                <td>{{ item.advisor_name }}</td>
                <td>
                  <x-tooltip position="right bootom">
                    <p v-if="item.total == 0">{{ item.total }}</p>
                    <button
                      v-else
                      @click="onFetchLeadsInfo(item.advisor_id, 'total')"
                      class="text-primary underline"
                    >
                      {{ item.total }}
                    </button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.TOTAL_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="right bootom">
                    <p v-if="item.lost == 0">{{ item.lost }}</p>
                    <button
                      v-else
                      @click="onFetchLeadsInfo(item.advisor_id, 'lost')"
                      class="text-primary underline"
                    >
                      {{ item.lost }}
                    </button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.LOST_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="right bootom">
                    <p v-if="item.invalid == 0">{{ item.invalid }}</p>
                    <button
                      v-else
                      @click="onFetchLeadsInfo(item.advisor_id, 'invalid')"
                      class="text-primary underline"
                    >
                      {{ item.invalid }}
                    </button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.INVALID_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="right bootom">
                    <p v-if="item.sales == 0">{{ item.sales }}</p>
                    <button
                      v-else
                      @click="onFetchLeadsInfo(item.advisor_id, 'sales')"
                      class="text-primary underline"
                    >
                      {{ item.sales }}
                    </button>
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.SALES_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="right bootom">
                    {{ item.volume_gross_retention }}
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.VOLUME_GROSS_RETENTION_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="right bootom">
                    {{ item.volume_net_retention }}
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.VOLUME_GROSS_RETENTION_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
                <td>
                  <x-tooltip position="left bootom">
                    -------
                    <template #tooltip>
                      <span class="custom-tooltip-content">
                        {{ retentionReportTooltipEnum.RELATIVE_RETENTION_COLUMN }}
                      </span>
                    </template>
                  </x-tooltip>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
        <div
          v-if="((reportData.length == 0 ) || (reportData && reportData.data.length ==  0))"
          data-v-32683533=""
          class="vue3-easy-data-table__message"
        >
          No Available Data
        </div>
      </div>
    </div>
    <Pagination
      :links="{
        next: reportData.next_page_url,
        prev: reportData.prev_page_url,
        current: reportData.current_page,
        from: reportData.from,
        to: reportData.to,
        total: reportData.total,
        last: reportData.last_page,
      }"
    />

    <x-modal v-model="totalLeads.modal" size="xl" show-close backdrop>
      <template #header>
        <div class="text-center">Advisor Assigned : {{ filters.type.charAt(0).toUpperCase() + filters.type.slice(1) }} Leads</div>
      </template>
      <section class="min-h-[70vh]">
        <div v-if="!loaders.advisorLeadTable">
          <PaginateClient
            :links="{
              next: totalLeads.data.next_page_url,
              prev: totalLeads.data.prev_page_url,
              current: totalLeads.data.current_page,
              from: totalLeads.data.from,
              to: totalLeads.data.to,
              total: totalLeads.data.total,
              last: totalLeads.data.last_page,
            }"
            :loading="totalLeads.loader"
            @update="setPageTable"
          />
          <DataTable
            table-class-name="tablefixed compact"
            :loading="totalLeads.loader"
            :headers="totalLeads.tableHeader"
            :items="totalLeads.data.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
          ></DataTable>
        </div>
        <div v-else class="p-4 flex flex-col justify-center items-center gap-4">
          <x-spinner size="lg" color="#1d83bc" />
          <p class="text-sm">Fetching records...</p>
        </div>
      </section>
    </x-modal>

  </div>
</template>

<style scoped>

.custom-tooltip-content {
  max-width: 200px;
  white-space: normal;
  z-index: 999;
  position: relative;
  font-size: 12px;
  text-transform: none;
}
.custom-height {
  min-height: 200px;
}

.tooltip-display {
  display: inherit;
}

.inner-th-class {
  min-width: 160px;
}
</style>
