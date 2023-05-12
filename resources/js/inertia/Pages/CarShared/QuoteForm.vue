<script setup>
import {XTextarea} from "@indielayer/ui";
import {computed} from "vue";

const page = usePage();
const toast = useToast();
const notification = useNotifications('toast');
const comp_for_car_revival = 'car-revival';
const comp_for_car = 'car';

const dateFormat = date =>
    date ? useDateFormat(date, 'Y-m-d').value : '-';

const props = defineProps({
    dynamic_route: {
        type: String,
        default: '',
        required: true
    },
});

const nationalities = computed(() => {
    return page.props.form_options.nationalities.map(nationality => ({
        value: nationality.id,
        label: nationality.text,
    }));
});

const vehicleTypeOptions = computed(() => {
    return page.props.form_options.vehicle_types.map(vehicle_type => ({
        value: vehicle_type.id,
        label: vehicle_type.text,
    }));
});

const typeOfInsuranceOptions = computed(() => {
    return page.props.form_options.types_of_insurance.map(type_of_insurance => ({
        value: type_of_insurance.id,
        label: type_of_insurance.text,
    }));
});

const currentlyInsuredWith = computed(() => {
    return page.props.form_options.currently_insured_with_options.map(
        currently_insured_with => ({
            value: currently_insured_with.id,
            label: currently_insured_with.text,
        }),
    );
});

const uaeLicenseHeldFor = computed(() => {
    return page.props.form_options.uae_license_help_for.map(
        uae_license_help_for => ({
            value: uae_license_help_for.id,
            label: uae_license_help_for.text,
        }),
    );
});

const emiratesOfRegistration = computed(() => {
    return page.props.form_options.emirate_of_visa.map(
        emirate_of_visa => ({
            value: emirate_of_visa.id,
            label: emirate_of_visa.text,
        }),
    );
});

const carMakes = computed(() => {
    return page.props.form_options.car_make.map(
        car_make => ({
            value: car_make.id,
            label: car_make.text,
        }),
    );
});

const yearOfManufacture = computed(() => {
    return page.props.form_options.year_of_manufacture.map(
        year_of_manufacture => ({
            value: year_of_manufacture.text,
            label: year_of_manufacture.text,
        }),
    );
});


const claimHistory = computed(() => {
    return page.props.form_options.claim_history.map(
        claim_history => ({
            value: claim_history.id,
            label: claim_history.text,
        }),
    );
});

const carFetchModel = ref('');
const carModel = computed(() => {
    // return carFetchModel.map(
    //     carFetchModel => ({
    //         value: carFetchModel.id,
    //         label: carFetchModel.text,
    //     }),
    // );
});

console.log(page.props.form_options);
console.log(page.props.quote);

const conditionallyTitle = computed(() => {
    return props.dynamic_route === comp_for_car ? 'Car' : 'Car Revival';
});


const quoteForm = useForm({
    renewal_batch: page.props.quote?.renewal_batch || '',
    first_name: page.props.quote?.first_name || '',
    last_name: page.props.quote?.last_name || '',
    dob: page.props.quote?.dob || null,
    email: page.props.quote?.email || '',
    mobile_no: page.props.quote?.mobile_no || '',
    nationality_id: page.props.quote?.nationality_id || null,
    uae_license_held_for_id: page.props.quote?.uae_license_held_for_id || null,
    back_home_license_held_for_id: page.props.quote?.back_home_license_held_for_id || null,
    car_make_id : page.props.quote?.car_make_id || null,
    car_model_id : page.props.quote?.car_model_id || null,
    cylinder : page.props.quote?.cylinder || '',
    // trim_id : page.props.quote?.car_model_id || null,
    year_of_manufacture : page.props.quote?.year_of_manufacture || '',
    car_value : page.props.quote?.car_value || '',
    // car_value_at_enquiry : page.props.quote?.car_value || null,
    vehicle_type_id : page.props.quote?.vehicle_type_id || null,
    seat_capacity : page.props.quote?.seat_capacity || '',
    emirate_of_registration_id : page.props.quote?.emirate_of_registration_id || null,
    car_type_insurance_id : page.props.quote?.car_type_insurance_id || null,
    currently_insured_with : page.props.quote?.currently_insured_with || null,
    claim_history_id : page.props.quote?.claim_history_id || null,
    // prevous_insurer :
    previous_quote_policy_number : page.props.quote?.previous_quote_policy_number || '',
    previous_policy_expiry_date : page.props.quote?.previous_policy_expiry_date || '',
    additional_notes : page.props.quote?.additional_notes || ''
});

// const carModel = fetch(`/car-model-by-id?id=${quoteForm.car_make_id}`).then(cars => {
//     return cars;
// });
//
// console.log(carModel);

const { isRequired, isEmail } = useRules();

