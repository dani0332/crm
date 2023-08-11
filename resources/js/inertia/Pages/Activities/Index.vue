<script setup>

defineProps({
  activities: Object,
  advisors: Object
});

const page = usePage();
const selectedOption  = ref('');
const customStartDate = ref(null);
const customEndDate   = ref(null);

const notification = useNotifications('toast');

const filters = reactive({
  assignee_id: '',
  status: '',
  due_date_start: '',  
  due_date_end: '',  
  page: 1,
});

const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'TITLE', value: 'title' },
  { text: 'CDBID', value: 'uuid' },
  { text: 'CLIENT NAME', value: 'client_name' },
  { text: 'ASSIGNED TO', value: 'name' },
  { text: 'FOLLOWUP DATE', value: 'due_date' },  
  { text: 'DONE', value: 'status' },
];


const handleEdit = (item) => {
  // Implement the edit functionality here
};

const handleDelete = (item) => {
  // Implement the delete functionality here
};

function filterActivities(isValid) {
  if (!isValid) {
    return;
  }
  for (const key in filters) {
    if (filters[key] === '') {
      delete filters[key];
    }
  }

  router.visit('/activities', {
    method: 'get',
    data: {
      ...filters,
    },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loader.table = false;
    },
    onBefore: () => {
      filters.page = 1;
      loader.table = true;
    },
  });
}

function resetFilters() {
  router.visit('/activities', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

function setQueryFilters() {
  let query = router.page.url.split('?')[1];
  if (query) {
    query = query.split('&');
    query.forEach(item => {
      const [key, value] = item.split('=');
      filters[key] = value;
    });
  }
}

function resetDates(option) {
  const today = new Date();
  let startDate, endDate;
  selectedOption.value = option; 
  if (option == 'today') {
    startDate = today.toLocaleDateString();
    endDate = today.toLocaleDateString();
  }  else if (option == 'tomorrow') {
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1); 
    startDate = tomorrow.toLocaleDateString();
    endDate = tomorrow.toLocaleDateString();
  }  else if (option == 'tweek') {
    const firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 1));
    const lastDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 7));    
    startDate = firstDayOfWeek.toLocaleDateString();
    endDate = lastDayOfWeek.toLocaleDateString();
  } else if (option == 'tmonth') {
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    startDate = firstDayOfMonth.toLocaleDateString();
    endDate = lastDayOfMonth.toLocaleDateString();
  }  else if (option == 'overdue') {
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1); 
    startDate = '1/1/1970';
    endDate = yesterday.toLocaleDateString();    
  } else if (option === 'custom') {
    // Handle the custom option by setting the custom start and end dates
    selectedOption.value = option;
    customStartDate.value = null; // Clear previously selected dates
    customEndDate.value = null;
  }

  filters.due_date_start  = startDate;
  filters.due_date_end    = endDate;
  filterActivities(1); // Call the filterActivities function
}

function applyCustomDates() {
  if (customStartDate.value && customEndDate.value) {
    // Update the filters with the selected custom dates
    filters.due_date_start = customStartDate.value;
    filters.due_date_end = customEndDate.value;
    filterActivities(1); // Call the filterActivities function
  }
}

watch(
  () => filters,
  { deep: true, immediate: true },
);

onMounted(() => {
  setQueryFilters();
});
</script>

<template>
  <div>
    <Head title="Activities" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Activities</h2>
      <div class="space-x-3">
        <Link href="/activities/create">
          <x-button size="sm" color="#ff5e00" tag="div"> Create Activity </x-button>
        </Link>
      </div>
    </div>
   
    <x-divider class="my-4" />

    <x-form @submit="filterActivities" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
        <x-select
          v-model="filters.assignee_id"
          label="Assigned To"
          placeholder="Select Assigned To"
          :options="[
            // Loop through advisorArray to generate options
            ...advisors.map(advisor => ({ value: advisor.id, label: advisor.name })),
          ]"
        />
        <x-select
          v-model="filters.status"
          label="Status"
          placeholder="Select Activity Status"
          :options="[
            { value: '1', label: 'Done' },
            { value: '0', label: 'Pending' },            
          ]"
        />
      </div>      
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex gap-3"> 
          <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
          <x-button size="sm" color="primary" @click.prevent="resetFilters">
            Reset
          </x-button>
        </div>
      </div>

    </x-form>
    <x-divider class="my-2" />
    <div class="flex justify-end gap-3 mb-4 mt-1">
  <div class="flex gap-3">
    <x-button
      size="sm"
      :color="selectedOption === 'overdue' ? 'primary' : 'default'"
      @click.prevent="resetDates('overdue')"
    >
      Overdue
    </x-button>
    <x-button
      size="sm"
      :color="selectedOption === 'today' ? 'primary' : 'default'"
      @click.prevent="resetDates('today')"
    >
      Today
    </x-button>
    <x-button
      size="sm"
      :color="selectedOption === 'tomorrow' ? 'primary' : 'default'"
      @click.prevent="resetDates('tomorrow')"
    >
      Tomorrow
    </x-button>
    <x-button
      size="sm"
      :color="selectedOption === 'tweek' ? 'primary' : 'default'"
      @click.prevent="resetDates('tweek')"
    >
      This Week
    </x-button>
    <x-button
      size="sm"
      :color="selectedOption === 'tmonth' ? 'primary' : 'default'"
      @click.prevent="resetDates('tmonth')"
    >
      This Month
    </x-button>
    <x-button
      size="sm"
      :color="selectedOption === 'custom' ? 'primary' : 'default'"
      @click.prevent="resetDates('custom')"
    >
      Custom
    </x-button>    
  </div>  
</div>
 <!-- Custom Date Range Picker -->
  <div v-if="selectedOption === 'custom'">
      <div class="flex gap-3">        
        <DatePicker
          v-model="customStartDate"
          name="created_at_start"
          label="Start Date"
        />
        <DatePicker
          v-model="customEndDate"
          name="created_at_end"
          label="End Date"
        />  
        
      </div>
      <div class="flex justify-end gap-3 mb-4 mt-1">
        <div class="flex gap-3"> 
          <x-button size="sm" color="primary" @click="applyCustomDates">Apply</x-button>
        </div>
      </div>
      <x-divider class="my-2" />
    </div>
    <DataTable
      table-class-name="tablefixed"
      :loading="loader.table"
      :headers="[...tableHeader, { text: 'Action', value: 'action' }]"
      :items="activities.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    > 
      <template #item.action="{ item }">button</template>     
    </DataTable>

    <Pagination
      :links="{
        next: activities.next_page_url,
        prev: activities.prev_page_url,
        current: activities.current_page,
        from: activities.from,
        to: activities.to,
      }"
    />
  </div>
</template>
