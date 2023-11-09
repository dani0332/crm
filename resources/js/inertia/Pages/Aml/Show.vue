<script setup>

const props = defineProps({
    aml: Object,
    amlResults: Array,
    responseFrom: String,
    quoteStatusCode: Object,
    amlDecisionStatusCode: Object
});
const page = usePage();
const notification = useToast();
const rolesEnum = page.props.rolesEnum;
const hasRole = role => useHasRole(role);
const amlResults = ref(props.amlResults);
const loader = reactive({
    table: false,
});

const tableHeader = [
    {text: 'Result', value: 'result'},
    {text: 'Score', value: 'EntityScore'},
    {text: 'Name', value: 'full_name'},
    {text: 'Date of Birth', value: 'date_of_birth'},
    {text: 'Gender', value: 'gender'},
    {text: 'ID Number', value: 'customer_id'},
    {text: 'Address', value: 'address'},
    {text: 'Country', value: 'country'},
    {text: 'Customer Type', value: 'customer_type'},
    {text: 'Citizenship', value: 'citizenship'},
];
const decisionNotes = ref('');
const decisionNotesModal = ref(false);
const submitDecisionLoading = ref(false);
const decisionModalHeading = ref('');
const amlDecision = ref('');
const passingDecisions = [ props.amlDecisionStatusCode.FALSE_POSITIVE, props.amlDecisionStatusCode.TRUE_MATCH_ACCEPT_RISK];
const decisionTitles = {
    FalsePositive : 'False Positive',
    TrueMatchAcceptRisk: 'True Match - Accept Risk',
    TrueMatchRejectRisk: 'True Match - Reject Risk',
};

function submitDecision(decision) {
    submitDecisionLoading.value = true;
    let quoteStatusCode = passingDecisions.includes(decision)
        ? props.quoteStatusCode.AMLScreeningCleared
        : props.quoteStatusCode.AMLScreeningFailed;
    let url = `${props.aml.quote_type_id}/details/${props.aml.quote_request_id}/quoteStatusUpdate/${quoteStatusCode}?notes=${decisionNotes.value}&aml_id=${props.aml.id}&aml_decision=${decision}`;

    axios
        .get(url)
        .then((response) => {
            submitDecisionLoading.value = false;
            decisionNotesModal.value = false;
            if(response.status) {
                notification.success({
                    title: 'Quote Status Updated',
                    position: 'top',
                });
                window.location = `/kyc/aml/${props.aml.quote_type_id}/details/${props.aml.quote_request_id}`;
            } else {
                notification.error({
                    title: 'Quote Status not Updated',
                    position: 'top',
                });
            }
        })
        .catch(err => {
            console.log(err);
        });
}

const submitAMLDecision = decision => {
    decisionNotes.value = '';
    decisionNotesModal.value = true;
    decisionModalHeading.value = decisionTitles[decision];
    amlDecision.value = decision;
};

const setSelectedOption = (e, item) => {
    let index = amlResults.value.findIndex(
        x => x.EntityUniqueID == item.EntityUniqueID,
    );
    if (index != -1) amlResults.value[index].decision = e;
};

const isTrue = computed(() => {
    return amlResults.value.some(x => x.decision == 'TrueMatch');
});

const falsePositive = computed(() => {
    return amlResults.value.every(x => x.decision == 'FalsePositive');
});
</script>

