<script setup>
const notification = useToast();
const uploadForm = useForm({
  csvFile: '',
});
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
    console.log('file', file);
    // return;
    formData.append('file_name', file);
    formData.append('renewals_upload_type', 'create');
    axios
      .post('/quotes/travel-upload-create', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then(() => {
        console.log('SUCCESS!!');
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
      .catch(error => {
        console.log('FAILURE!!');
        uploadForm.setError(error.response.data.errors);
        notification.error({
          title: 'Error while uploading . Please try again',
          position: 'top',
        });
        document.getElementById('file_name').value = '';
      });
  } else {
    notification.error({
      title: 'Error. Please try again',
      position: 'top',
    });
  }
}
const can = permission => useCan(permission);
</script>

<template>
  <div>
    <Head title="Upload Travel Expired Policies For Renewal" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        Upload Travel Expired Policies For Renewal
      </h2>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4"></div>

      <Dropzone
        v-model="uploadForm.csvFile"
        @change="handleFileUpload($event)"
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
          <li>Remove unnecessary formatting.</li>
          <li>Unhide all columns.</li>
        </ul>
      </x-alert>
      <div class="flex justify-end gap-3 my-4">
        <x-button size="sm" color="#ff5e00" type="submit">Upload</x-button>
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
              <tr>
                <td>1</td>
                <td>REF-ID</td>
                <td style="width: 450px">Reference Id</td>
                <td>Yes</td>
                <td>20</td>
              </tr>
              <tr>
                <td>2</td>
                <td>First Name</td>
                <td>Customer First Name</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>3</td>
                <td>Last Name</td>
                <td>Customer Last Name</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>4</td>
                <td>Lead Status</td>
                <td>Lead Status</td>
                <td>Yes</td>
                <td>50</td>
              </tr>
              <tr>
                <td>5</td>
                <td>Advisor</td>
                <td>Advisor email</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>6</td>
                <td>Source</td>
                <td>Lead Source (Revival)</td>
                <td>Yes</td>
                <td>8</td>
              </tr>
              <tr>
                <td>7</td>
                <td>Batch Number</td>
                <td>Batch Number</td>
                <td>Yes</td>
                <td>20</td>
              </tr>
              <tr>
                <td>8</td>
                <td>Created Date</td>
                <td>Created Date (By Default system will take today's date)</td>
                <td>No</td>
                <td>20</td>
              </tr>
              <tr>
                <td>9</td>
                <td>Premium</td>
                <td>Premium</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>10</td>
                <td>Policy Number</td>
                <td>Policy Number</td>
                <td>No</td>
                <td>10</td>
              </tr>
              <tr>
                <td>11</td>
                <td>Destination</td>
                <td>Destination Text</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>12</td>
                <td>Currently Located in</td>
                <td>Currently Located in (UAE, OUTSIDE_UAE)</td>
                <td>No</td>
                <td>25</td>
              </tr>
              <tr>
                <td>13</td>
                <td>Expiry Date</td>
                <td>Expiry Date Information - Format should be DD/MM/YYYY</td>
                <td>No</td>
                <td>20</td>
              </tr>
              <tr>
                <td>14</td>
                <td>Is Ecommerce</td>
                <td>Is Ecommerce (Yes/No)</td>
                <td>No</td>
                <td>3</td>
              </tr>
              <tr>
                <td>15</td>
                <td>Payment Status</td>
                <td>Payment Status Text</td>
                <td>No</td>
                <td>20</td>
              </tr>
              <tr>
                <td>16</td>
                <td>Customer Mobile#</td>
                <td>Customer Mobile Number</td>
                <td>No</td>
                <td>20</td>
              </tr>
              <tr>
                <td>17</td>
                <td>Customer Email#</td>
                <td>Customer Email address</td>
                <td>No</td>
                <td>100</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </x-form>
  </div>
</template>