const formFieldReq = reactive({
    first_name: false,
    last_name: false,
    dob: false,
    nationality_id: false,
    uae_license_held_for_id: false,
    car_make_id: false,
    car_model_id: false,
    cylinder: false,
    year_of_manufacture: false,
    vehicle_type_id: false,
    seat_capacity: false,
    emirate_of_registration_id: false,
    car_type_insurance_id: false,
    currently_insured_with: false,
    claim_history_id: false,
});

const isEmptyField = ref(false);
function onSubmit(isValid) {

    formFieldReq.first_name = (quoteForm.first_name == null || quoteForm.first_name === '');
    formFieldReq.last_name = (quoteForm.last_name == null || quoteForm.last_name === '');
    formFieldReq.dob = (quoteForm.dob == null || quoteForm.dob === '');
    formFieldReq.nationality_id = quoteForm.nationality_id == null;
    formFieldReq.uae_license_held_for_id = quoteForm.uae_license_held_for_id == null;
    formFieldReq.car_make_id = quoteForm.car_make_id == null;
    formFieldReq.car_model_id = quoteForm.car_model_id == null;
    formFieldReq.cylinder = (quoteForm.cylinder == null || quoteForm.cylinder === '');
    formFieldReq.year_of_manufacture = quoteForm.year_of_manufacture == null;
    formFieldReq.vehicle_type_id = quoteForm.vehicle_type_id == null;
    formFieldReq.seat_capacity = (quoteForm.seat_capacity == null || quoteForm.seat_capacity === '');
    formFieldReq.emirate_of_registration_id = quoteForm.emirate_of_registration_id == null;
    formFieldReq.car_type_insurance_id = quoteForm.car_type_insurance_id == null;
    formFieldReq.currently_insured_with = quoteForm.currently_insured_with == null;
    formFieldReq.claim_history_id = quoteForm.claim_history_id == null;

    if (isValid) {
        let method = 'post';
        let url = `/quotes/${props.dynamic_route}/`;
        let title = 'Quote saved successfully';
        let redirectUrl = `/quotes/${props.dynamic_route}`;
        if (page.props.quote) {
            method = 'put';
            url = url + page.props.quote.uuid;
            title = 'Quote updated successfully';
            redirectUrl = `/quotes/${props.dynamic_route}/${page.props.quote?.uuid}`;
        }

        quoteForm.submit(method, url, {
            onError: errors => {
                console.log(quoteForm.setError(errors));
            },
            onSuccess: () => {
                notification.success({
                    title: title,
                    position: 'top',
                });

                setTimeout(function () {
                    router.get(redirectUrl);
                }, 500);
            },
        });
    }
}

onMounted(() => {
    fetch(`/car-model-by-id?id=${quoteForm.car_make_id}`).
    then(carModel => {
         fetchCarModel.value = carModel;
    });

    // console.log(this.fetchCarModel);
})
</script>

