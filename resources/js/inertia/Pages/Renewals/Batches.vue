<script setup>
defineProps({
    batches: Object,
});

const page = usePage();

const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const role = [rolesEnum.Admin, rolesEnum.LeadPool, rolesEnum.LifeManager];
const roleLeadPool = [rolesEnum.LeadPool];
const hasAnyRole = role => useHasAnyRole(role);





const loader = reactive({
    table: false,
    export: false,
});


const tableHeader = [
    { text: 'Batch', value: 'renewal_batch' },
    { text: 'Actions', value: 'action', width:300 },
];

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

</script>

<template>
    <div>
        <Head title="Batches" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Batches</h2>
            <div class="space-x-3"></div>
        </div>
        <x-divider class="my-4" />

        <DataTable
            table-class-name="tablefixed"
            :loading="loader.table"
            :headers="tableHeader"
            :items="batches.data || []"
            border-cell
            hide-rows-per-page
            hide-footer
        >

            <template #item-action="{action,renewal_batch}">

                <x-button
                    :href="`/renewals/batches/${renewal_batch}/plans-processes`"
                    class="text-primary-500  btn-passed mr-2 "
                    color="primary"
                >
                    Fetch Plans
                </x-button>
                <x-button
                    :href="`/renewals/batches/${renewal_batch}/`"
                    class="text-primary-500"
                    color="#ff5e00"
                >
                    Send Emails
                </x-button>
            </template>
        </DataTable>

        <Pagination
            :links="{
        next: batches.next_page_url,
        prev: batches.prev_page_url,
        current: batches.current_page,
        from: batches.from,
        to: batches.to,
      }"
        />
    </div>
</template>
