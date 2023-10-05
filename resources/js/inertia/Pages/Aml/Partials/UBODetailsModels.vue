<script setup>
    const props = defineProps({
        quoteDetails: Object,
        quoteType: Object,
        nationalities: Object,
        uboDetails: Object,
        uboRelations: Object,
        customerType: String,
        entity_id: Number
    });

    const notification = useToast();
    const dateFormat = date =>
        date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

    const nationalitiesOptions = computed(() => {
        return props.nationalities.map(nat => ({
            value: nat.id,
            label: nat.text,
        }));
    });

    const uboRelationOptions = computed(() => {
        return props.uboRelations.map(relation => ({
            value: relation.code,
            label: relation.text,
        }));
    });

    const addUBODetails = ref(false);
    const editUBODetails = ref(false);
    const addUBOToggle = () => {
        addUBODetails.value = !addUBODetails.value;
    };
    const UBODetailsTable = reactive({
        isLoading: false,
        columns: [
            {
                text: 'Full Name',
                value: 'first_name',
            },
            {
                text: 'Date of Birth',
                value: 'dob',
            },
            {
                text: 'Nationality',
                value: 'nationality',
            },
            {
                text: 'Position',
                value: 'relation',
            },
            {
                text: 'Action',
                value: 'action',
            },
        ],
    });

    const uboForm = reactive({
        quote_type: props.quoteType.code,
        customer_type: props.customerType,
        quote_request_id: props.quoteDetails.id,
        customer_id: props.quoteDetails.customer_id,
        entity_id: props.entity_id,
        id: null,
        first_name: '',
        dob: null,
        relation_code: null,
        nationality_id: null
    });

    function onEditUBO(data) {
        addUBODetails.value = true;
        editUBODetails.value = true;
        uboForm.quote_type = props.quoteType.code;
        uboForm.quote_request_id = data.quote_request_id;
        uboForm.id = data.id;
        uboForm.first_name = data.first_name;
        uboForm.dob = data.dob;
        uboForm.relation_code = data.relation_code;
        uboForm.nationality_id = data.nationality_id;
    }

    const onUBOSubmit = isValid => {
        if (!isValid) return;
        if(editUBODetails.value) {
            axios.put(`/members/${uboForm.id}`, uboForm)
                .then(res => {
                    notification.success({
                        title: 'UBO Updated Successfully',
                        position: 'top',
                    });
                    uboForm.reset();
                })
                .catch(err => {
                    notification.error({
                        title: 'Something went wrong',
                        position: 'top',
                    });
                });
        } else {
            axios.post(`/members`, uboForm)
                .then(res => {
                    notification.success({
                        title: 'UBO Added Successfully',
                        position: 'top',
                    });
                    uboForm.reset();
                    addUBODetails.value = false;
                })
                .catch(err => {
                    notification.error({
                        title: 'Something went wrong',
                        position: 'top',
                    });
                });
        }
    }
</script>

<template>
    <Transition name="fade">
        <div v-if="addUBODetails" class="mb-4">
            <h3 class="font-semibold text-primary-800 text-lg mb-3">
                Add UBO Details
            </h3>
            <x-button
                v-if="addUBODetails"
                @click.prevent="addUBOToggle"
                size="sm"
                color="red"
            >
                Hide
            </x-button>
            <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
                <x-field label="Full Name">
                    <x-input
                        v-model="uboForm.first_name"
                        placeholder="Full Name"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Nationality">
                    <ComboBox
                        :single="true"
                        v-model="uboForm.nationality_id"
                        placeholder="Select Nationality"
                        :options="nationalitiesOptions"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Date of Birth">
                    <DatePicker
                        v-model="uboForm.dob"
                        placeholder="Date of Birth"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Position">
                    <x-select
                        v-model="uboForm.relation_code"
                        placeholder="Select Position"
                        :options="uboRelationOptions"
                        class="w-full"
                    />
                </x-field>
            </dl>
        </div>
    </Transition>
    <x-divider v-if="addUBODetails" class="mb-3 mt-1" />

    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
            UBO Details
            <x-tag size="sm">{{ uboDetails.length || 0 }}</x-tag>
        </h3>
        <x-button
            v-if="addUBODetails"
            @click.prevent="onUBOSubmit"
            :loading="uboForm.loading"
            size="sm"
            color="success"
        >
            Submit UBO Details
        </x-button>
        <x-button
            v-if="!addUBODetails"
            @click.prevent="addUBOToggle"
            size="sm"
            color="orange"
        >
            Add UBO Details
        </x-button>
    </div>
    <DataTable
        table-class-name="tablefixed compact"
        :headers="UBODetailsTable.columns"
        :items="uboDetails || []"
        show-index
        border-cell
        hide-rows-per-page
        hide-footer
    >
        <template #item-index="{ code }">
            <div>{{ code }}</div>
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
            </div>
        </template>
    </DataTable>
</template>
