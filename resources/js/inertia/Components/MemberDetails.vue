<script setup>

defineProps({
    membersDetails: Object,
    nationalities: Array
});

const page = usePage();
const modals = reactive({
    member: false,
    // memberConfirm: false,
});

const nationalitiesOptions = computed(() => {
    return page.props.nationalities.map(nat => ({
        value: nat.id,
        label: nat.text,
    }));
});

const memberActionEdit = ref(false);
const memberDetailsTable = reactive({
    isLoading: false,
    columns: [
        {
            text: 'Member Name',
            value: 'gender',
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
            text: 'Relation',
            value: 'emirate',
        },
    ],
});

const memberForm = useForm({
    id: null,
    name: '',
    nationality_id: null ,
    dob: null,
    relation_id: null,
});

const addMemberModal = () => {
    memberForm.reset();
    // memberActionEdit.value = false;
    modals.member = true;
};

</script>

<template>
    <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-primary-800 text-lg">
                Member Details
                <x-tag size="sm">{{ 0 }}</x-tag>
            </h3>
            <x-button @click.prevent="addMemberModal" size="sm" color="orange">
                Add Member
            </x-button>
        </div>
        <DataTable
            table-class-name="tablefixed compact"
            :headers="memberDetailsTable.columns"
            :items="[]"
            show-index
            border-cell
            hide-rows-per-page
            hide-footer
        />

        <x-modal v-model="modals.member" size="lg" show-close backdrop>
            <template #header>
                {{ memberActionEdit ? 'Edit' : 'Add' }} Member
            </template>

            <x-form :auto-focus="false">
                <div class="grid md:grid-cols-2 gap-4">
                    <input type="hidden" :value="memberForm.id" />
                    <x-input
                        v-model="memberForm.name"
                        label="Member Name"
                        placeholder="Member Name"
                    />
                    <ComboBox
                        v-model="memberForm.nationality_id"
                        label="Nationality"
                        :options="nationalitiesOptions"
                        placeholder="Select Nationality"
                        :single="true"
                    />
                    <DatePicker
                        v-model="memberForm.dob"
                        label="Date of Birth"
                    />
                    <x-select
                        v-model="memberForm.relation_id"
                        label="Relation"
                        :options="relationOptions"
                        placeholder="Select Relation"
                        class="w-full"
                    />
                </div>

                <div class="text-right space-x-4 mt-8">
                    <x-button size="sm" @click.prevent="modals.member = false">
                        Cancel
                    </x-button>

                    <x-button
                        size="sm"
                        color="emerald"
                        :loading="memberForm.processing"
                        type="submit"
                    >
                        {{ memberActionEdit ? 'Update' : 'Save' }}
                    </x-button>
                </div>
            </x-form>
        </x-modal>

    </div>
</template>
