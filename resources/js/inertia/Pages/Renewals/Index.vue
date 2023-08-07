<script setup>
import {XSelect} from "@indielayer/ui";

const notification = useToast();
const uploadForm = useForm({
    csvFile:'',
    skipPlans:''
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
        formData.append('skip_plans', uploadForm.skipPlans);
        formData.append('renewals_upload_type','update');
        axios.post('/renewals/upload-update',
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        ).then(function(){
            notification.success({
                title: 'Uploaded renewals records has been stored',
                position: 'top',
            });
            document.getElementById("file_name").value = "";
        })
            .catch(function(error){
                uploadForm.setError(error.response.data.errors);
                notification.error({
                    title: 'Error while uploading . Please try again',
                    position: 'top',
                });
                console.log('FAILURE!!');
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

const skipOptions = [
    { value: 0, text: 'No' },
    { value: 1, text: 'Yes' },
    { value: 2, text: 'Skip Plans for Non GCC, Bike, Company Vehicles' },
];
const can = permission => useCan(permission);



</script>

<template>
    <div>
        <Head title="Upload & Update Renewals" />

        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Upload & Update Renewals (for motor only)</h2>
        </div>
        <x-divider class="my-4" />
        <!--   filters     -->
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
                <x-input
                    v-model="uploadForm.csvFile"
                    type="file"
                    name="previous_quote_policy_number"
                    label="File"
                    class="w-full"
                    :error="uploadForm.errors.type"
                    id="full_name"
                    @change="handleFileUpload( $event )"
                />
            </div>
            <div class="grid sm:grid-cols-2 md:grid-cols-2 gap-4">
                <x-select
                    v-model="uploadForm.skipPlans"
                    name="previous_quote_policy_number"
                    label="Skip Plans"
                    class="w-full"
                    :error="uploadForm.errors.skip_plans"
                    :options="
                        skipOptions.map(item => ({
                          value: item.value,
                          label: item.text,
                        }))"
                />
            </div>
            <div>
                <span class="text-sm"><strong>Skip Plans - No</strong> - Plans will be fetched/refreshed during fetch plans process.</span>
                <br/>
                <span class="text-sm"><strong>Skip Plans - Yes</strong> - <span class="text-red-500">Plans will be not be refreshed during fetch plans process. make sure plans are already fetched for the batch being uploaded.</span></span>
            </div>
            <div>
                <div class="text-red-500 my-6">
                    <p></p><h4 class="required"><b>Import Instructions must be follow:</b></h4><p></p>
                    <p></p><ul class="required" style="font-weight:bold;">
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
                <span>Download Sample XLSX</span> <a :href="azureStorageUrl+azureStorageContainer+'/renewals/renewals_upload_create_m3.xlsx'"  ><img src="https://img.icons8.com/color/40/000000/ms-excel.png" alt="xlsx" border="0"></a>
            </div>
        <div class="item form-group">
            <div class="col-md-12 scrollable">
                <table class="table table-bordered">
                    <thead class="vue3-easy-data-table__header">
                    <tr><th>Sr No.</th>
                        <th>Field name</th>
                        <th>Description</th>
                        <th>Required</th>
                        <th>Max size</th></tr>
                    </thead>
                    <tbody class="vue3-easy-data-table__body">
                    <tr><td>1</td><td>Customer Name</td><td>Customer Name</td><td>No</td><td>100</td></tr>
                    <tr><td>2</td><td>Customer Email</td><td>Customer Email</td><td>No</td><td>255</td></tr>
                    <tr><td>3</td><td>Customer mobile</td><td>Customer Mobile Number - only numeric data</td><td>No</td><td>100</td></tr>
                    <tr><td>4</td><td>Insurance Type</td><td>Insurance Type</td><td>Yes</td><td>10</td></tr>
                    <tr><td>5</td><td>Insurance Provider</td><td>Insurance Provider Code</td><td>No</td><td>20</td></tr>
                    <tr><td>6</td><td>Product Type</td><td>Type of Insurance | Comprehensive or Third Party Only</td><td>No</td><td>50</td></tr>
                    <tr><td>7</td><td>Advisor Email</td><td>Advisor Email</td><td>No</td><td>100</td></tr>
                    <tr><td>8</td><td>Policy Number</td><td>Previous Quote Policy Number</td><td>Yes</td><td>100</td></tr>
                    <tr><td>9</td><td>Policy End Date</td><td>End Date of the insurance Policy - Format should be DD/MM/YYYY</td><td>Yes</td><td>10</td></tr>
                    <tr><td>10</td><td>Batch</td><td>Batch number assigned</td><td>Yes</td><td>25</td></tr>
                    <tr><td>11</td><td>Car Make</td><td>Car Make Information</td><td>No</td><td>50</td></tr>
                    <tr><td>12</td><td>Car Model</td><td>Car Model Information</td><td>No</td><td>50</td></tr>
                    <tr><td>13</td><td>Model Year</td><td>Vehicle Year of manufacture</td><td>No</td><td>4</td></tr>
                    <tr><td>14</td><td>Date Of Birth</td><td>Customer Date of Birth</td><td>No</td><td>10</td></tr>
                    <tr><td>15</td><td>Driving Experience</td><td>Driving Experience</td><td>No</td><td>50</td></tr>
                    <tr><td>16</td><td>Nationality</td><td>Nationality</td><td>No</td><td>60</td></tr>
                    <tr><td>17</td><td>Provider Name</td><td>Insurance Provider Name</td><td>No</td><td>100</td></tr>
                    <tr><td>18</td><td>Plan Name</td><td>Insurer Plan Name</td><td>No</td><td>100</td></tr>
                    <tr><td>19</td><td>Repair Type</td><td>Repair Type</td><td>No</td><td>100</td></tr>
                    <tr><td>20</td><td>Claim History</td><td>Claim History</td><td>No</td><td>100</td></tr>
                    <tr><td>21</td><td>NC Letter</td><td>NC Letter - Yes/No</td><td>No</td><td>100</td></tr>
                    <tr><td>22</td><td>Insurer Quote No.</td><td>Insurer Quote No.</td><td>No</td><td>100</td></tr>
                    <tr><td>23</td><td>Car Value</td><td>Car Value (From Insurer)</td><td>No</td><td>50</td></tr>
                    <tr><td>24</td><td>Renewal Premium</td><td>Renewal Premium</td><td>No</td><td>50</td></tr>
                    <tr><td>25</td><td>Excess</td><td>Excess</td><td>No</td><td>20</td></tr>
                    <tr><td>26</td><td>Ancillary Excess</td><td>Ancillary Excess</td><td>No</td><td>20</td></tr>
                    <tr><td>27</td><td>PAB Driver</td><td>PAB Driver</td><td>No</td><td>20</td></tr>
                    <tr><td>28</td><td>Amount - PAB Driver</td><td>Amount - PAB Driver</td><td>No</td><td>20</td></tr>
                    <tr><td>29</td><td>PAB Passenger</td><td>PAB Passenger</td><td>No</td><td>20</td></tr>
                    <tr><td>30</td><td>Amount - PAB Passenger</td><td>Amount - PAB Passenger</td><td>No</td><td>20</td></tr>
                    <tr><td>31</td><td>Rent a car</td><td>Rent a car</td><td>No</td><td>20</td></tr>
                    <tr><td>32</td><td>Amount- Rent a Car</td><td>Amount- Rent a Car</td><td>No</td><td>20</td></tr>
                    <tr><td>33</td><td>Oman cover</td><td>Amount - Oman cover</td><td>No</td><td>20</td></tr>
                    <tr><td>34</td><td>Amount - Oman cover</td><td>Amount - Oman cover</td><td>No</td><td>20</td></tr>
                    <tr><td>35</td><td>Road Side Assistance</td><td>Road Side Assistance</td><td>No</td><td>20</td></tr>
                    <tr><td>36</td><td>Amount - Road Side Assistance</td><td>Amount - Road Side Assistance</td><td>No</td><td>20</td></tr>
                    <tr><td>37</td><td>First Year of Registration</td><td>First Year of Registration</td><td>No</td><td>20</td></tr>
                    <tr><td>38</td><td>Trim</td><td>Trim</td><td>No</td><td>50</td></tr>
                    <tr><td>39</td><td>Registration Location</td><td>Registration Location</td><td>No</td><td>50</td></tr>
                    <tr><td>40</td><td>Previous Advisor Email</td><td>Previously assigned Advisor Email</td><td>No</td><td>100</td></tr>
                    <tr><td>41</td><td>Notes</td><td>Any other Information</td><td>No</td><td>200</td></tr>
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
