<script setup>
defineProps({
  policies: Array,
});

const { isRequired } = useRules();

const page = usePage();
const loader = reactive({
  table: false,
  export: false,
});

const tableHeader = [
  { text: 'Policy Number', value: 'policy_no' },
  { text: 'Customer name', value: 'customer.name' },
  { text: 'Currently insured with', value: 'policy.insurer' },
  { text: 'Product', value: '' },
  { text: 'Policy expiry date', value: 'policy.end_date' },
];
</script>

<template>
  <div>
    <Head title="Legacy Policy" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Legacy Policy List</h2>
    </div>
    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :items="policies.data || []"
      border-cell
      hide-rows-per-page
      hide-footer
      fixed-checkbox
    >
      <template #item-policy_no="{ policy_oid }">
        <Link
          :href="`/legacy-policy/${policy_oid}`"
          class="text-primary-500 hover:underline"
        >
          {{ policy_oid }}
        </Link>
      </template>
    </DataTable>

    <Pagination
      :links="{
        next: policies.next_page_url,
        prev: policies.prev_page_url,
        current: policies.current_page,
        from: policies.from,
        to: policies.to,
      }"
    />
  </div>
</template>
