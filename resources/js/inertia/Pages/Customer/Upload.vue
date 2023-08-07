<script setup>

const loader = reactive({
    table: false,
    export: false,
});

const tableHeader = [
    { text: 'SR NO.', value: 'iterator' },
    { text: 'FIELD NAME', value: 'field_name' },
    { text: 'DESCRIPTION', value: 'description' },
    { text: 'REQUIRED', value: 'required' },
    { text: 'MAX SIZE', value: 'max_size' },
];

const tableData = [];

const uploadCustomer = useForm({
    file_name : '',
    cdb_id : '',
    myalfred_expiry_date : '',
    inviatation_email : ''
});

</script>

<template>
    <Head title="Upload Customers List" />
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">
            Upload Customers
        </h2>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
        <div class="grid sm:grid-cols-3 gap-4">
            <x-input
                v-model="uploadCustomer.file_name"
                type="file"
                label="Upload File*"
                class="w-full"
                :error="uploadCustomer.errors.file_name"
            />
            <x-input
                v-model="uploadCustomer.cdb_id"
                type="text"
                label="Ref-ID"
                placeholder="Ref-ID"
                class="w-full"
                :error="uploadCustomer.errors.cdb_id"
            />
            <DatePicker
                v-model="uploadCustomer.myalfred_expiry_date"
                name="myalfred_expiry_date"
                label="Policy Expiry Date"
                :hasError="uploadCustomer.errors.myalfred_expiry_date"
            />
            <div class="grid grid-cols-2 gap-2">
                <x-checkbox
                    v-model="uploadCustomer.inviatation_email"
                    label="Send Invitation Email"
                    color="primary"
                />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <p class="text-lg font-semibold">Download Sample XLSX
                <a href="https://myalfreddev.blob.core.windows.net/myrewards/81205DF5-E2A5-4626-D5DD-13656D4D9E2C_test-new.xlsx">
                    <img src="https://img.icons8.com/color/40/000000/ms-excel.png" alt="xlsx" border="0" />
                </a>
            </p>
            <div>
                <x-button
                    size="md"
                    color="emerald"
                    type="submit"
                    :loading="uploadCustomer.processing"
                >
                    Create
                </x-button>
            </div>
        </div>
    </x-form>
    <x-divider class="my-4" />
    <div class="col-start-2 col-span-4">
        <div class="flex flex-col gap-4">
            <p class="text-lg font-semibold">Import Instructions must be follow:</p>
            <ul class="list-disc list-inside">
                <li>File must be a xlsx file with the following fields.</li>
                <li>Please ensure there are no commas in file.</li>
                <li>First line will be skipped while uploading.</li>
                <li>Please ensure there are no spaces in start and end of columns data</li>
                <li>Please ensure max allowed size is 2mb (2048kb)</li>
                <li>Arabic is not supported in CSV file upload</li>
            </ul>
        </div>
    </div>
    <x-divider class="my-4" />
    <DataTable
        table-class-name="tablefixed"
        :headers="tableHeader"
        :loading="loader.table"
        :items="tableData"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox>
    </DataTable>
</template>