<template>
    <div>
        <Head title="AML"/>
        <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
            <h2 class="text-xl font-semibold">AML</h2>
            <div class="flex gap-2">
                <Link href="/kyc/aml" preserve-scroll>
                    <x-button size="sm" color="primary" tag="div">AMl</x-button>
                </Link>
            </div>
        </div>

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">AML Id</dt>
                        <dd>{{ aml.id }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Quote Type</dt>
                        <dd>{{ aml.quote_type_text }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Quote Request ID</dt>
                        <Link
                            :href="`${aml.quote_type_id}/details/${aml.quote_request_id}`"
                            class="text-primary-500 hover:underline"
                        >
                            {{ aml.quote_request_id }}
                        </Link>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Input</dt>
                        <dd>{{ aml.input }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Created At</dt>
                        <dd>{{ aml.created_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Updated At</dt>
                        <dd>{{ aml.updated_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Results Found</dt>
                        <dd>{{ aml.results_found }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Search Type</dt>
                        <dd>{{ aml.search_type }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Screenshot</dt>
                        <dd>
                            <img
                                v-if="aml.screenshot"
                                :src="aml.screenshot"
                                alt="IMCRM"
                                class="w-auto"
                            />
                        </dd>
                    </div>
                </dl>
            </div>
            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">Results</h3>
                <x-divider class="mb-4 mt-1"/>
            </div>

            <DataTable
                table-class-name="compact tablefixed"
                :headers="tableHeader"
                :loading="loader.table"
                :items="amlResults || []"
                border-cell
                hide-rows-per-page
                hide-footer
                fixed-checkbox
            >
                <template #item-result="item">
                    <x-select
                        :modelValue="item.decision"
                        :options="[
                          { value: amlDecisionStatusCode.UNKNOWN, label: 'Unknown' },
                          { value: amlDecisionStatusCode.FALSE_POSITIVE, label: 'False Positive' },
                          { value: amlDecisionStatusCode.TRUE_MATCH, label: 'True Match' },
                        ]"
                        placeholder="Select Result"
                        class="w-full"
                        @update:modelValue="setSelectedOption($event, item)"
                    />
                </template>

                <template
                    v-if="responseFrom === 'Bridger'"
                    #item-full_name="{ EntityDetails }"
                >
                    {{ EntityDetails.Name.Full ?? '' }}
                </template>
                <template
                    v-if="responseFrom === 'RYU'"
                    #item-full_name="{ firstName, lastName }"
                >
                    {{ firstName + ' ' + lastName }}
                </template>

                <template v-if="responseFrom === 'RYU'" #item-date_of_birth="{ dob }">
                    {{ dob }}
                </template>

                <template
                    v-if="responseFrom === 'Bridger'"
                    #item-gender="{ EntityDetails }"
                >
                    {{ EntityDetails.Gender ?? '' }}
                </template>
                <template v-if="responseFrom === 'RYU'" #item-gender="{ gender }">
                    {{ gender ?? '' }}
                </template>

                <template v-if="responseFrom === 'RYU'" #item-customer_id="{ id }">
                    {{ id ?? '' }}
                </template>

                <template
                    v-if="responseFrom === 'Bridger'"
                    #item-country="{ EntityDetails }"
                >
                    {{
                        EntityDetails.Addresses.map(
                            nationality => nationality.Country,
                        ).toString() ?? ''
                    }}
                </template>
                <template v-if="responseFrom === 'RYU'" #item-country="{ pob }">
                    {{ pob ?? '' }}
                </template>

                <template
                    v-if="responseFrom === 'RYU'"
                    #item-citizenship="{ nationality }"
                >
                    {{ nationality ?? '' }}
                </template>

                <template #item-customer_type>
                    {{ aml.search_type }}
                </template>
            </DataTable>
            <div v-if="amlResults.length" class="flex justify-end">
                <x-button
                    class="mt-2 ml-2"
                    color="emerald"
                    size="sm"
                    @click="submitAMLDecision(amlDecisionStatusCode.FALSE_POSITIVE)"
                    :disabled="!falsePositive"
                >
                    False Positive
                </x-button>
                <x-button
                    v-if="hasRole(rolesEnum.ComplianceSuperUser)"
                    class="mt-2 ml-2"
                    color="orange"
                    size="sm"
                    @click="submitAMLDecision(amlDecisionStatusCode.TRUE_MATCH_REJECT_RISK)"
                    :disabled="!isTrue"
                >
                    True Match - Reject Risk
                </x-button>
                <x-button
                    v-if="hasRole(rolesEnum.ComplianceSuperUser)"
                    class="mt-2 ml-2"
                    color="red"
                    size="sm"
                    @click="submitAMLDecision(amlDecisionStatusCode.TRUE_MATCH_ACCEPT_RISK)"
                    :disabled="!isTrue"
                >
                    True Match - Accept Risk
                </x-button>
            </div>
            <x-modal v-model="decisionNotesModal" backdrop>
                <template #header>
                    {{ decisionModalHeading }}
                </template>
                <x-textarea
                    v-model="decisionNotes"
                    placeholder="Notes"
                    class="w-full"
                ></x-textarea>

                <template #actions>
                    <div class="text-right space-x-4">
                        <x-button @click="decisionNotesModal = false">Cancel</x-button>
                        <x-button :loading="submitDecisionLoading" @click="submitDecision(amlDecision)" color="success"
                        >Submit
                        </x-button
                        >
                    </div>
                </template>
            </x-modal>
        </div>
    </div>
</template>
