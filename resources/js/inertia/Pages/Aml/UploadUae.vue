<script setup>
defineProps({ errors: Object });

const notification = useNotifications('toast');
const page = usePage();

const { isRequired } = useRules();
const uploadForm = useForm({
  file_name: '',
});

function onSubmit(isValid) {
  if (isValid) {
    uploadForm.post(`/kyc/aml/upload/uae-list`, {
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

onMounted(() => {});
</script>

<template>
  <div>
    <Head title="Upload UAE Sanction List" />

    <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
      <h2 class="text-xl font-semibold">Upload UAE Sanction List</h2>
    </div>

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
                  <li>File must be a xls file with the following fields.</li>
                  <li>Please ensure max allowed size is 2mb (2048kb)</li>
                  <li>
                    File name should always be =>
                    <strong>UAESanctionlist.xls</strong>
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <hr class="my-4" />
          <div class="flex justify-end">
            <x-button type="submit" color="#ff5e00" size="sm">
              Upload
            </x-button>
          </div>
        </form>
        <div class="flex flex-col gap-4"></div>
      </div>
    </div>
  </div>
</template>
