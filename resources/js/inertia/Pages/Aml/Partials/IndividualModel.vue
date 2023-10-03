<script setup>
    import MemberDetailsModel from "./MemberDetailsModel.vue";
    import UBODetailsModels from "./UBODetailsModels.vue";
    import {XButton, XInput} from "@indielayer/ui";

    const props = defineProps({
        modelValue: { type: Boolean, default: false},
        quoteDetails: Object,
        quoteType: Object,
        nationalities: Object,
        emirates: Object,
        industryType: Object,
        membersDetails: Object,
        memberRelations: Object,
        uboRelations: Object
    });

    const emit = defineEmits(['update:modelValue', 'loaded']);
    const notification = useToast();
    const showModal = computed({
        get: () => props.modelValue,
        set: val => emit('update:modelValue', val),
    });

    const modals = reactive({
        insuredDetailConfirmation: false,
        entityView: false
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
        quote_type: props.quoteType.code,
        insured_first_name: props.quoteDetails?.customer?.insured_first_name ?? null,
        insured_last_name: props.quoteDetails?.customer?.insured_last_name ?? null,
        nationality: props.quoteDetails?.customer.nationality_id ?? null,
        date_of_birth: props.quoteDetails?.customer.dob ?? null,
        trade_licenese: props.quoteDetails?.customer?.insured_first_name ?? null,
        company_name: props.quoteDetails?.customer?.insured_last_name ?? null,
        company_address: props.quoteDetails?.customer.nationality_id ?? null,
        entity_type: props.quoteDetails?.customer.dob ?? null,
        industry_type: props.quoteDetails?.customer.dob ?? null,
        emirate_of_registration: props.quoteDetails?.customer.dob ?? null,
    });

    const insuredDetailsSubmit = isValid => {
        if (!isValid) return;

        insuredFormDetails.get(`${props.quoteDetails.id}/quoteUpdate`, {
            preserveScroll: true,
            onError: errors => {
                notification.error({
                    title: errors.error || 'Data not saved',
                    position: 'top',
                });
            },
            onSuccess: () => {
                // const session = usePage().props.flash;
                // notification.success(session.success);
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

    const switchToEntityView = () => {
        modals.insuredDetailConfirmation = false;
        showModal.value = false;
        modals.entityView = true;
    };

    const searchByTradeLicense = () => {
        let url = `/kyc/fetch-entity?customer_id=${props.quoteDetails.customer_id}&trade_license=${props.quoteDetails.customer_id}`;
        axios.get(url)
            .then(res => {
                notification.success({
                    title: 'Lead Status Updated',
                    position: 'top',
                });
            })
            .catch(err => {
                console.log(err);
            });
    };

</script>

<template>
    <!-- Individual Type Insured Form -->
    <x-modal v-model="showModal" size="xl" show-close backdrop>
        <template #header>Update and Verify</template>
        <p class="text-center mb-10">Please confirm the Name, Nationality, and Date of Birth of the insured person(s) as per the Emirates ID</p>

        <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div  class="grid sm:grid-cols-2">
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
                            :options="nationalitiesOptions"
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

            <MemberDetailsModel
                :quoteDetails="quoteDetails"
                :quoteType="quoteType"
                :nationalities="nationalities"
                :membersDetails="membersDetails"
                :memberRelations="memberRelations"
            />

            <x-divider class="mb-4 mt-4" />

            <div class="text-right space-x-4 mt-8"  >
                <x-button
                    size="sm"
                    color="success"
                    @click.prevent="modals.insuredDetailConfirmation = true"
                >
                    Confirm
                </x-button>
            </div>
        </x-form>
    </x-modal>

    <x-modal v-model="modals.insuredDetailConfirmation" show-close backdrop>
        <p>Are you sure you want to run AML screen for this lead as Individual Customer?</p>
        <template #actions>
            <div class="text-center space-x-4">
                <x-button
                    size="sm"
                    color="#ff5e00"
                    @click.prevent="switchToEntityView"
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

    <!-- Entity Type Insured Form -->
    <x-modal v-model="modals.entityView" size="xl" show-close backdrop>
        <template #header>Update and Verify</template>
        <p class="text-center mb-10">Please Enter Entity details to change the Customer Type to 'Entity'</p>

        <transition v-model="modals.entityView" name="fade">
            <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4 mb-5">
                <x-field label="Trade License No">
                    <x-input
                        type="text"
                        class="w-full"
                        disabled
                    />
                </x-field>

                <x-field label="Company Name">
                    <x-input
                        type="text"
                        class="w-full"
                        disabled
                    />
                </x-field>
                <x-field label="Comapny Address">
                    <x-input
                        type="text"
                        class="w-full"
                        disabled
                    />
                </x-field>

                <div class="text-left space-x-4"  >
                    <x-button
                        size="sm"
                        color="info"
                    >
                        Hide
                    </x-button>
                    <x-button
                        size="sm"
                        color="orange"
                    >
                        Link
                    </x-button>
                </div>
            </dl>
        </transition>
        <x-form @submit="insuredDetailsSubmit" :auto-focus="false">
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div  class="grid sm:grid-cols-2">
                    <dt class="font-medium">Trade License No</dt>
                    <dd>
                        <x-input
                            v-model="insuredFormDetails.trade_licenese"
                            placeholder="Trade License No"
                            class="w-full"
                        />
                        <x-button
                            @click.prevent="searchByTradeLicense"
                            size="xs"
                            color="primary"
                        >
                            Search
                        </x-button>
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Company Name</dt>
                    <dd>
                        <x-input
                            v-model="insuredFormDetails.company_name"
                            placeholder="Company Name"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Company Address</dt>
                    <dd>
                        <x-input
                            v-model="insuredFormDetails.company_address"
                            placeholder="Company Address"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Entity Type</dt>
                    <dd>
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.entity_type"
                            placeholder="Select Entity Type"
                            :options="[
                                {label: 'Parent', value: 'parent'},
                                {label: 'Sub Entity', value: 'sub_entity'}
                            ]"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Industry Type</dt>
                    <dd>
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.industry_type"
                            placeholder="Select Industry Type"
                            :options="industryTypeOptions"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Emirate of Registration</dt>
                    <dd>
                        <ComboBox
                            :single="true"
                            v-model="insuredFormDetails.emirate_of_registration"
                            placeholder="Select Emirate of Registration"
                            :options="emirateRegistrationOptions"
                            class="w-full"
                        />
                    </dd>
                </div>
            </dl>
            <x-divider class="mb-4 mt-1" />

            <UBODetailsModels
                :quoteDetails="quoteDetails"
                :quoteType="quoteType"
                :nationalities="nationalities"
                :membersDetails="membersDetails"
                :uboRelations="uboRelations"
            />
            <x-divider class="mb-4 mt-4" />
            <div class="text-right space-x-4 mt-8"  >
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
                    @click.prevent="insuredDetailsSubmit"
                    :loading="insuredFormDetails.processing"
                >
                    Submit
                </x-button>
            </div>
        </x-form>
    </x-modal>

</template>
