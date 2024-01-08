<script setup>
const props = defineProps({
    quote: Object,
    UBOsDetails: Object,
    UBORelations: {
        required: true,
        type: Array,
        default: []
    },
    nationalities: {
        required: true,
        type: Array,
        default: []
    },
    quote_type: {
        required: true,
        type: String,
    }
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();
const modals = reactive({
    ubo: false,
});

const dateFormat = date =>
    date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
    return page.props.nationalities.map(nat => ({
        value: nat.id,
        label: nat.text,
    }));
});

const UBORelationOptions = computed(() => {
    return page.props.UBORelations.map(relation => ({
        value: relation.code,
        label: relation.text,
    }));
});

const uboMembers = ref(props.UBOsDetails);
const computedUboMembers = computed(() => {
    return uboMembers && uboMembers.value && uboMembers.value.filter(x => !x.is_third_party_payer);
});

const isLoading = ref(false);
const UBOActionEdit = ref(false);
const UBODetailsTable = reactive({
    isLoading: false,
    columns: [
        {
            text: 'Name',
            value: 'first_name',
        },
        {
            text: 'Owner / Partner',
            value: 'relation',
        },
        {
            text: 'Nationality',
            value: 'nationality',
        },
        {
            text: 'Date of Birth',
            value: 'dob',
        },
        {
            text: 'Action',
            value: 'action',
        },
    ],
});

const UBOFieldReq = reactive({
    nationality: false,
    dob: false,
});

const UBOForm = useForm({
    id: null,
    first_name: '',
    dob: null,
    relation_code: null,
    nationality_id: null,
    quote_request_id: page.props.quote.id,
    customer_id: page.props.quote.customer_id,
    quote_type: props.quote_type,
    customer_type: page.props.quote.customer_type,
    entity_id: page.props.quote?.quote_request_entity_mapping?.entity_id ?? page.props.quote.entity_id
});

const addUBOModal = () => {
    UBOForm.reset();
    UBOActionEdit.value = false;
    modals.UBO = true;
};

function onEditUBO(data) {
    UBOActionEdit.value = true;
    modals.UBO = true;
    UBOForm.id = data.id;
    UBOForm.first_name = data.first_name;
    UBOForm.dob = data.dob;
    UBOForm.relation_code = data.relation_code;
    UBOForm.nationality_id = data.nationality_id;
    UBOForm.quote_request_id = data.quote_id;
    UBOForm.quote_type = props.quote_type;
}

const onUBOSubmit = isValid => {

    UBOFieldReq.nationality = (UBOForm.nationality_id == null);
    UBOFieldReq.dob = (UBOForm.dob == null);
    if (!isValid) return;
    isLoading.value = true;

    if (UBOActionEdit.value) {
        UBOForm.put(`/members/${UBOForm.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                notification.success({
                    title: 'UBO Updated',
                    position: 'top',
                });
                UBOForm.reset();
            },
            onFinish: () => {
                modals.UBO = false;
                isLoading.value = false;
            },
        });
    } else {
        UBOForm.post(`/members`, {
            preserveScroll: true,
            onSuccess: () => {
                notification.success({
                    title: 'UBO Added',
                    position: 'top',
                });
            },
            onFinish: () => {
                modals.UBO = false;
                isLoading.value = false;
            },
        });
    }
};

const confirmDeleteData = reactive({
    UBO: null,
});

const UBODelete = id => {
    modals.UBOConfirm = true;
    confirmDeleteData.UBO = id;
};

const UBODeleteConfirmed = () => {
    UBOForm.delete(`/members/${props.quote.customer_type}-${props.quote_type}-${confirmDeleteData.UBO}`, {
        preserveScroll: true,
        onSuccess: () => {
            notification.success({
                title: 'UBO Deleted',
                position: 'top',
            });
        },
        onFinish: () => {
            modals.UBOConfirm = false;
        },
    });
};

</script>

<template>
    <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-primary-800 text-lg">
                UBO Details
                <x-tag size="sm">{{ computedUboMembers && computedUboMembers.length || 0 }}</x-tag>
            </h3>
            <x-button
                v-if="page.props.quote?.quote_request_entity_mapping?.entity_id ?? page.props.quote.entity_id"
                @click.prevent="addUBOModal" size="sm" color="orange" :loading="isLoading">
                Add UBO
            </x-button>
        </div>

        <DataTable
            table-class-name="tablefixed compact"
            :headers="UBODetailsTable.columns"
            :items="computedUboMembers || []"
            show-index
            border-cell
            hide-rows-per-page
            hide-footer
        >
            <template #item-index="{ index, code }">
                <div>{{ code ?? 'UBO ' + index }}</div>
            </template>
            <template #item-dob="{ dob }">
                {{ dateFormat(dob) }}
            </template>
            <template #item-relation="{ relation }">
                {{ relation?.text }}
            </template>
            <template #item-nationality="{ nationality }">
                {{ nationality?.text }}
            </template>
            <template #item-action="item">
                <div class="flex gap-2">
                    <x-button
                        size="xs"
                        color="primary"
                        outlined
                        @click.prevent="onEditUBO(item)"
                    >
                        Edit
                    </x-button>
                    <x-button
                        size="xs"
                        color="error"
                        outlined
                        @click.prevent="UBODelete(item.id)"
                    >
                        Delete
                    </x-button>
                </div>
            </template>
        </DataTable>

        <x-modal v-model="modals.UBO" size="lg" show-close backdrop>
            <template #header>
                {{ UBOActionEdit ? 'Edit' : 'Add' }} UBO
            </template>

            <x-form @submit="onUBOSubmit" :auto-focus="false">
                <div class="grid md:grid-cols-2 gap-4">
                    <input type="hidden" :value="UBOForm.id" />
                    <x-input
                        v-model="UBOForm.first_name"
                        :rules="[isRequired]"
                        label="Name"
                        placeholder="Name"
                    />
                    <x-select
                        v-model="UBOForm.relation_code"
                        :rules="[isRequired]"
                        label="Owner / Partner"
                        :options="UBORelationOptions"
                        placeholder="Select Owner / Partner"
                        class="w-full"
                    />
                    <DatePicker
                        :rules="[isRequired]"
                        v-model="UBOForm.dob"
                        label="DOB"
                        :hasError="UBOFieldReq.dob"
                    />
                    <ComboBox
                        required
                        v-model="UBOForm.nationality_id"
                        label="Nationality"
                        :options="nationalitiesOptions"
                        placeholder="Select Nationality"
                        :single="true"
                        :hasError="UBOFieldReq.nationality"
                    />
                </div>

                <div class="text-right space-x-4 mt-8">
                    <x-button size="sm" @click.prevent="modals.UBO = false">
                        Cancel
                    </x-button>

                    <x-button
                        size="sm"
                        color="emerald"
                        :loading="UBOForm.processing"
                        type="submit"
                    >
                        {{ UBOActionEdit ? 'Update' : 'Save' }}
                    </x-button>
                </div>
            </x-form>
        </x-modal>

        <x-modal v-model="modals.UBOConfirm" show-close backdrop>
            <template #header> Delete UBO Detail </template>
            <p>Are you sure you want to delete this?</p>
            <template #actions>
                <div class="text-right space-x-4">
                    <x-button
                        size="sm"
                        ghost
                        @click.prevent="modals.UBOConfirm = false"
                    >
                        Cancel
                    </x-button>
                    <x-button
                        size="sm"
                        color="error"
                        @click.prevent="UBODeleteConfirmed"
                        :loading="UBOForm.processing"
                    >
                        Delete
                    </x-button>
                </div>
            </template>
        </x-modal>
    </div>
</template>
