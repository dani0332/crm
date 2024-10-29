<script setup>
defineProps({ errors: Object });

const notification = useNotifications('toast');
const page = usePage();

const { isRequired } = useRules();
const uploadForm = useForm({
  file_name: '',
});

const tableHeader = ref([
  { text: 'Field name', value: 'name' },
  { text: 'Description', value: 'description' },
  { text: 'Required', value: 'required' },
  { text: 'Max Size', value: 'MaxSize' },
]);

const data = reactive([
  {
    name: 'Customer Name',
    description: 'Customer Name should only be in letters - no numbers allowed',
    required: true,
    MaxSize: 50,
  },
  {
    name: 'Phone No',
    description: 'Customer Phone No',
    required: true,
    MaxSize: 12,
  },
  {
    name: 'Email Id',
    description: 'Customer Email Id must be valid Email Id',
    required: true,
    MaxSize: 50,
  },
  {
    name: 'Insurance Type',
    description:
      'Insurance Type description of the lead, must be exact match with Insurance Type List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Lead Type',
    description:
      'Lead Type of the lead, must be exact match with Lead Type List',
    required: true,
    MaxSize: 30,
  },
  {
    name: 'Nationality',
    description: 'Nationality must be exact match with Nationality List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'DOB',
    description: 'DOB must in the format of dd/mm/yyyy (Ex:- 16/06/1985)',
    required: false,
    MaxSize: 10,
  },
  {
    name: 'Years of driving',
    description:
      'Years of driving must be exact match with Years of driving List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Car Manufacturer',
    description: 'Car Make must be exact match with Car Make List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Model',
    description: 'Car Model must be exact match with Car Model List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Year of manufacture',
    description:
      'Year of manufacture must be exact match with Year of manufacture List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Emirates of registration',
    description:
      'Emirates of registration must be exact match with Emirates of registration List',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Car Value',
    description:
      'Car value must be in decimal between 0 to 9999999999.99 (Example: 1223456789.15)',
    required: false,
    MaxSize: 10,
  },
  {
    name: 'Notes',
    description: 'Additional Notes',
    required: false,
    MaxSize: 500,
  },
  {
    name: 'Enquiry Date',
    description:
      'Enquiry Date must in the format of dd/mm/yyyy (Ex:- 16/06/2021)',
    required: true,
    MaxSize: 10,
  },
  {
    name: 'Created Date',
    description:
      'Created Date must in the format of dd/mm/yyyy (Ex:- 16/06/2021)',
    required: true,
    MaxSize: 10,
  },
  {
    name: 'Advisor email',
    description: 'Advisor Email Id must be valid Email Id',
    required: false,
    MaxSize: 30,
  },
  {
    name: 'Followup Date',
    description:
      'Followup Date must in the format of dd/mm/yyyy (Ex:- 16/06/2021)',
    required: false,
    MaxSize: 10,
  },
  {
    name: '	Followup Time',
    description:
      'Followup Date must in the format of dd/mm/yyyy (Ex:- 16/06/2021)',
    required: false,
    MaxSize: 10,
  },
]);
function onSubmit(isValid) {
  if (isValid) {
    uploadForm.post(route('tmuploadlead-store'), {
      onError: errors => {},
      onSuccess: () => {
        displayNotification();
        uploadForm.file_name = '';
      },
      onStart: () => {
        uploadForm.clearErrors();
      },
    });
  }
}

function displayNotification() {
  const session = usePage().props.flash;
  for (const key in session) {
    notification[key]({
      title: session[key],
      position: 'top',
      timeout: 0,
    });
  }
}
</script>
<template>
  <Head title="Upload Tm Leads" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Upload TM Leads</h2>
    <div class="flex gap-2">
      <x-button size="sm" color="green">
        <a
          href="https://myalfreddev.blob.core.windows.net/myrewards/98034F42-DD9B-4CAC-6572-5BB47595351E_TestTeleLead.csv"
          >Download Sample
        </a>
      </x-button>
      <Link :href="route('tmuploadlead-list')">
        <x-button size="sm" color="#ff5e00"> Upload TM Leads List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex flex-col gap-4">
      <form
        @submit.prevent="onSubmit"
        class="flex flex-col gap-4"
        enctype="multipart/form-data"
      >
        <div class="grid grid-cols-6 gap-4">
          <div class="col-start-2 col-span-4">
            <div class="flex flex-col gap-4">
              <label for="file" class="text-sm font-semibold">
                Upload File <sup class="text-red-500">*</sup>
              </label>
              <input
                type="file"
                class="border border-gray-300 rounded p-2"
                @input="uploadForm.file_name = $event.target.files[0]"
              />
              <div v-if="errors.file_name" class="text-red-500 text-sm">
                {{ errors.file_name }}
              </div>
              <progress
                v-if="uploadForm.progress"
                :value="uploadForm.progress.percentage"
                max="100"
              >
                {{ uploadForm.progress.percentage }}%
              </progress>
            </div>
          </div>
          <div class="col-start-2 col-span-4">
            <div class="flex flex-col gap-4">
              <p class="text-lg font-semibold">Required file format</p>
              <ul class="list-disc list-inside">
                <li>
                  Please download the sample csv file and modify the data
                  according to the recommendations for a successful import..
                </li>
                <li>File must be a csv file with the following fields.</li>
                <li>Please ensure there are no commas in file.</li>
                <li>First line will be skipped while uploading.</li>
                <li>
                  Please ensure there are no spaces in start and end of columns
                  data.
                </li>
                <li>
                  Please ensure max allowed size is
                  <strong>2mb (2048kb)</strong> & max numbers of
                  <strong>1000 to 2000</strong>
                  leads per csv file.
                </li>
                <li>
                  Please ensure columns header and allocation same as per given
                  in sample csv file.
                </li>
                <li>
                  Please ensure all required columns data filled in the csv
                  file.
                </li>
                <li>
                  Please ensure all date columns format set as
                  <strong>dd/mm/yyyy (Ex:- 16/06/2021).</strong>
                </li>
                <li>
                  For Car Insurance, available 2 types: 1.) Car Insurance - TPL
                  2.) Car Insurance - Comp
                </li>
              </ul>
            </div>
          </div>
        </div>
        <hr class="my-4" />
        <DataTable
          table-class-name="tablefixed"
          :headers="tableHeader"
          :items="data || []"
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-required="{ required }">
            <div class="text-center">
              <x-tag size="sm" :color="required ? 'success' : 'error'">
                {{ required ? 'Yes' : 'No' }}
              </x-tag>
            </div>
          </template>
        </DataTable>
        <div class="flex justify-end">
          <x-button type="submit" color="#ff5e00" size="sm"> Upload </x-button>
        </div>
      </form>
    </div>
  </div>
</template>
