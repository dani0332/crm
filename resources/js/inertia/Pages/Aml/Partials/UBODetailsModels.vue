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

    const addNewUBODetails = ref(false);
    const editUBODetails = ref(false);
    const addNewUBOTrigger = () => {
        addNewUBODetails.value = !addNewUBODetails.value;
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
        customer_id: props.quoteDetails.customer_id,
        quote_type: props.quoteType.code,
        customer_type: props.customerType,
        entity_id: props.entity_id,
        id: null,
        first_name: '',
        dob: null,
        relation_code: null,
        nationality_id: null,
        quote_request_id: props.quoteDetails.id,

    });

    function onEditUBO(data) {
        addNewUBODetails.value = true;
        editUBODetails.value = true;
        uboForm.id = data.id;
        uboForm.first_name = data.first_name;
        uboForm.dob = data.dob;
        uboForm.relation_code = data.relation_code;
        uboForm.nationality_id = data.nationality_id;
        uboForm.quote_request_id = data.quote_request_id;
        uboForm.quote_type = props.quoteType.code;
    }

    const onUBOSubmit = isValid => {
        if (!isValid) return;
        if(editUBODetails.value) {
            axios.put(`/members/${uboForm.id}`, uboForm)
                .then(res => {
                    notification.success({
                        title: 'Member Updated Successfully',
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
                        title: 'Member Added Successfully',
                        position: 'top',
                    });
                    uboForm.reset();
                    addNewUBODetails.value = false;
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
        <div v-if="addNewUBODetails" class="mb-4">
            <p class="font-semibold text-center mb-5">Add UBO Details</p>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Full Name</dt>
                    <dd>
                        <x-input
                            v-model="uboForm.first_name"
                            placeholder="Member Name"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Nationality</dt>
                    <dd>
                        <ComboBox
                            v-model="uboForm.nationality_id"
                            :single="true"
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
                            v-model="uboForm.dob"
                            placeholder="Date of Birth"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Position</dt>
                    <dd>
                        <x-select
                            v-model="uboForm.relation_code"
                            placeholder="Select Position"
                            :options="uboRelationOptions"
                            class="w-full"
                        />
                    </dd>
                </div>
            </dl>
        </div>
    </Transition>
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
            UBO Details
            <x-tag size="sm">{{ uboDetails.length || 0 }}</x-tag>
        </h3>
        <x-button
            v-if="addNewUBODetails"
            @click.prevent="onUBOSubmit"
            :loading="uboForm.loading"
            size="sm"
            color="success"
        >
            Submit UBO Details
        </x-button>
        <x-button
            v-if="!addNewUBODetails"
            @click.prevent="addNewUBOTrigger"
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