<template>
    <div>
        <Head :title="conditionallyTitle"/>
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">
                {{ conditionallyTitle }} Quote <span v-if="page.props.quote">{{ page.props.quote?.uuid }}</span>
            </h2>
            <div>
                <Link :href="`/quotes/${props.dynamic_route}`">
                    <x-button size="sm" color="#ff5e00"> {{ conditionallyTitle }} Quotes List </x-button>
                </Link>
            </div>
        </div>
        <x-divider class="my-4" />
        <x-form @submit="onSubmit" :auto-focus="false">
            <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error">
                {{ quoteForm?.errors?.error }}
            </x-alert>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-input
                    v-model="quoteForm.renewal_batch"
                    type="text"
                    label="RENEWAL BATCH #"
                    :disabled="true"
                    class="w-full"
                    :error="quoteForm.errors.renewal_batch"
                />
                <x-input
                    v-model="quoteForm.first_name"
                    type="text"
                    label="First Name*"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.first_name"
                />

                <x-input
                    v-model="quoteForm.last_name"
                    type="text"
                    label="Last Name*"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.last_name"
                />

                <DatePicker
                    v-model="quoteForm.dob"
                    name="created_at_start"
                    label="Date of Birth*"
                    :rules="[isRequired]"
                    :hasError="quoteForm.errors.dob || formFieldReq.dob"
                />

                <x-input
                    v-model="quoteForm.email"
                    type="email"
                    label="Email*"
                    :disabled="true"
                    :rules="[isRequired, isEmail]"
                    class="w-full"
                    :error="quoteForm.errors.email"
                />

                <x-input
                    v-model="quoteForm.mobile_no"
                    type="tel"
                    label="Phone Number*"
                    :disabled="true"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.mobile_no"
                />

                <ComboBox
                    v-model="quoteForm.nationality_id"
                    label="Nationality*"
                    :single="true"
                    :rules="[isRequired]"
                    :options="nationalities"
                    :hasError="isEmptyField || formFieldReq.nationality_id"
                    :error="quoteForm.errors.nationality_id"
                />

                <x-select
                    v-model="quoteForm.uae_license_held_for_id"
                    label="UAE licence held for*"
                    :rules="[isRequired]"
                    :options="uaeLicenseHeldFor"
                    class="w-full"
                    :error="quoteForm.errors.uae_license_held_for_id"
                />

                <x-select
                    v-model="quoteForm.back_home_license_held_for_id"
                    label="HOME COUNTRY DRIVING LICENSE HELD FOR"
                    :options="uaeLicenseHeldFor"
                    class="w-full"
                    :error="quoteForm.errors.back_home_license_held_for_id"
                />

                <x-select
                    v-model="quoteForm.car_make_id"
                    label="CAR MAKE*"
                    :rules="[isRequired]"
                    :options="carMakes"
                    class="w-full"
                    :error="quoteForm.errors.car_make_id"
                />

                <x-select
                    v-model="quoteForm.car_model_id"
                    label="CAR MODEL*"
                    :rules="[isRequired]"
                    :options="carModel"
                    class="w-full"
                    :error="quoteForm.errors.car_model_id"
                />

                <x-input
                    v-model="quoteForm.cylinder"
                    label="CYLINDER*"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.cylinder"
                />

                <x-select
                    v-model="quoteForm.year_of_manufacture"
                    label="TRIM"
                    :options="[]"
                    class="w-full"
                    :error="quoteForm.errors.cylinder"
                />

                <x-select
                    v-model="quoteForm.year_of_manufacture"
                    label="CAR MODEL YEAR*"
                    :rules="[isRequired]"
                    :options="yearOfManufacture"
                    class="w-full"
                    :error="quoteForm.errors.year_of_manufacture"
                />

                <x-input
                    v-model="quoteForm.car_value"
                    label="CAR VALUE"
                    class="w-full"
                    :error="quoteForm.errors.car_value"
                />

                <x-input
                    v-model="quoteForm.car_value"
                    label="CAR VALUE (AT ENQUIRY)*"
                    :disabled="true"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.car_value"
                />

                <x-select
                    v-model="quoteForm.vehicle_type_id"
                    label="VEHICLE TYPE*"
                    :rules="[isRequired]"
                    :options="vehicleTypeOptions"
                    class="w-full"
                    :error="quoteForm.errors.vehicle_type_id"
                />

                <x-input
                    v-model="quoteForm.seat_capacity"
                    label="SEAT CAPACITY*"
                    :rules="[isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.seat_capacity"
                />

                <x-select
                    v-model="quoteForm.emirate_of_registration_id"
                    label="EMIRATE OF REGISTRATION*"
                    :rules="[isRequired]"
                    :options="emiratesOfRegistration"
                    class="w-full"
                    :error="quoteForm.errors.emirate_of_registration_id"
                />

                <x-select
                    v-model="quoteForm.car_type_insurance_id"
                    label="TYPE OF CAR INSURANCE*"
                    :rules="[isRequired]"
                    :options="typeOfInsuranceOptions"
                    class="w-full"
                    :error="quoteForm.errors.car_type_insurance_id"
                />

                <x-select
                    v-model="quoteForm.currently_insured_with"
                    label="CURRENTLY INSURED WITH*"
                    :rules="[isRequired]"
                    :options="currentlyInsuredWith"
                    class="w-full"
                    :error="quoteForm.errors.currently_insured_with"
                />

                <x-select
                    v-model="quoteForm.claim_history_id"
                    label="CLAIM HISTORY*"
                    :rules="[isRequired]"
                    :options="claimHistory"
                    class="w-full"
                    :error="quoteForm.errors.claim_history_id"
                />

                <x-select
                    v-model="quoteForm.uae_license_held_for_id"
                    label="CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?"
                    :options="[
                        { value: 1, label: 'Yes' },
                        { value: 0, label: 'No' },
                    ]"
                    class="w-full"
                    :error="quoteForm.errors.uae_license_held_for_id"
                />

                <x-input
                    v-model="quoteForm.previous_quote_policy_number"
                    label="PREVIOUS POLICY NUMBER"
                    :disabled="true"
                    class="w-full"
                    :error="quoteForm.errors.previous_quote_policy_number"
                />

                <x-input
                    v-model="quoteForm.previous_policy_expiry_date"
                    label="PREVIOUS POLICY EXPIRY DATE"
                    :disabled="true"
                    class="w-full"
                    :error="quoteForm.errors.previous_policy_expiry_date"
                />

                <x-textarea
                    v-model="quoteForm.additional_notes"
                    type="textarea"
                    label="ADDITIONAL NOTES"
                    class="w-full"
                    :error="quoteForm.errors.additional_notes"
                />

                <x-divider class="my-4" />
                <div class="flex justify-end gap-3 mb-4">
                    <x-button
                        size="md"
                        color="emerald"
                        type="submit"
                        :loading="quoteForm.processing"
                    >
                        Save
                    </x-button>
                </div>
            </div>
        </x-form>
    </div>
</template>
