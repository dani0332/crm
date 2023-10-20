<script setup>
import MemberDetailsModel from './MemberDetailsModel.vue';
import UBODetailsModels from './UBODetailsModels.vue';
import {XButton, XInput} from '@indielayer/ui';

const props = defineProps({
    modelValue: {type: Boolean, default: false},
    quoteType: Object,
    quoteDetails: Object,
    entityDetails: Object,
    nationalities: Object,
    emirates: Object,
    industryType: Object,
    membersDetails: Object,
    uboDetails: Object,
    memberRelations: Object,
    uboRelations: Object,
    customerTypeEnum: Object,
});

const loader = ref({
    search: false,
});

const emit = defineEmits(['update:modelValue', 'loaded']);
const notification = useToast();
const {isRequired} = useRules();
const showModal = computed({
    get: () => props.modelValue,
    set: val => emit('update:modelValue', val),
});

const modals = reactive({
    insuredDetailConfirmation: false,
    entityView: false,
});
const nationalitiesOptions = computed(() => {
    return props.nationalities.map(nat => ({
        value: nat.id,
        label: nat.text,
    }));
});
const emirateRegistrationOptions = computed(() => {
    return props.emirates.map(emirate => ({
        value: emirate.id,
        label: emirate.text,
    }));
});
const industryTypeOptions = computed(() => {
    return props.industryType.map(indType => ({
        value: indType.code,
        label: indType.text,
    }));
});

const insuredFormDetails = useForm({
    customer_id: props.quoteDetails.customer_id,
    customer_type: props.customerTypeEnum.Individual,
    quote_type: props.quoteType.code,

    insured_first_name: props.quoteDetails?.customer?.insured_first_name ?? null,
    insured_last_name: props.quoteDetails?.customer?.insured_last_name ?? null,
    nationality_id: props.quoteDetails?.customer.nationality_id ?? null,
    dob: props.quoteDetails?.customer.dob ?? null,

    entity_id: props.entityDetails?.entity?.id,
    trade_license_no: props.entityDetails?.entity?.trade_license_no,
    company_name: props.entityDetails?.entity?.company_name,
    company_address: props.entityDetails?.entity?.company_address,
    entity_type_code: props.entityDetails?.entity?.entity_type_code ?? 'Parent',
    industry_type_code: props.entityDetails?.entity?.industry_type_code,
    emirate_of_registration_id:
    props.entityDetails?.entity?.emirate_of_registration_id,
});

const submitQuoteUpdateForm = () => {
    insuredFormDetails.get(`${props.quoteDetails.id}/quoteUpdate`, {
        preserveScroll: true,
        onError: errors => {
            notification.error({
                title: errors.error || 'Quote not updated',
                position: 'top',
            });
        },
        onSuccess: () => {
            notification.success({
                title: 'Quote is updated',
                position: 'top',
            });
        },
        onFinish: () => {
        },
    });
}

const insuredDetailsSubmit = isValid => {
    if (!isValid) return;

    if(insuredFormDetails.customer_type == props.customerTypeEnum.Individual) {
        modals.insuredDetailConfirmation = true;
    }

    if(insuredFormDetails.customer_type == props.customerTypeEnum.Entity) {
        submitQuoteUpdateForm();
    }

};

const entityDetailsFound = ref(false);
const linkLoader = ref(false);
const switchToEntityView = () => {
    insuredFormDetails.customer_type = props.customerTypeEnum.Entity;
    modals.insuredDetailConfirmation = false;
    showModal.value = false;
    modals.entityView = true;
};

const tradeLicenseEntity = reactive({
    entity_id: null,
    trade_license: null,
    company_name: null,
    company_address: null,
});

