<script setup>
import {router, useForm, usePage} from "@inertiajs/vue3";
import {computed, reactive, ref} from "vue";
import {useNotifications} from "@indielayer/ui";

defineProps({
    quote: Object,
    activities: Array,
    can: Object,
    advisors: Object,
    quoteType: String
});

const notification = useNotifications('toast');

const page = usePage();

const rules = {
    isRequired: v => !!v || 'This field is required',
};

const modals = reactive({
    activity: false,
    activityConfirm: false,
});

const activityActionEdit = ref(false);

const advisorOptions = computed(() => {
    return page.props.advisors.map(advisor => ({
        value: advisor.id,
        label: advisor.name,
    }));
});

const activityTable = [
    { text: 'Done', value: 'status', width: 60, align: 'center' },
    { text: 'Title', value: 'title' },
    { text: 'Client Name', value: 'client_name' },
    { text: 'Followup Date', value: 'due_date' },
    { text: 'Assigned To', value: 'assignee' },
    { text: 'Action', value: 'action' },
];

const activityForm = useForm({
    uuid: page.props.quote.uuid,
    quote_id: page.props.quote.id,
    quote_type: page.props.quoteType,
    quote_type_id: 8,
    title: null,
    description: null,
    due_date: null,
    assignee_id: null,
    status: null,
    activity_id: null,
});

const addActivity = () => {
    activityForm.reset();
    activityActionEdit.value = false;
    modals.activity = true;
};

const onActivityStatusUpdate = id => {
    activityForm.activity_id = id;
    activityForm.post(`/activities/updateStatus`, {
        preserveScroll: true,
        onSuccess: () => {
            notification.success({
                title: 'Lead Activity Done',
                position: 'top',
            });
        },
    });
};

const activityEdit = data => {
    activityActionEdit.value = true;
    modals.activity = true;
    activityForm.activity_id = data.id;
    activityForm.uuid = data.uuid;
    activityForm.title = data.title;
    activityForm.description = data.description;
    activityForm.due_date = data.due_date
        ? data.due_date.split(' ')[0].split('-').reverse().join('-') +
        'T' +
        data.due_date.split(' ')[1]
        : null;
    activityForm.assignee_id = data.assignee_id;
    activityForm.status = data.status;
};

const onActivitySubmit = isValid => {

    if (!isValid) return;

    let url = `/activities/v2/`;
    let method = `post`;

    if (activityActionEdit.value) {
        url += activityForm.activity_id;
        method = 'patch';
    }

    activityForm.submit(method, url, {
        preserveScroll: true,
        onSuccess: () => {
            notification.success({
                title: 'Activity saved',
                position: 'top',
            });
        },
        onFinish: () => {
            modals.activity = false;
        },
    });
};

const activityDelete = id => {
    modals.activityConfirm = true;
    confirmDeleteData.activity = id;
};

const confirmDeleteData = reactive({
    activity: null
});

const activityDeleteConfirmed = () => {
    router.post(
        `/activities/${confirmDeleteData.activity}/delete`,
        {
            isInertia: true,
            quote_uuid: page.props.quote.uuid,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                notification.error({
                    title: 'Activity Deleted',
                    position: 'top',
                });
            },
            onFinish: () => {
                modals.activityConfirm = false;
            },
        },
    );
};

</script>

<template>

    <div class="p-4 rounded shadow mb-6 bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-semibold text-primary-800 text-lg">
                Lead Activities
                <x-tag size="sm">{{ activities.length || 0 }}</x-tag>
            </h3>
            <x-button size="sm" color="orange" @click.prevent="addActivity">
                Add Activity
            </x-button>
        </div>
        <x-divider class="my-4" />

        <DataTable
            table-class-name="compact"
            :headers="activityTable"
            :items="activities"
            border-cell
            hide-rows-per-page
            :rows-per-page="15"
            :hide-footer="activities.length < 15"
        >
            <template #item-status="{ status, id }">
                <x-checkbox
                    color="emerald"
                    size="xl"
                    :modelValue="status === 1"
                    :disabled="status === 1"
                    @change="onActivityStatusUpdate(id)"
                />
            </template>
            <template #item-action="item">
                <div class="space-x-4">
                    <x-button
                        size="xs"
                        color="primary"
                        outlined
                        :disabled="item.status === 1"
                        @click.prevent="activityEdit(item)"
                    >
                        Edit
                    </x-button>
                    <x-button
                        size="xs"
                        color="error"
                        :disabled="item.status === 1"
                        outlined
                        @click.prevent="activityDelete(item.id)"
                    >
                        Delete
                    </x-button>
                </div>
            </template>
        </DataTable>
        <x-modal v-model="modals.activity" size="lg" show-close backdrop>
            <template #header>
                {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
            </template>

            <x-form @submit="onActivitySubmit" :auto-focus="false">
                <div class="grid gap-4">
                    <x-input
                        v-model="activityForm.title"
                        label="Title"
                        :rules="[rules.isRequired]"
                        class="w-full"
                    />

                    <x-textarea
                        v-model="activityForm.description"
                        label="Description"
                        :adjust-to-text="false"
                        class="w-full"
                    />

                    <x-select
                        v-model="activityForm.assignee_id"
                        label="Assignee"
                        :options="advisorOptions"
                        :rules="[rules.isRequired]"
                        placeholder="Select Assignee"
                        class="w-full"
                    />

                    <x-input
                        v-model="activityForm.due_date"
                        label="Due Date"
                        type="datetime-local"
                        :rules="[rules.isRequired]"
                        class="w-full"
                    />
                </div>

                <div class="text-right space-x-4 mt-12">
                    <x-button size="sm" @click.prevent="modals.activity = false">
                        Cancel
                    </x-button>

                    <x-button
                        size="sm"
                        color="emerald"
                        :loading="activityForm.processing"
                        type="submit"
                    >
                        {{ activityActionEdit ? 'Update' : 'Save' }}
                    </x-button>
                </div>
            </x-form>
        </x-modal>
        <x-modal v-model="modals.activityConfirm" show-close backdrop>
            <template #header> Delete Activity </template>
            <p>Are you sure you want to delete this activity?</p>
            <template #actions>
                <div class="text-right space-x-4">
                    <x-button
                        size="sm"
                        ghost
                        @click.prevent="modals.activityConfirm = false"
                    >
                        Cancel
                    </x-button>
                    <x-button
                        size="sm"
                        color="error"
                        :loading="activityForm.processing"
                        @click.prevent="activityDeleteConfirmed"
                    >
                        Delete
                    </x-button>
                </div>
            </template>
        </x-modal>
    </div>

</template>
