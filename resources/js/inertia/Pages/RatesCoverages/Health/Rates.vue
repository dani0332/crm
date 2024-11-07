<script setup>
import dayjs from 'dayjs';
defineProps({
  rates: Array,
  azureStorageUrl: String,
  azureStorageContainer: String,
});
const notification = useToast();
const uploadForm = useForm({
  csvFile: '',
});
const contactLoader = ref(false);
const page = usePage();

const tableHeader = [
  { text: 'ID', value: 'upload_id' },
  { text: 'File Name', value: 'fileName' },
  { text: 'Status', value: 'status' },
  { text: 'Total Record', value: 'totalRecords' },
  { text: 'Uploaded Record', value: 'good' },
  { text: 'Bad Record', value: 'cannotUpload' },
  { text: 'Error', value: 'error' },
  { text: 'Created At', value: 'created_at' },
  { text: 'Updated At', value: 'updated_at' },
];

let errors = {
  type: '',
  step: '',
};
let file = '';
let files = [];
function handleFileUpload(event) {
  files = event;
  file = event[0].file;
  uploadForm.csvFile = event[0];
}

function onSubmit(isValid) {
  if (isValid) {
    let formData = new FormData();
    formData.append('file_name', file);
    contactLoader.value = true;
    axios
      .post('/rates-coverages/upload-rates', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then(() => {
        contactLoader.value = false;
        notification.success({
          title: 'Rates upload is being processed.',
          position: 'top',
        });
        files = [];
        uploadForm.setError([]);
        uploadForm.errors.file_name = [];
        uploadForm.errors.type = [];
        uploadForm.csvFile = '';
        router.reload({
          preserveState: true,
          preserveScroll: true,
        });
      })
      .catch(error => {
        if (error.response.data.message) {
          notification.error({
            title: error.response.data.message,
            position: 'top',
          });
        }
        contactLoader.value = false;
        uploadForm.setError(
          error.response.data.error || error.response.data.errors.file_name?.[0],
        );
        if(error.response.data.error) {
          notification.error({
            title:
              error.response.data.error,
            position: 'top',
          });
        }

        uploadForm.csvFile = '';
        files = [];
        file = '';
      });
  } else {
    contactLoader.value = false;
    notification.error({
      title: 'Error. Please try again',
      position: 'top',
    });
  }
}
const formattedRates = computed(() => {
  return page.props?.rates.data.map(item => {
    return {
      ...item,
      created_at: item.created_at ? dayjs(item.created_at).format('DD-MM-YYYY HH:mm:ss') : '',
      updated_at: item.updated_at ? dayjs(item.updated_at).format('DD-MM-YYYY HH:mm:ss') : '',
    };
  });
});
</script>

<template>
  <div>
    <Head title="Upload & Create Renewals" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Upload Rates</h2>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
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
        {{ uploadFile.original_name || uploadFile.name }}
      </a>
      <span class="text-red-500" v-for="error in uploadForm.errors.type">
        {{ error }}
      </span>
      <span class="text-red-500" v-for="error in uploadForm.errors.file_name">
        {{ error }}
      </span>
      <x-alert class="mt-2">
        <h4 class="text-red-500"><b>Import Instructions must be follow:</b></h4>
        <ul class="list-disc text-sm pl-4">
          <li>
            Download the sample xlsx file, modify the data according to the
            recommendations for a successful import.
          </li>
          <li>File must be a xlsx file.</li>
          <li>Please ensure there are no commas in file.</li>
          <li>
            Please ensure there are no spaces in start and end of columns data.
          </li>
          <li>Please ensure max allowed size is 5mb (5120kb).</li>
          <li>
            Please ensure all required columns data filled in the xlsx file.
          </li>
        </ul>
      </x-alert>
      <div class="flex justify-end gap-3 my-4">
        <x-button
          size="sm"
          color="#ff5e00"
          type="submit"
          :loading="contactLoader"
          >Upload</x-button
        >
      </div>
      <div class="flex items-center">
        <x-button
          :href="
            azureStorageUrl +
            azureStorageContainer +
            '/ratings/health/sample/sample_upload_rates.xlsx'
          "
          color="green"
          icon-right="cells"
        >
          Download Sample XLSX
        </x-button>
      </div>
    </x-form>

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Uploaded Rates</h2>
      <div class="space-x-3"></div>
    </div>
    <x-divider class="my-4" />

    <DataTable
      table-class-name="tablefixed"
      :headers="tableHeader"
      :items="formattedRates || []"
      border-cell
      hide-rows-per-page
      hide-footer
    >
    </DataTable>

    <Pagination
      :links="{
        next: rates.next_page_url,
        prev: rates.prev_page_url,
        current: rates.current_page,
        from: rates.from,
        to: rates.to,
      }"
    />
  </div>
</template>
