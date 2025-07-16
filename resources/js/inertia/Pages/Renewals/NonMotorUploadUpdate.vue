<script setup>
import { ref, computed } from 'vue';
const notification = useToast();
const page = usePage();
const uploadForm = useForm({
  csvFile: '',
  lob: '',
});
let file = '';
let files = [];
let isUploading = ref(false);
defineProps({
  azureStorageUrl: String,
  azureStorageContainer: String,
  lobs: Object,
});

let errors = {
  type: '',
  step: '',
};

// table for home
const tableData = [
  {
    id: 1,
    name: 'Customer Name',
    description: 'Customer name',
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 2,
    name: 'Customer Email',
    description: 'Customer email',
    required: 'No',
    maxSize: 255,
  },
  {
    id: 3,
    name: 'Customer Number',
    description: 'Customer mobile number',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 4,
    name: 'Insurance Type',
    description: 'Insurer type code',
    required: 'Yes',
    maxSize: 10,
  },
  {
    id: 5,
    name: 'Current Insurance Provider',
    description: 'Currently insured with',
    required: 'Yes',
    maxSize: 20,
  },
  {
    id: 6,
    name: 'Advisor Email',
    description: 'Advisor email',
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 7,
    name: 'Policy Number',
    description: 'Previous policy number',
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 8,
    name: 'Policy Start Date',
    description: 'Previous policy start date',
    required: 'No',
    maxSize: 10,
  },
  {
    id: 9,
    name: 'Policy End Date',
    description: 'Previous policy expiry date',
    required: 'Yes',
    maxSize: 10,
  },
  {
    id: 10,
    name: 'You are a',
    description: 'Ownership status',
    required: 'Yes',
    maxSize: 150,
  },
  {
    id: 11,
    name: 'I live in a',
    description: 'Type of property',
    required: 'Yes',
    maxSize: 25,
  },
  {
    id: 12,
    name: 'Occupancy Status for Owners',
    description: "Type of owner's occupancy",
    required: 'No',
    maxSize: 150,
  },
  {
    id: 13,
    name: 'Location Area',
    description: 'Location Area',
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 14,
    name: 'Cover required',
    description: 'Type of coverage you need',
    required: 'Yes',
    maxSize: 50,
  },
  {
    id: 15,
    name: 'Contents',
    description: 'Contents AED',
    required: 'No',
    maxSize: 50,
  },
  {
    id: 16,
    name: 'Personal Belongings',
    description: 'Personal belongings AED',
    required: 'No',
    maxSize: 25,
  },
  {
    id: 17,
    name: 'Building',
    description: ' Building AED',
    required: 'no',
    maxSize: 25,
  },
  {
    id: 18,
    name: 'Insurance Provider',
    description: 'Provider name',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 19,
    name: 'Plan Name',
    description: 'Plan name',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 20,
    name: 'Claims History',
    description: 'Claims history',
    required: 'Yes',
    maxSize: 10,
  },
  {
    id: 21,
    name: 'Premium',
    description: 'Renewal premium',
    required: 'No',
    maxSize: 20,
  },
  {
    id: 22,
    name: 'Insurer Quote No.',
    description: 'Insurer Quote No.',
    required: 'No',
    maxSize: 20,
  },
  {
    id: 23,
    name: 'Previous Advisor Email',
    description: 'Previous advisor',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 24,
    name: 'Notes',
    description: 'Additional notes',
    required: 'No',
    maxSize: 100,
  },
];

