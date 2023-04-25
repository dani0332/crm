<script setup>
defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
  isManger: Boolean,
  isManualAllocationAllowed: Boolean,
  dropdownSource: Object,
});

const { isRequired } = useRules();

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

let availableFilters = {
  code: '',
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  created_at_start: '',
  created_at_end: '',
  quote_status: [],
  advisors: [],
  is_ecommerce: '',
  is_renewal: '',
  page: 1,
};

const filters = reactive(availableFilters);

function onSubmit(isValid) {
  if (isValid) {
    filters.page = 1;

    Object.keys(filters).forEach(
      key =>
        (filters[key] === '' || filters[key].length === 0) &&
        delete filters[key],
    );

    router.visit('/personal-quotes/pet', {
      method: 'get',
      data: filters,
      preserveState: true,
      preserveScroll: true,
      onBefore: () => (loader.table = true),
      onSuccess: () => (loader.table = false),
    });
  } else {
    console.log('Invalid');
  }
}

function onReset() {
  router.visit('/personal-quotes/pet', {
    method: 'get',
    data: { page: 1 },
    preserveScroll: true,
    onBefore: () => (loader.table = true),
    onSuccess: () => (loader.table = false),
  });
}

onMounted(() => {});

const tableHeader = [
  { text: 'CDB ID', value: 'uuid' },
  { text: 'FIRST NAME', value: 'first_name' },
  { text: 'LAST NAME', value: 'last_name' },
  { text: 'DOB', value: 'dob_formatted' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ADVISOR', value: 'advisor' },
  { text: 'CREATED DATE', value: 'created_at' },
  { text: 'LAST MODIFIED DATE', value: 'updated_at' },
  { text: 'PREMIUM', value: 'premium' },
  { text: 'POLICY NO', value: 'policy_no' },
  { text: 'SOURCE', value: 'source' },
  { text: 'CURRENTLY INSURED WITH', value: 'currently_insured_with' },
  { text: 'IS ECOMMERCE', value: 'is_ecommerce' },
  { text: 'POLICY NUMBER', value: 'policy_number' },
  { text: 'TYPE OF PET', value: 'type_of_pet' },
  { text: 'BREED OF PET', value: 'breed_of_pet1' },
  { text: 'AGE OF PET', value: 'age_of_pet' },
  { text: 'IS NEUTERED', value: 'is_neutered' },
  { text: 'IS MICROCHIPPED', value: 'is_microchipped' },
  { text: 'MICROCHIP NO', value: 'microchip_no' },
  { text: 'IS MIXED BREED', value: 'is_mixed_breed' },
  { text: 'HAS INJURY', value: 'has_injury' },
  { text: 'ACCOMMODATION TYPE', value: 'accommodation_type' },
  { text: 'POSSESION TYPE', value: 'possesion_type' },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const options = computed(() => {
  return page.props.dropdownSource.advisor_id.map(item => {
    return {
      value: item.id,
      label: item.name,
    };
  });
});

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const quotesSelected = ref([]);

const assignForm = useForm({
  assigned_to_id_new: null,
  modelType: 'Pet',
  selectTmLeadId: '',
  isManagerOrDeputy: page.props.isManger,
  isLeadPool: false,
  isManualAllocationAllowed: page.props.isManualAllocationAllowed,
});

function onAssignLead(isValid) {
  if (isValid) {
    const selected = quotesSelected.value.map(e => e.id);
    assignForm
      .transform(data => ({
        ...data,
        selectTmLeadId: `${selected}`,
      }))
      .post('/quotes/pet/manualLeadAssign', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          let title =
            quotesSelected.value.length > 1
              ? 'Pet Leads Assigned'
              : 'Pet Lead Assigned';
          quotesSelected.value = [];
          notification.success({
            title: title,
            position: 'top',
          });
        },
      });
  }
}
</script>

