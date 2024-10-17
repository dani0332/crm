<script setup>
const notification = useToast();
const uploadForm = useForm({
  csvFile: '',
});
const contactLoader = ref(false);

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
      .post('/rates-coverages/upload-coverages', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then(() => {
        contactLoader.value = false;
        notification.success({
          title: 'Coverages upload is being processed.',
          position: 'top',
        });
        files = [];
        uploadForm.setError([]);
        uploadForm.errors.file_name = [];
        uploadForm.errors.type = [];
        uploadForm.csvFile = '';
      })
      .catch(error => {
        contactLoader.value = false;
        uploadForm.setError(
          error.response.data.error || error.response.data.errors.file_name[0],
        );
        notification.error({
          title:
            error.response.data.error ||
            error.response.data.errors.file_name[0],
          position: 'top',
        });
        document.getElementById('file_name').value = '';
      });
  } else {
    contactLoader.value = false;
    notification.error({
      title: 'Error. Please try again',
      position: 'top',
    });
  }
}
</script>

<template>
  <div>
    <Head title="Upload & Create Renewals" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Upload Coverages</h2>
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
          <li>File must be a xlsx file.</li>
          <li>Please ensure there are no commas in file.</li>
          <li>
            Please ensure there are no spaces in start and end of columns data.
          </li>
          <li>Please ensure max allowed size is 2mb (2048kb).</li>
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
    </x-form>
  </div>
</template>
