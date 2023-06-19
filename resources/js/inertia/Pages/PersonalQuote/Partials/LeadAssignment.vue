<script setup>
import {router} from "@inertiajs/vue3";

const props = defineProps({
    selected: {
        type: Array,
        default: () => [],
    },
    advisors: {
        type: Array,
        default: () => [],
    },
    quoteType: {
        type: String
    }
});

const emit = defineEmits(['success', 'error']);

if (!String.prototype.hasOwnProperty('capitalizeFirstChar')) {
    Object.defineProperty(String.prototype, 'capitalizeFirstChar', {
        get: function () {
            return function () {
                return this.charAt(0).toUpperCase() + this.slice(1);
            };
        },
        enumerable: false
    });
}

const {isRequired} = useRules();

const assignForm = useForm({
    assigned_advisor_id: null,
    assigned_lead_id: '',
    manual_assignment_email_flag: '1',
    modelType: props.quoteType,
});

function onAssignLead(isValid) {
    if (isValid) {
        assignForm
            .transform(data => ({
                ...data,
                assigned_lead_id: `${props.selected}`,
                assignment_type: assignForm.manual_assignment_email_flag,
            }))
            .post(`/quotes/${props.quoteType}/leadAssign`, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    assignForm.processing = false;
                    emit('success');
                    router.reload({
                        preserveScroll:true,
                    });
                },
                onError: () => {
                    assignForm.processing = false;
                    emit('error');
                },
            });
    }
}
</script>

<template>
    <section class="mb-4">
        <div class="px-4 py-6 rounded shadow mb-4 bg-primary-50/50">
            <h3 class="font-semibold text-primary-800">Assign Leads</h3>
            <x-divider class="mb-4 mt-1"/>
            <x-form @submit="onAssignLead" :auto-focus="false">
                <div class="w-full flex flex-col md:flex-row gap-4">
                    <ComboBox
                        v-model="assignForm.assigned_advisor_id"
                        label="Assign Advisor"
                        :options="props.advisors"
                        placeholder="Select Advisor"
                        class="flex-1 w-auto"
                        single
                    />
                    <x-select
                        v-model="assignForm.manual_assignment_email_flag"
                        label="Assignment Type"
                        :options="[
              { value: '1', label: 'Without Email' },
              { value: '2', label: 'With Email' },
            ]"
                        placeholder="Select Type"
                        class="flex-1 w-auto"
                        :rules="[isRequired]"
                    />
                    <div class="mb-3 md:pt-6">
                        <x-button
                            color="orange"
                            size="sm"
                            type="submit"
                            :loading="assignForm.processing"
                        >
                            Assign
                        </x-button>
                    </div>
                </div>
            </x-form>
            <x-alert
                v-if="Object.keys($page.props.errors).length > 0"
                color="error"
                outlined
                light
            >
                <ul class="list-disc list-inside">
                    <li v-for="error in $page.props.errors" :key="error">
                        {{ error }}
                    </li>
                </ul>
            </x-alert>
        </div>
    </section>
</template>