<template>
  <div>
    <Head title="Pet Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Pet Quotes List</h2>
      <x-button
        v-if="can(permissionsEnum.PetQuotesCreate)"
        size="sm"
        color="#ff5e00"
        href="/personal-quotes/pet/create"
      >
        Create Lead
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
        <x-input
          v-model="filters.code"
          type="search"
          name="code"
          label="CDB ID"
          class="w-full"
          placeholder="Search by CDB ID"
        />
        <x-input
          v-model="filters.first_name"
          type="search"
          name="first_name"
          label="First Name"
          class="w-full"
          placeholder="Search by First Name"
        />
        <x-input
          v-model="filters.last_name"
          type="search"
          name="last_name"
          label="Last Name"
          class="w-full"
          placeholder="Search by Last Name"
        />
        <x-input
          v-model="filters.email"
          type="search"
          name="email"
          label="Email"
          class="w-full"
          placeholder="Search by Email"
        />
        <x-input
          v-model="filters.mobile_no"
          type="search"
          name="mobile_no"
          label="Mobile Number"
          class="w-full"
          placeholder="Search by Mobile Number"
        />

        <DatePicker
          v-model="filters.created_at_start"
          name="created_at_start"
          label="Created Date Start"
          class="w-full"
        />
        <DatePicker
          v-model="filters.created_at_end"
          name="created_at_end"
          label="Created Date End"
          class="w-full"
        />

        <ComboBox
          v-model="filters.quote_status_id"
          label="Lead Status"
          name="quote_status"
          placeholder="Search by Lead Status"
          :options="
            quoteStatuses.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
        />
        <ComboBox
          v-model="filters.advisors"
          label="Advisor"
          placeholder="Search by Advisor"
          :options="options"
        />
        <x-select
          v-model="filters.is_ecommerce"
          label="Is Ecommerce"
          placeholder="Search by Ecommerce"
          :options="[
            { value: '', label: 'All' },
            { value: 'Yes', label: 'Yes' },
            { value: 'No', label: 'No' },
          ]"
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

    <Transition name="fade">
      <div v-if="quotesSelected.length > 0" class="mb-4">
        <div
          class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50"
          v-if="isManualAllocationAllowed"
        >
          <x-form @submit="onAssignLead" :auto-focus="false">
            <div class="w-full flex flex-col md:flex-row gap-4">
              <x-select
                v-model="assignForm.assigned_to_id_new"
                label="Assign Advisor"
                :options="advisorOptions"
                placeholder="Select Advisor"
                class="flex-1 w-auto"
                :rules="[isRequired]"
              />
              <div class="mb-3 md:pt-6">
                <x-button
                  color="orange"
                  size="sm"
                  type="submit"
                  :loading="assignForm.processing"
                >
                  Assign
                </x-button>
              </div>
            </div>
          </x-form>
        </div>
      </div>
    </Transition>

    <DataTable
      v-model:items-selected="quotesSelected"
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="quotes.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-uuid="{ code, uuid }">
        <Link
          v-if="can(permissionsEnum.PetQuotesView)"
          :href="`/personal-quotes/pet/${uuid}`"
          class="text-primary-500 hover:underline"
        >
          {{ code }}
        </Link>
        <span v-else>{{ code }}</span>
      </template>

      <template #item-advisor="{ advisor }">
        {{ advisor?.email }}
      </template>

      <template #item-quote_status="{ quote_status }">
        {{ quote_status?.text }}
      </template>

      <template #item-currently_insured_with="{ currently_insured_with }">
        {{ currently_insured_with?.text }}
      </template>

      <template #item-is_ecommerce="{ is_ecommerce }">
        <div class="text-center">
          <x-tag size="sm" :color="is_ecommerce ? 'success' : 'error'">
            {{ is_ecommerce ? 'Yes' : 'No' }}
          </x-tag>
        </div>
      </template>

      <template #item-policy_number="{ pet_quote }">
        {{ pet_quote?.policy_number }}
      </template>
      <template #item-type_of_pet="{ pet_quote }">
        {{ pet_quote?.pet_type?.text }}
      </template>
      <template #item-age_of_pet="{ pet_quote }">
        {{ pet_quote?.pet_age?.text }}
      </template>
      <template #item-is_neutered="{ pet_quote }">
        {{ pet_quote?.is_neutered ? 'Yes' : 'No' }}
      </template>
      <template #item-is_microchipped="{ pet_quote }">
        {{ pet_quote?.is_microchipped ? 'Yes' : 'No' }}
      </template>
      <template #item-microchip_no="{ pet_quote }">
        {{ pet_quote?.microchip_no }}
      </template>
      <template #item-is_mixed_breed="{ pet_quote }">
        {{ pet_quote?.is_mixed_breed ? 'Yes' : 'No' }}
      </template>
      <template #item-has_injury="{ pet_quote }">
        {{ pet_quote?.has_injury ? 'Yes' : 'No' }}
      </template>
      <template #item-accommodation_type="{ pet_quote }">
        {{ pet_quote?.accomodation_type?.text }}
      </template>
      <template #item-possesion_type="{ pet_quote }">
        {{ pet_quote?.possession_type?.text }}
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    />
  </div>
</template>
