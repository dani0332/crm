<script setup>
import {XDivider, XModal} from "@indielayer/ui";
import MemberDetails from "../../Components/MemberDetails.vue";

const props = defineProps({
    quoteType: Object,
    quoteRequest: Object,
    membersDetails: Object,
    memberRelations: Object,
    nationalities: Object,


    businessTypeCode: Object,
    businessCoverTypeText: Array,
    businessCommuModeText: Array,
    kycLogs: Array,
    quoteStatusCode: {type: [Object, String]},
    isCurrentUserFromCompliance: {type: [Array, Number]},
    isCurrentUserFromPaAml: {type: [Array, Number]},
    firstAmlLogResults: {type: [Array, Number]},
    latestAmlLogResults: {type: [Array, Number]},
    getAMLNumRows: {type: [Array, Number]},
    nationalityList: Array,
    yearsList: Array,
    isCompanySearchEnabled: {type: [Array, String]},
});

const notification = useNotifications('toast');
const page = usePage();
// const { isRequired } = useRules();
const loader = reactive({
    table: false,
});

const modals = reactive({
    insuranceForm: false,
    insuredDetailConfirm: false
});

const tableHeader = [
    {text: 'AML Id', value: 'id'},
    {text: 'Input', value: 'input'},
    {text: 'Search Type', value: 'search_type'},
    {text: 'Screenshot', value: 'screenshot'},
    {text: 'Match Found', value: 'results_found'},
    {text: 'Results Found', value: 'results_found'},
    {text: 'Created At', value: 'created_at'},
    {text: 'Updated At', value: 'updated_at'},
];

const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const quoteBusinessTypeCode = page.props.quoteBusinessTypeCode;

// const yob = computed(() => {
//     return page.props.yearsList.map(year => ({
//         value: year,
//         label: year,
//     }));
// });

const nationalityOptions = computed(() => {
    return page.props.nationalities.map(nat => ({
        value: nat.id,
        label: nat.text,
    }));
});

const dateFormat = date => {
    return date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';
};

const dateToYear = date => {
    if (date) {
        const d = new Date(date);
        const year = d.getFullYear();
        return `${year}`;
    }
    return '';
}

const insuredFormDetails = useForm({
    customer_id: props.quoteRequest.customer_id,
    insured_first_name: props.quoteRequest?.customer?.insured_first_name ?? null,
    insured_last_name: props.quoteRequest?.customer?.insured_last_name ?? null,
    nationality: props.quoteRequest?.customer.nationality_id ?? null,
    date_of_birth: props.quoteRequest?.customer.dob ?? null
});

const insuredDetailsSubmit = isValid => {
    if (!isValid) return;

    insuredFormDetails.get(`${props.quoteRequest.id}/quoteUpdate`, {
            preserveScroll: true,
            onError: errors => {
                notification.error({
                    title: errors.error || 'Data not saved',
                    position: 'top',
                });
            },
            onSuccess: () => {
                additionalContact.reset();
                notification.success({
                    title: 'Additional Contact Added',
                    position: 'top',
                });
            },
            onFinish: () => {
                modals.addContact = false;
            },
        });
};

// const updateCustomer = isValid => {
//     if (!isValid) {
//         return;
//     }
//     customer
//         .transform(data => {
//             return {
//                 ...data,
//             };
//         })
//         .get(`${page.props.quoteRequest.id}/quoteUpdate`, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 const session = usePage().props.flash;
//                 notification.success(session.success);
//             },
//         });
// };

</script>