// table for health
const tableDataHealth = [
  {
    id: 1,
    name: 'Customer Name',
    description: 'Customer Name',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 2,
    name: 'Customer Email',
    description: 'Customer Email',
    required: 'No',
    maxSize: 255,
  },
  {
    id: 3,
    name: 'Customer Mobile',
    description: 'Customer Mobile Number',
    required: 'No',
    maxSize: 100,
  },
  {
    id: 4,
    name: 'Plan',
    description: 'Insurer Plan Code',
    required: 'Yes',
    maxSize: 50,
  },
  {
    id: 5,
    name: 'Previous Policy Number',
    description: 'Expiring policy number',
    required: 'Yes',
    maxSize: 50,
  },
  {
    id: 6,
    name: 'Previous Policy Expiry Date',
    description:
      'End date of the expiring policy - Format should be DD/MM/YYYY',
    required: 'Yes',
    maxSize: 10,
  },
  {
    id: 7,
    name: 'Advisor Email',
    description: "Renewal advisor's email",
    required: 'No',
    maxSize: 100,
  },
  {
    id: 8,
    name: 'Renewal Premium',
    description: 'Renewal Premium',
    required: 'No',
    maxSize: 150,
  },
  {
    id: 9,
    name: 'Renewal Co-Pay',
    description: 'Renewal copay code',
    required: 'Yes',
    maxSize: 300,
  },
  {
    id: 10,
    name: 'Member Names',
    description: 'Member Names',
    required: 'No',
    maxSize: 500,
  },
  {
    id: 11,
    name: 'DOB',
    description: "Customer's DOB",
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 12,
    name: 'Nationality',
    description: "Customer's Nationality",
    required: 'Yes',
    maxSize: 300,
  },
  {
    id: 13,
    name: 'Gender',
    description: "Customer's Gender",
    required: 'Yes',
    maxSize: 50,
  },
  {
    id: 14,
    name: 'Member Category',
    description: 'Member Category',
    required: 'Yes',
    maxSize: 300,
  },
  {
    id: 15,
    name: 'Emirate of Visa',
    description: 'Emirate of visa specified in expiring policy',
    required: 'Yes',
    maxSize: 100,
  },
  {
    id: 16,
    name: 'Payment Link',
    description: 'Payment link from insurer',
    required: 'No',
    maxSize: 400,
  },
  {
    id: 17,
    name: 'Previous Policy Premium',
    description: 'Expiring premium',
    required: 'Yes',
    maxSize: 15,
  },
  {
    id: 18,
    name: 'Notes',
    description: 'Any other information',
    required: 'No',
    maxSize: 500,
  },
];
function handleFileUpload(event) {
  files = event;
  file = event[0].file;
  uploadForm.csvFile = event[0];
}
function onSubmit(isValid) {
  if (isValid) {
    isUploading.value = true;
    let formData = new FormData();
    formData.append('file_name', file);
    formData.append('lob', uploadForm.lob);
    formData.append('renewals_upload_type', 'update');
    axios
      .post('/renewals/non-motor/upload-update', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then(() => {
        notification.success({
          title: 'Uploaded renewals records has been stored',
          position: 'top',
        });
        files = [];
        uploadForm.setError([]);
        uploadForm.errors.file_name = [];
        uploadForm.errors.type = [];
        uploadForm.csvFile = '';
      })
      .catch(function (error) {
        uploadForm.setError(error.response.data.errors);
        const title =
          error.response?.data?.message ||
          'Error while uploading . Please try again';
        notification.error({
          title: title,
          position: 'top',
        });
        console.log('FAILURE!!');
      })
      .finally(() => {
        isUploading.value = false;
      });
  }
}

const can = permission => useCan(permission);

const quoteTypesOptions = computed(() => {
  const quoteTypesOptions = [
    ...Object.keys(page.props.lobs).map(text => ({
      label: text,
      value: page.props.lobs[text],
    })),
  ];
  return quoteTypesOptions;
});

// Computed property to determine which table data to display based on selected LoB
const currentTableData = computed(() => {
  return uploadForm.lob === page.props.lobs.Health
    ? tableDataHealth
    : tableData;
});

// Set default LoB
uploadForm.lob = page.props.lobs.Home;
</script>

<template>
  <div>
    <Head title="Upload & Update Renewals" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Upload & Update Renewals (for non motor only)
      </h2>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
        <ComboBox
          v-model="uploadForm.lob"
          label="Line of Business"
          placeholder="Select Line of Business"
          :options="quoteTypesOptions"
          class="w-full"
          :single="true"
        />
      </div>
      <Dropzone
        v-model="uploadForm.csvFile"
        @changeMethod="handleFileUpload($event)"
        :error="uploadForm.errors.type"
      ></Dropzone>
      <a
        v-for="uploadFile in files"
        class="block px-2 py-2 border rounded mt-2 mb-2 text-xs hover:text-primary-600 truncate"
      >
        {{ uploadFile.file.name }}
      </a>
      <span class="text-red-500" v-for="error in uploadForm.errors.file_name">
        {{ error }}
      </span>
      <span class="text-red-500" v-for="error in uploadForm.errors.type">
        {{ error }}
      </span>
      <x-alert>
        <h4 class="text-red-500"><b>Import Instructions must be follow:</b></h4>
        <ul class="list-disc text-sm pl-4">
          <li>
            Download the sample xlsx file, modify the data according to the
            recommendations for a successful import.
          </li>
          <li>File must be a xlsx file with the following fields.</li>
          <li>Please ensure there are no commas in file.</li>
          <li>First row will be skipped while uploading.</li>
          <li>
            Please ensure there are no spaces in start and end of columns data.
          </li>
          <li>Please ensure max allowed size is 2mb (2048kb).</li>
          <li>
            Please ensure columns header and allocation same as per given in
            sample xlsx file.
          </li>
          <li>
            Please ensure all required columns data filled in the xlsx file.
          </li>
          <li>Arabic is not supported in xlsx file upload.</li>
        </ul>
      </x-alert>
      <div class="flex justify-end gap-3 my-4">
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          :disabled="isUploading"
        >
          <template v-if="isUploading">
            <div class="flex items-center gap-2">
              <svg
                class="animate-spin h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              <span>Uploading...</span>
            </div>
          </template>
          <template v-else> Upload </template>
        </x-button>
      </div>
      <div class="flex items-center my-4">
        <!-- Home LOB download button -->
        <x-button
          v-if="uploadForm.lob === page.props.lobs.Home"
          :href="
            azureStorageUrl +
            azureStorageContainer +
            '/renewals/renewals_home_upload_update_m4.xlsx'
          "
          color="green"
          icon-right="cells"
        >
          Download Sample XLSX
        </x-button>
        <!-- Health LOB download button -->
        <x-button
          v-if="uploadForm.lob === page.props.lobs.Health"
          :href="
            azureStorageUrl +
            azureStorageContainer +
            '/renewals/renewals_health_upload_update_m4.xlsx'
          "
          color="green"
          icon-right="cells"
        >
          Download Sample XLSX
        </x-button>
      </div>
      <div class="vue3-easy-data-table tablefixed">
        <div
          class="vue3-easy-data-table__main fixed-header table-fixed hoverable border-cell"
        >
          <table class="table table-bordered w-full">
            <thead class="vue3-easy-data-table__header">
              <tr>
                <th>Sr No.</th>
                <th>Field name</th>
                <th>Description</th>
                <th>Required</th>
                <th>Max size</th>
              </tr>
            </thead>
            <tbody class="vue3-easy-data-table__body">
              <tr v-for="(row, index) in currentTableData" :key="index">
                <td>{{ row.id }}</td>
                <td>{{ row.name }}</td>
                <td>{{ row.description }}</td>
                <td>{{ row.required }}</td>
                <td>{{ row.maxSize }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </x-form>
  </div>
</template>
