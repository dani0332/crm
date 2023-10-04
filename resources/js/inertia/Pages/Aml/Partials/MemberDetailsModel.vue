<script setup>

    const props = defineProps({
        quoteDetails: Object,
        quoteType: Object,
        nationalities: Object,
        membersDetails: Object,
        memberRelations: Object,
        customerType: String
    });

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

    const addNewMember = ref(false);
    const editMemberDetails = ref(false);
    const addNewMemberTrigger = () => {
        addNewMember.value = !addNewMember.value;
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
        id: null,
        first_name: '',
        dob: null,
        relation_code: null,
        nationality_id: null,
        quote_request_id: props.quoteDetails.id,
        customer_id: props.quoteDetails.customer_id,
        quote_type: props.quoteType.code,
        customer_type: props.customerType
    });

    function onEditMember(data) {
        addNewMember.value = true;
        editMemberDetails.value = true;
        memberForm.id = data.id;
        memberForm.first_name = data.first_name;
        memberForm.dob = data.dob;
        memberForm.relation_code = data.relation_code;
        memberForm.nationality_id = data.nationality_id;
        memberForm.quote_request_id = data.quote_request_id;
        memberForm.quote_type = props.quoteType.code;
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
                    addNewMember.value = false;
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
        <div v-if="addNewMember" class="mb-4">
            <p class="font-semibold text-center mb-5">Add Member</p>
            <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Member Name</dt>
                    <dd>
                        <x-input
                            v-model="memberForm.first_name"
                            placeholder="Member Name"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Nationality</dt>
                    <dd>
                        <ComboBox
                            v-model="memberForm.nationality_id"
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
                            v-model="memberForm.dob"
                            placeholder="Date of Birth"
                            class="w-full"
                        />
                    </dd>
                </div>
                <div class="grid sm:grid-cols-2">
                    <dt class="font-medium">Relation</dt>
                    <dd>
                        <x-select
                            v-model="memberForm.relation_code"
                            placeholder="Select Relation"
                            :options="memberRelationOptions"
                            class="w-full"
                        />
                    </dd>
                </div>
            </dl>
        </div>
    </Transition>
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
            Member Details
            <x-tag size="sm">{{ membersDetails.length || 0 }}</x-tag>
        </h3>
        <x-button
            v-if="addNewMember"
            @click.prevent="onMemberSubmit"
            :loading="memberForm.loading"
            size="sm"
            color="success"
        >
            Submit Member
        </x-button>
        <x-button
            v-if="!addNewMember"
            @click.prevent="addNewMemberTrigger"
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