const searchByTradeLicense = () => {
    loader.value.search = true;
    let url = `/kyc/aml-fetch-entity?trade_license=${insuredFormDetails.trade_license_no}`;
    axios
        .get(url)
        .then(res => {
            if (res.data.status) {
                let response = res.data.response;
                entityDetailsFound.value = true;
                tradeLicenseEntity.entity_id = response.id;
                tradeLicenseEntity.trade_license = response.trade_license_no;
                tradeLicenseEntity.company_name = response.company_name;
                tradeLicenseEntity.company_address = response.company_address;

                notification.success({
                    title: res.data.message,
                    position: 'top',
                });
            } else {
                notification.error({
                    title: res.data.message,
                    position: 'top',
                });
            }
        })
        .catch(err => {
            console.log(err);
        })
        .finally(() => (loader.value.search = false));
};

const linkEntity = () => {
    linkLoader.value = true;
    let entityDetails = {
        quote_type_id: props.quoteType.id,
        quote_request_id: props.quoteDetails.id,
        entity_id: tradeLicenseEntity.entity_id,
    };
    axios
        .post(route('link-entity-details'), entityDetails)
        .then(res => {
            if (res.data.status) {
                let response = res.data.response;

                insuredFormDetails.trade_license_no = response.trade_license_no;
                insuredFormDetails.company_name = response.company_name;
                insuredFormDetails.company_address = response.company_address;
                insuredFormDetails.entity_type_code = response?.quote_request_entity_mapping[0]?.entity_type_code ?? '';
                insuredFormDetails.industry_type_code = response.industry_type_code;
                insuredFormDetails.emirate_of_registration_id = response.emirate_of_registration_id;
                notification.success({
                    title: res.data.message,
                    position: 'top',
                });
            }
            linkLoader.value = false;
        })
        .catch(err => {
            console.log(err);
        })
        .finally(() => (entityDetailsFound.value = false));
};
</script>

