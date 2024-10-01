<script setup>
const loader = reactive({
  table: false,
  export: false,
});
const notification = useToast();
const tableHeader = [
  { text: 'SR NO.', value: 'iterator' },
  { text: 'FIELD NAME', value: 'field_name' },
  { text: 'DESCRIPTION', value: 'description' },
  { text: 'REQUIRED', value: 'required' },
  { text: 'MAX SIZE', value: 'max_size' },
];

const tableData = [
  {
    iterator: 1,
    field_name: 'Customer Name',
    description: 'Customer Name should only be in letters - no numbers allowed',
    required: 'Yes',
    max_size: '100',
  },
  {
    iterator: 2,
    field_name: 'Email Id',
    description: 'Customer Email Id',
    required: 'Yes',
    max_size: '100',
  },
];

const uploadCustomer = useForm({
  file_name: '',
  cdb_id: '',
  myalfred_expiry_date: '',
  inviatation_email: '',
});

function onSubmit() {
  uploadCustomer.post('/customer-process', {
    onError: errors => {
      console.log(uploadCustomer.setError(errors));
    },
    onSuccess: () => {
      notification.success({
        title: 'Upload customers records has been stored',
        position: 'top',
      });
    },
  });
}
</script>

<template>
  <Head title="Upload Customers List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Upload Customers</h2>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 gap-4">
      <label
        class="relative x-input inline-block align-bottom text-left mb-3 w-full"
        style="--x-input-border: #38bdf8"
      >
        <p class="font-medium text-gray-800 mb-1">Upload File*</p>
        <div class="relative">
          <input
            class="appearance-none block w-full placeholder-gray-400 outline-transparent outline outline-2 outline-offset-[-1px] transition-all duration-150 ease-in-out border-gray-300 dark:border-gray-700 border shadow-sm rounded-md hover:border-gray-400 dark:hover:border-gray-500 px-3 py-1.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-[color:var(--x-input-border)]"
            type="file"
            @input="uploadCustomer.file_name = $event.target.files[0]"
          />
        </div>
      </label>

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
      <p class="text-lg font-semibold">
        Download Sample XLSX
        <a
          href="https://myalfreddev.blob.core.windows.net/myrewards/81205DF5-E2A5-4626-D5DD-13656D4D9E2C_test-new.xlsx"
        >
          <img
            src="https://img.icons8.com/color/40/000000/ms-excel.png"
            alt="xlsx"
            border="0"
          />
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
        <li>
          Please ensure there are no spaces in start and end of columns data
        </li>
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
  >
  </DataTable>
</template>
