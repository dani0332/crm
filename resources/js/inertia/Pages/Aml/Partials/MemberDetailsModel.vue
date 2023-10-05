<script setup>
    const props = defineProps({
        quoteType: Object,
        quoteDetails: Object,
        nationalities: Object,
        membersDetails: Object,
        memberRelations: Object,
        customerType: String
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

    const memberRelationOptions = computed(() => {
        return props.memberRelations.map(relation => ({
            value: relation.code,
            label: relation.text,
        }));
    });

    const addMember = ref(false);
    const editMemberDetails = ref(false);
    const addMemberToggle = () => {
        addMember.value = !addMember.value;
    };
    const memberDetailsTable = reactive({
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
                text: 'Relation',
                value: 'relation',
            },
            {
                text: 'Action',
                value: 'action',
            },
        ],
    });

    const memberForm = reactive({
        quote_type: props.quoteType.code,
        customer_type: props.customerType,
        quote_request_id: props.quoteDetails.id,
        customer_id: props.quoteDetails.customer_id,
        id: null,
        first_name: null,
        dob: null,
        relation_code: null,
        nationality_id: null
    });

    function onEditMember(data) {
        addMember.value = true;
        editMemberDetails.value = true;
        memberForm.quote_type = props.quoteType.code;
        memberForm.quote_request_id = props.quoteDetails.id;
        memberForm.id = data.id;
        memberForm.first_name = data.first_name;
        memberForm.dob = data.dob;
        memberForm.relation_code = data.relation_code;
        memberForm.nationality_id = data.nationality_id;
    }

    const onMemberSubmit = isValid => {
        if (!isValid) return;
        if(editMemberDetails.value) {
            axios.put(`/members/${memberForm.id}`, memberForm)
                .then(res => {
                    notification.success({
                        title: 'Member Updated Successfully',
                        position: 'top',
                    });
                    memberForm.reset();
                })
                .catch(err => {
                    notification.error({
                        title: 'Something went wrong',
                        position: 'top',
                    });
                });
        } else {
            axios.post(`/members`, memberForm)
                .then(res => {
                    notification.success({
                        title: 'Member Added Successfully',
                        position: 'top',
                    });
                    memberForm.reset();
                    addMember.value = false;
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
        <div v-if="addMember" class="mb-4">
            <h3 class="font-semibold text-primary-800 text-lg mb-3">
                Add Member
            </h3>
            <x-button
                v-if="addMember"
                @click.prevent="addMemberToggle"
                size="sm"
                color="red"
            >
                Hide
            </x-button>
            <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
                <x-field label="Member Name">
                    <x-input
                        v-model="memberForm.first_name"
                        placeholder="Member Name"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Nationality">
                    <ComboBox
                        :single="true"
                        v-model="memberForm.nationality_id"
                        placeholder="Select Nationality"
                        :options="nationalitiesOptions"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Date of Birth">
                    <DatePicker
                        v-model="memberForm.dob"
                        placeholder="Date of Birth"
                        class="w-full"
                    />
                </x-field>
                <x-field label="Relation">
                    <x-select
                        v-model="memberForm.relation_code"
                        placeholder="Select Relation"
                        :options="memberRelationOptions"
                        class="w-full"
                    />
                </x-field>
            </dl>
        </div>
    </Transition>
    <x-divider v-if="addMember" class="mb-3 mt-1" />

    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
            Member Details
            <x-tag size="sm">{{ membersDetails.length || 0 }}</x-tag>
        </h3>
        <x-button
            v-if="addMember"
            @click.prevent="onMemberSubmit"
            :loading="memberForm.loading"
            size="sm"
            color="primary"
        >
            Submit Member
        </x-button>
        <x-button
            v-if="!addMember"
            @click.prevent="addMemberToggle"
            size="sm"
            color="orange"
        >
            Add Member
        </x-button>
    </div>
    <DataTable
        table-class-name="tablefixed compact"
        :headers="memberDetailsTable.columns"
        :items="membersDetails || []"
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
                    @click.prevent="onEditMember(item)"
                >
                    Edit
                </x-button>
            </div>
        </template>
    </DataTable>
</template>
