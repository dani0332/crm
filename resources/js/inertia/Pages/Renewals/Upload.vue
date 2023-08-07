<script setup>
import {XSelect} from "@indielayer/ui";

const notification = useToast();
const uploadForm = useForm({
    csvFile:''
});
defineProps({
    azureStorageUrl: String,
    azureStorageContainer: String,
});


let errors = {
    type:'',
    step:''
}
let file = '';
function handleFileUpload( event ){
    file = event.target.files[0];
}
function onSubmit(isValid) {
    if (isValid) {
        let formData = new FormData();
        formData.append('file_name',file);
        formData.append('renewals_upload_type','create');
        axios.post('/renewals/upload-create',
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        ).then(()=>{
            console.log('SUCCESS!!');
            notification.success({
                title: 'Uploaded renewals records has been stored',
                position: 'top',
            });
            uploadForm.csvFile = '';
            document.getElementById("file_name").value = "";
        })
            .catch((error)=>{
                console.log('FAILURE!!');
                uploadForm.setError(error.response.data.errors);
                notification.error({
                    title: 'Error while uploading . Please try again',
                    position: 'top',
                });
                document.getElementById("file_name").value = "";

            });
    } else {
        notification.error({
            title: 'Error. Please try again',
            position: 'top',
        });
    }
}





onMounted(() => {

});

const can = permission => useCan(permission);



</script>

<template>
    <div>
        <Head title="Upload & Update Renewals" />

        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Upload & Update Renewals</h2>
        </div>
        <x-divider class="my-4" />
        <!--   filters     -->
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
                <x-input
                    v-model="uploadForm.csvFile"
                    type="file"
                    name="file_name"
                    label="File"
                    class="w-full"
                    id="file_name"
                    :error="uploadForm.errors.type"
                    @change="handleFileUpload( $event )"
                />
            </div>
            <div>
                <div class="text-red-500 my-6">
                    <p></p><h4 class="required"><b>Import Instructions must be follow:</b></h4><p></p>
                    <p></p>
                    <ul class="required" style="font-weight:bold;">
                        <li>Download the sample xlsx file, modify the data according to the recommendations for a successful import.</li>
                        <li>File must be a xlsx file with the following fields.</li>
                        <li>Please ensure there are no commas in file.</li>
                        <li>First row will be skipped while uploading.</li>
                        <li>Please ensure there are no spaces in start and end of columns data.</li>
                        <li>Please ensure max allowed size is 2mb (2048kb).</li>
                        <li>Please ensure columns header and allocation same as per given in sample xlsx file.</li>
                        <li>Please ensure all required columns data filled in the xlsx file.</li>
                        <li>Arabic is not supported in xlsx file upload.</li>
                    </ul>
                    <p></p>
                </div>
            </div>
            <div class="flex items-center">
                <span>Download Sample XLSX</span>
                <a :href="azureStorageUrl+azureStorageContainer+'/renewals/renewals_upload_create_m3.xlsx'" target="_blank"><img src="https://img.icons8.com/color/40/000000/ms-excel.png" alt="xlsx" border="0"></a>
            </div>
        <div class="item form-group">
            <div class="col-md-12 scrollable">
                <table class="table table-bordered w-full">
                    <thead class="vue3-easy-data-table__header">
                    <tr><th>Sr No.</th>
                        <th>Field name</th>
                        <th>Description</th>
                        <th>Required</th>
                        <th>Max size</th></tr>
                    </thead>
                    <tbody class="vue3-easy-data-table__body">
                    <tr><td>1</td><td>Customer Name</td><td style="width:450px;">Customer Name should only be in letters - no numbers allowed</td><td>Yes</td><td>100</td></tr>
                    <tr><td>2</td><td>Customer Email</td><td>Customer Email Id</td><td>Yes</td><td>255</td></tr>
                    <tr><td>3</td><td>Customer Mobile No.</td><td>Customer Mobile Number - only numeric data</td><td>Yes</td><td>100</td></tr>
                    <tr><td>4</td><td>Insurance Type</td><td>Insurance Type</td><td>Yes</td><td>4</td></tr>
                    <tr><td>5</td><td>Insurer Provider</td><td>Insurance Provider - should match the CDB data</td><td>Yes</td><td>100</td></tr>
                    <tr><td>6</td><td>Product</td><td>Quotation asked against the insurance line</td><td>Yes</td><td>100</td></tr>
                    <tr><td>7</td><td>Product Type</td><td>Type of Insurance | Comprehensive or Third Party Only</td><td>No</td><td>100</td></tr>
                    <tr><td>8</td><td>Advisor Email</td><td>Advisor Email</td><td>No</td><td>100</td></tr>
                    <tr><td>9</td><td>Policy Number</td><td>Policy number assigned</td><td>Yes</td><td>100</td></tr>
                    <tr><td>10</td><td>Policy Start Date</td><td>Start Date of the insurance - Format should be DD/MM/YYYY</td><td>No</td><td>10</td></tr>
                    <tr><td>11</td><td>Policy End Date</td><td>End Date of the insurance - Format should be DD/MM/YYYY</td><td>Yes</td><td>10</td></tr>
                    <tr><td>12</td><td>Batch</td><td>Batch number assigned</td><td>No</td><td>25</td></tr>
                    <tr><td>13</td><td>Car Make</td><td>Car Make Information</td><td>No</td><td>50</td></tr>
                    <tr><td>14</td><td>Car Model</td><td>Car Model Information</td><td>No</td><td>50</td></tr>
                    <tr><td>15</td><td>Model Year</td><td>Vehicle Year of manufacture</td><td>No</td><td>4</td></tr>
                    <tr><td>16</td><td>Previous Advisor Email</td><td>Previously assigned Advisor Email</td><td>No</td><td>100</td></tr>
                    <tr><td>17</td><td>Object</td><td>Information against the quotation</td><td>No</td><td>200</td></tr>
                    <tr><td>18</td><td>Gross Premium</td><td>Gross Premium amount</td><td>No</td><td>25</td></tr>
                    <tr><td>19</td><td>Sales Channel</td><td>Source of the quotation</td><td>No</td><td>100</td></tr>
                    <tr><td>20</td><td>Notes</td><td>Any other Information</td><td>No</td><td>200</td></tr>
                    <tr><td>21</td><td>Plan Name</td><td>Plan Name - Effective for Health Only</td><td>No</td><td>200</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
            <div class="flex justify-end gap-3 my-4">
                <x-button size="sm" color="#ff5e00" type="submit">Upload</x-button>
            </div>
        </x-form>

    </div>
</template>
