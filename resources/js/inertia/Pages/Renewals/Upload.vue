<script setup>
const notification = useToast();
const uploadForm = useForm({
  csvFile: '',
});
defineProps({
  azureStorageUrl: String,
  azureStorageContainer: String,
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
      .post('/renewals/upload-create', formData, {
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
    <Head title="Upload & Create Renewals" />

    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Upload & Create Renewals</h2>
    </div>
    <x-divider class="my-4" />
    <!--   filters     -->
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
        <!-- <x-input
                    v-model="uploadForm.csvFile"
                    type="file"
                    name="file_name"
                    label="File"
                    class="w-full"
                    id="file_name"
                    :error="uploadForm.errors.type"
                    @change="handleFileUpload( $event )"
                />-->
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
        <x-button size="sm" color="#ff5e00" type="submit">Upload</x-button>
      </div>
      <div class="flex items-center my-4">
        <x-button
          :href="
            azureStorageUrl +
            azureStorageContainer +
            '/renewals/renewals_upload_create_m3.xlsx'
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
              <tr>
                <td>1</td>
                <td>Customer Name</td>
                <td style="width: 450px">
                  Customer Name should only be in letters - no numbers allowed
                </td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>2</td>
                <td>Customer Email</td>
                <td>Customer Email Id</td>
                <td>Yes</td>
                <td>255</td>
              </tr>
              <tr>
                <td>3</td>
                <td>Customer Mobile No.</td>
                <td>Customer Mobile Number - only numeric data</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>4</td>
                <td>Insurance Type</td>
                <td>Insurance Type</td>
                <td>Yes</td>
                <td>4</td>
              </tr>
              <tr>
                <td>5</td>
                <td>Insurer Provider</td>
                <td>Insurance Provider - should match the CDB data</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>6</td>
                <td>Product</td>
                <td>Quotation asked against the insurance line</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>7</td>
                <td>Product Type</td>
                <td>Type of Insurance | Comprehensive or Third Party Only</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>8</td>
                <td>Advisor Email</td>
                <td>Advisor Email</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>9</td>
                <td>Policy Number</td>
                <td>Policy number assigned</td>
                <td>Yes</td>
                <td>100</td>
              </tr>
              <tr>
                <td>10</td>
                <td>Policy Start Date</td>
                <td>
                  Start Date of the insurance - Format should be DD/MM/YYYY
                </td>
                <td>No</td>
                <td>10</td>
              </tr>
              <tr>
                <td>11</td>
                <td>Policy End Date</td>
                <td>End Date of the insurance - Format should be DD/MM/YYYY</td>
                <td>Yes</td>
                <td>10</td>
              </tr>
              <tr>
                <td>12</td>
                <td>Batch</td>
                <td>Batch number assigned</td>
                <td>No</td>
                <td>25</td>
              </tr>
              <tr>
                <td>13</td>
                <td>Car Make</td>
                <td>Car Make Information</td>
                <td>No</td>
                <td>50</td>
              </tr>
              <tr>
                <td>14</td>
                <td>Car Model</td>
                <td>Car Model Information</td>
                <td>No</td>
                <td>50</td>
              </tr>
              <tr>
                <td>15</td>
                <td>Model Year</td>
                <td>Vehicle Year of manufacture</td>
                <td>No</td>
                <td>4</td>
              </tr>
              <tr>
                <td>16</td>
                <td>Previous Advisor Email</td>
                <td>Previously assigned Advisor Email</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>17</td>
                <td>Object</td>
                <td>Information against the quotation</td>
                <td>No</td>
                <td>200</td>
              </tr>
              <tr>
                <td>18</td>
                <td>Gross Premium</td>
                <td>Gross Premium amount</td>
                <td>No</td>
                <td>25</td>
              </tr>
              <tr>
                <td>19</td>
                <td>Sales Channel</td>
                <td>Source of the quotation</td>
                <td>No</td>
                <td>100</td>
              </tr>
              <tr>
                <td>20</td>
                <td>Notes</td>
                <td>Any other Information</td>
                <td>No</td>
                <td>200</td>
              </tr>
              <tr>
                <td>21</td>
                <td>Plan Name</td>
                <td>Plan Name - Effective for Health Only</td>
                <td>No</td>
                <td>200</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </x-form>
  </div>
</template>