<template>
    <!-- Individual Type Insured Form -->
    <x-modal v-model="showModal" size="xl" show-close backdrop>
        <template #header>Update and Verify</template>
        <p class="text-center mb-10">
            Please confirm the Name, Nationality, and Date of Birth of the insured
            person(s) as per the Emirates ID
        </p>

        <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
            <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
                <x-field label="Insured First Name">
                    <x-input
                        v-model="insuredFormDetails.insured_first_name"
                        :rules="[isRequired]"
                        placeholder="Insured First Name"
                        type="text"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Insured Last Name">
                    <x-input
                        v-model="insuredFormDetails.insured_last_name"
                        :rules="[isRequired]"
                        placeholder="Insured Last Name"
                        type="text"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Nationality">
                    <ComboBox
                        :single="true"
                        v-model="insuredFormDetails.nationality_id"
                        :rules="[isRequired]"
                        placeholder="Select Nationality"
                        :options="nationalitiesOptions"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Date of Birth">
                    <DatePicker
                        v-model="insuredFormDetails.dob"
                        :rules="[isRequired]"
                        placeholder="Date of Birth"
                        class="w-full"
                    />
                </x-field>
            </dl>

            <x-divider class="mb-4 mt-1"/>

            <MemberDetailsModel
                :quoteType="quoteType"
                :quoteDetails="quoteDetails"
                :nationalities="nationalities"
                :membersDetails="membersDetails"
                :memberRelations="memberRelations"
                :customerType="props.customerTypeEnum.Individual"
            />

            <x-divider class="mb-4 mt-4"/>

            <div class="text-right space-x-4 mt-8">
                <x-button
                    size="sm"
                    color="success"
                    type="submit"
                >
                    Confirm
                </x-button>
            </div>
        </x-form>
    </x-modal>

    <!-- Confirmation Model -->
    <x-modal
        v-model="modals.insuredDetailConfirmation"
        show-close
        :backdrop="true"
    >
        <p>
            Are you sure you want to run AML screen for this lead as Individual
            Customer?
        </p>
        <template #actions>
            <div class="text-center space-x-4">
                <x-button size="sm" color="#ff5e00" @click.prevent="switchToEntityView">
                    No
                </x-button>
                <x-button
                    size="sm"
                    color="success"
                    @click.prevent="submitQuoteUpdateForm"
                    :loading="insuredFormDetails.processing"
                >
                    Yes
                </x-button>
            </div>
        </template>
    </x-modal>

    <!-- Entity Type Insured Form -->
    <x-modal v-model="modals.entityView" size="xl" show-close backdrop>
        <template #header>Update and Verify</template>
        <p class="text-center mb-10">
            Please Enter Entity details to change the Customer Type to 'Entity'
        </p>

        <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
            <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
                <x-field label="Trade License No">
                    <x-input
                        v-model="insuredFormDetails.trade_license_no"
                        :rules="[isRequired]"
                        placeholder="Trade License No"
                        type="text"
                        class="w-full"
                    />
                    <x-button
                        @click.prevent="searchByTradeLicense"
                        size="xs"
                        color="primary"
                        :loading="loader.search"
                    >
                        Search
                    </x-button>
                </x-field>
                <x-field label="Company Name">
                    <x-input
                        v-model="insuredFormDetails.company_name"
                        :rules="[isRequired]"
                        placeholder="Company Name"
                        type="text"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Company Address">
                    <x-input
                        v-model="insuredFormDetails.company_address"
                        :rules="[isRequired]"
                        placeholder="Company Address"
                        type="text"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Entity Type">
                    <ComboBox
                        :single="true"
                        v-model="insuredFormDetails.entity_type_code"
                        :rules="[isRequired]"
                        placeholder="Select Entity Type"
                        :options="[
                          { label: 'Parent', value: 'Parent' },
                          { label: 'Sub Entity', value: 'SubEntity' },
                        ]"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Industry Type">
                    <ComboBox
                        :single="true"
                        v-model="insuredFormDetails.industry_type_code"
                        :rules="[isRequired]"
                        placeholder="Select Industry Type"
                        :options="industryTypeOptions"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Emirate of Registration">
                    <ComboBox
                        :single="true"
                        v-model="insuredFormDetails.emirate_of_registration_id"
                        :rules="[isRequired]"
                        placeholder="Select Emirate of Registration"
                        :options="emirateRegistrationOptions"
                        class="w-full"
                    />
                </x-field>
            </dl>
            <x-divider class="mb-4 mt-1"/>
            <template v-if="entityDetailsFound">
                <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 mb-5">
                    <x-field label="Trade License No">
                        <x-input
                            v-model="tradeLicenseEntity.trade_license"
                            type="text"
                            class="w-full"
                            disabled
                        />
                    </x-field>
                    <x-field label="Company Name">
                        <x-input
                            v-model="tradeLicenseEntity.company_name"
                            type="text"
                            class="w-full"
                            disabled
                        />
                    </x-field>
                    <x-field label="Company Address">
                        <x-input
                            v-model="tradeLicenseEntity.company_address"
                            type="text"
                            class="w-full"
                            disabled
                        />
                    </x-field>
                    <div class="text-left space-x-4">
                        <x-button
                            size="sm"
                            color="red"
                            @click.prevent="entityDetailsFound = false"
                        >
                            Hide
                        </x-button>
                        <x-button
                            size="sm"
                            color="orange"
                            @click.prevent="linkEntity"
                            :loading="linkLoader"
                        >
                            Link
                        </x-button>
                    </div>
                </dl>
            </template>
            <x-divider v-if="entityDetailsFound" class="mb-4 mt-1"/>

            <UBODetailsModels
                :quoteDetails="quoteDetails"
                :quoteType="quoteType"
                :nationalities="nationalities"
                :uboDetails="uboDetails"
                :uboRelations="uboRelations"
                :entity_id="insuredFormDetails.entity_id"
                :customerType="props.customerTypeEnum.Entity"
            />
            <x-divider class="mb-4 mt-4"/>
            <div class="text-right space-x-4 mt-8">
                <x-button
                    size="sm"
                    color="red"
                    @click.prevent="modals.entityView = false"
                >
                    Cancel
                </x-button>
                <x-button
                    size="sm"
                    color="orange"
                    type="submit"
                    :loading="insuredFormDetails.processing"
                >
                    Submit
                </x-button>
            </div>
        </x-form>
    </x-modal>
</template>