<template>
    <div>
        <Head title="AML"/>

        <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
            <h2 class="text-xl font-semibold">{{ quoteType.text }} Quote</h2>
            <div class="flex gap-2">
                <Link href="/kyc/aml" preserve-scroll>
                    <x-button size="sm" color="primary" tag="div"> AML List</x-button>
                </Link>
            </div>
        </div>

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">{{ quoteType.code }} QUOTE ID</dt>
                        <dd>{{ quoteRequest.id }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <div>
                            <x-tooltip position="bottom">
                                <label
                                    class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
                                >
                                    Ref-ID
                                </label>
                                <template #tooltip> Reference ID</template>
                            </x-tooltip>
                        </div>
                        <div>{{ quoteRequest.code }}</div>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">QUOTE STATUS</dt>
                        <dd>{{ quoteRequest?.quote_status?.text ?? '' }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PHONE NUMBER</dt>
                        <dd>{{ quoteRequest.mobile_no }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">FIRST NAME</dt>
                        <dd>{{ quoteRequest.first_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST NAME</dt>
                        <dd>{{ quoteRequest.last_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">YEAR OF BIRTH</dt>
                        <dd>{{ dateToYear(quoteRequest.dob) }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMAIL ADDRESS</dt>
                        <dd>{{ quoteRequest.email }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LANG</dt>
                        <dd>{{ quoteRequest.lang }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SOURCE</dt>
                        <dd>{{ quoteRequest.source }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">REVIVER NAME</dt>
                        <dd>{{ quoteRequest.reviver_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PROMO CODE</dt>
                        <dd>{{ quoteRequest.promo_code }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DEVICE</dt>
                        <dd>{{ quoteRequest.device }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PAYMENT STATUS</dt>
                        <dd>{{ quoteRequest?.payment_status?.text ?? '' }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">REFERENCE URL</dt>
                        <dd>{{ quoteRequest.reference_url }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADDITIONAL NOTES</dt>
                        <dd>{{ quoteRequest.additional_notes }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">IS SYNCED</dt>
                        <dd>{{ quoteRequest.is_synced ? 'No' : 'Yes' }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CUSTOMER NAME</dt>
                        <dd>{{ quoteRequest?.customer.first_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED AT</dt>
                        <dd>{{ dateFormat(quoteRequest.created_at) }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">UPDATED AT</dt>
                        <dd>{{ dateFormat(quoteRequest.updated_at) }}</dd>
                    </div>

                    <template v-if="quoteType.code == quoteTypeCodeEnum.Car">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NATIONALITY</dt>
                            <dd>{{ quoteRequest.nationality_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">UAE LICENCE HELD FOR</dt>
                            <dd>{{ quoteRequest.uae_license_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CAR MAKE</dt>
                            <dd>{{ quoteRequest.car_make_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CAR MODEL</dt>
                            <dd>{{ quoteRequest.car_model_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">YEAR OF MANUFACTURE</dt>
                            <dd>{{ quoteRequest.year_of_manufacture }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">EMIRATES OF REGISTRATION</dt>
                            <dd>{{ quoteRequest.emirates_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                            <dd>{{ quoteRequest.currently_insured_with }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CAR VALUE(AED)</dt>
                            <dd>{{ quoteRequest.car_value }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">TYPE OF CAR INSURANCE</dt>
                            <dd>{{ quoteRequest.car_type_ins_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CLAIM HISTORY</dt>
                            <dd>{{ quoteRequest.claim_history_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DATE OF BIRTH</dt>
                            <dd>{{ quoteRequest.dob }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Health">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">COVER FOR</dt>
                            <dd>{{ quoteRequest.health_cover_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">MARITAL STATUS</dt>
                            <dd>{{ quoteRequest.marital_status_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">EMIRATE OF VISA</dt>
                            <dd>{{ quoteRequest.emirates_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DATE OF BIRTH</dt>
                            <dd>{{ quoteRequest.dob }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">GENDER</dt>
                            <dd>{{ quoteRequest.gender }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">PREFERRED HOSPITALS/CLINICS</dt>
                            <dd>{{ quoteRequest.preference }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DETAILS</dt>
                            <dd>{{ quoteRequest.details }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">OPTIONAL COVERS REQUIRED</dt>
                            <dd>{{ quoteRequest.mobile_no }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NATIONALITY</dt>
                            <dd>{{ quoteRequest.nationality_text }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Home">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">I AM</dt>
                            <dd>{{ quoteRequest.home_possession_type_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">I LIVE IN</dt>
                            <dd>{{ quoteRequest.home_accommodation_type_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">COVER FOR</dt>
                            <dd>{{ quoteRequest.mobile_no }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">ADDRESS</dt>
                            <dd>{{ quoteRequest.address }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Travel">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DAYS COVER FOR</dt>
                            <dd>{{ quoteRequest.days_cover_for }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">REGIONS COVER</dt>
                            <dd>{{ quoteRequest.region_cover_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">COVER FOR</dt>
                            <dd>{{ quoteRequest.cover_for_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DETAILS</dt>
                            <dd>{{ quoteRequest.details }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NATIONALITY</dt>
                            <dd>{{ quoteRequest.nationality_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DESTINATION</dt>
                            <dd>{{ quoteRequest.destination }}</dd>
                        </div>
                    </template
                    >
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Life">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">PURPOSE OF INSURANCE</dt>
                            <dd>{{ quoteRequest.purpose_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CHILDREN</dt>
                            <dd>{{ quoteRequest.children_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">MARITAL STATUS</dt>
                            <dd>{{ quoteRequest.marital_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">TENURE OF INSURANCE</dt>
                            <dd>{{ quoteRequest.tenure_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">SMOKER</dt>
                            <dd>{{ quoteRequest.is_smoker }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NO. OF YEARS</dt>
                            <dd>{{ quoteRequest.number_of_year_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">SUM INSURED</dt>
                            <!-- quoteRequest.currency_text -->
                            <dd>
                                {{ quoteRequest.currency_text }}
                            </dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">OTHER INFO</dt>
                            <dd>{{ quoteRequest.others_info }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DATE OF BIRTH</dt>
                            <dd>{{ quoteRequest.dob }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">GENDER</dt>
                            <dd>{{ quoteRequest.gender }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NATIONALITY</dt>
                            <dd>{{ quoteRequest.nationality_text }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Bike">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">NATIONALITY</dt>
                            <dd>{{ quoteRequest.nationality_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">DATE OF BIRTH</dt>
                            <dd>{{ quoteRequest.dob }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">UAE LICENSE HELD FOR</dt>
                            <dd>{{ quoteRequest.uae_license_text }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">BIKE(S) TO INSURE</dt>
                            <dd>{{ quoteRequest.bike_company_to_insure }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">BIKE VALUE(AED)</dt>
                            <dd>{{ quoteRequest.bike_value }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">YEAR OF MANUFACTURE</dt>
                            <dd>{{ quoteRequest.year_of_manufacture }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                            <dd>{{ quoteRequest.currently_insured_with }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Yacht">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">BOAT DETAILS</dt>
                            <dd>{{ quoteRequest.boat_details }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">ENGINE DETAILS</dt>
                            <dd>{{ quoteRequest.engine_details }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">CLAIMS EXPERIENCE</dt>
                            <dd>{{ quoteRequest.claim_experience }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">SUM INSURED</dt>
                            <dd>{{ quoteRequest.sum_insured_value }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">USER</dt>
                            <dd>{{ quoteRequest.use }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">OPERATOR'S EXPERIENCE</dt>
                            <dd>{{ quoteRequest.operator_experience }}</dd>
                        </div>
                    </template>
                    <template v-if="quoteType.code == quoteTypeCodeEnum.Business">
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">COMPANY NAME</dt>
                            <dd>{{ quoteRequest.company_name }}</dd>
                        </div>
                        <div class="grid sm:grid-cols-2">
                            <dt class="font-medium">TYPE OF BUSINESS INSURANCE</dt>
                            <dd>{{ quoteRequest.business_type_text }}</dd>
                        </div>
                        <template
                            v-if="businessTypeCode && businessTypeCode != quoteBusinessTypeCode.photographers">
                            <div class="grid sm:grid-cols-2">
                                <dt class="font-medium">BRIEF DETAILS</dt>
                                <dd>{{ quoteRequest.brief_details }}</dd>
                            </div>
                        </template>
                        <template
                            v-if="businessTypeCode && businessTypeCode != quoteBusinessTypeCode.groupMedical">
                            <div class="grid sm:grid-cols-2">
                                <dt class="font-medium">NUMBER OF MEMBERS</dt>
                                <dd>{{ quoteRequest.number_of_employees }}</dd>
                            </div>
                        </template>
                        <template
                            v-if="businessTypeCode && businessTypeCode != quoteBusinessTypeCode.photographers">
                            <div class="grid sm:grid-cols-2">
                                <dt class="font-medium">INTEREST</dt>
                                <dd>{{ quoteRequest.interest }}</dd>
                            </div>
                        </template>
                        <!-- Need to be fetch from env -->
                        <template v-if="quoteRequest.reference_url == 'crm.afia.ae'">
                            <template
                                v-if="businessTypeCode == quoteBusinessTypeCode.groupMedical">
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">CONTACT PERSON DESIGNATION</dt>
                                    <dd>{{ quoteRequest.contact_person_designation }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">RENEWAL DUE DATE</dt>
                                    <dd>{{ quoteRequest.renewal_due_date }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">COVER TYPE</dt>
                                    <dd>{{ businessCoverTypeText }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">TIME TO CONTACT</dt>
                                    <dd>{{ quoteRequest.time_to_contact }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">COMMUNICATION MODE PREFERENCE</dt>
                                    <dd>{{ businessCommuModeText }}</dd>
                                </div>
                            </template>
                            <template
                                v-if="businessTypeCode == quoteBusinessTypeCode.marineHull"
                            >
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">BOAT DETAILS</dt>
                                    <dd>{{ quoteRequest.boat_details }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">ENGINE DETAILS</dt>
                                    <dd>{{ quoteRequest.engine_details }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">CLAIM EXPERIENCE</dt>
                                    <dd>{{ quoteRequest.claims_experience }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">SUM INSURED</dt>
                                    <dd>{{ quoteRequest.sum_insured_value }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">USE</dt>
                                    <dd>{{ quoteRequest.use }}</dd>
                                </div>
                                <div class="grid sm:grid-cols-2">
                                    <dt class="font-medium">OPERATOR'S EXPERIENCE</dt>
                                    <dd>{{ quoteRequest.operators_experience }}</dd>
                                </div>
                            </template>
                        </template>
                    </template>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS QUOTE ID</dt>
                        <dd>{{ quoteRequest.previous_quote_id }}</dd>
                    </div>
                    <div class="flex justify-end">
                        <x-button
                            class="mt-4"
                            color="#ff5e00"
                            size="sm"
                            @click.prevent="modals.insuranceForm = true;"
                        >
                            Update & Verify
                        </x-button>
                    </div>
                </dl>
            </div>
        </div>

        <x-modal v-model="modals.insuranceForm" size="xl" show-close backdrop>
            <template #header>Update and Verify</template>
            <p class="text-center mb-10">Please confirm the Name, Nationality, and Date of Birth of the insured person(s) as per the Emirates ID</p>
            <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Insured First Name</dt>
                        <dd>
                            <x-input
                                v-model="insuredFormDetails.insured_first_name"
                                placeholder="Insured First Name"
                                class="w-full"
                            />
                        </dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Insured Last Name</dt>
                        <dd>
                            <x-input
                                v-model="insuredFormDetails.insured_last_name"
                                placeholder="Insured Last Name"
                                class="w-full"
                            />
                        </dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Nationality</dt>
                        <dd>
                            <ComboBox
                                :single="true"
                                v-model="insuredFormDetails.nationality"
                                placeholder="Select Nationality"
                                :options="nationalityOptions"
                                class="w-full"
                            />
                        </dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Date of Birth</dt>
                        <dd>
                            <DatePicker
                                v-model="insuredFormDetails.date_of_birth"
                                placeholder="Date of Birth"
                                class="w-full"
                            />
                        </dd>
                    </div>
                </dl>
                <x-divider class="mb-4 mt-1" />
                <MemberDetails
                    :quote="quoteRequest"
                    :membersDetails="membersDetails"
                    :nationalities="nationalities"
                    :memberRelations="memberRelations"
                    :quote_type=quoteType.code
                    :forAml="true"
                />
            <x-divider class="mb-4 mt-1" />
            <p class="text-center mb-5">Payer Details</p>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Payment Method</dt>
                    <dd>
                        <x-input
                            placeholder="Payment Method"
                            class="w-full"
                            disabled
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Payer Name</dt>
                    <dd>
                        <x-input
                            placeholder="Payer Name"
                            class="w-full"
                            disabled
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Total Amount</dt>
                    <dd>
                        <x-input
                            placeholder="Total Amount"
                            class="w-full"
                            disabled
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Paid By</dt>
                    <dd>
                        <x-input
                            placeholder="Paid By"
                            class="w-full"
                            disabled
                        />
                    </dd>
                </div>
            </dl>
            <div class="text-right space-x-4 mt-8"  >
                <x-button
                    size="sm"
                    color="success"
                    :loading="insuredFormDetails.processing"
                    @click.prevent="modals.insuredDetailConfirm = true"
                >
                    Confirm
                </x-button>
            </div>
            </x-form>
        </x-modal>
        <x-modal v-model="modals.insuredDetailConfirm" show-close backdrop>
            <p>Are you sure you want to run AML screen for this lead as Individual Customer?</p>
            <template #actions>
                <div class="text-center space-x-4">
                    <x-button
                        size="sm"
                        color="#ff5e00"
                        @click.prevent="modals.insuredDetailConfirm = false"
                    >
                        No
                    </x-button>
                    <x-button
                        size="sm"
                        color="success"
                        @click.prevent="insuredDetailsSubmit"
                        :loading="insuredFormDetails.processing"
                    >
                        Yes
                    </x-button>
                </div>
            </template>
        </x-modal>
        <x-modal>

        </x-modal>

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
                <h3 class="font-semibold text-primary-800 text-lg">KYC-AML Logs</h3>
            </div>
            <DataTable
                table-class-name="tablefixed"
                :headers="tableHeader"
                :loading="loader.table"
                :items="kycLogs || []"
                border-cell
                hide-rows-per-page
                hide-footer
                fixed-checkbox
            >
                <template #item-id="{ id }">
                    <Link
                        :href="`/kyc/aml/${id}`"
                        class="text-primary-500 hover:underline"
                    >
                        {{ id }}
                    </Link>
                </template>

                <template #item-screenshot="{ screenshot }">
                    <img :src="screenshot" alt="IMCRM" class="w-6"/>
                </template>
            </DataTable>
        </div>
        <AuditLogs :type="`App\\Models\\${quoteType.code}Quote`" :id="quoteRequest.id"/>
    </div>
</template>
