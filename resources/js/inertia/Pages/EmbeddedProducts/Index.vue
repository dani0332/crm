<script setup>
defineProps({
  quotes: Object,
  quoteStatuses: Array,
  advisors: Array,
});

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
</script>

<template>
  <div>
    <Head title="Pet Quotes" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Embedded Products</h2>
      <x-button size="sm" color="#ff5e00" href="/embedded-products/create">
        Create Embedded Product
      </x-button>
    </div>
    <x-divider class="my-4" />

    <!--   filters     -->

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :loading="loader.table"
      :items="[]"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
    </DataTable>
    <!-- 
    <Pagination
      :links="{
        next: quotes.next_page_url,
        prev: quotes.prev_page_url,
        current: quotes.current_page,
        from: quotes.from,
        to: quotes.to,
      }"
    /> -->
  </div>
</template>
