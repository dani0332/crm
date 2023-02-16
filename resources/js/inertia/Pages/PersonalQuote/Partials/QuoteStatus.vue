<script setup>

import {useForm, usePage} from "@inertiajs/vue3";
import {computed} from "vue";
import {useNotifications} from "@indielayer/ui";

defineProps({
    quote: Object,
    documentTypes: Object,
    quoteStatuses: Object,
    storageUrl: String
})

const notification = useNotifications('toast');

const page = usePage();

const quoteStatusOptions = computed(() => {
    return page.props.quoteStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

const quoteStatusForm = useForm({
    quote_uuid: page.props.quote.uuid,
    quote_status_id:  null,
    notes: page.props.quote.notes || null,
    trans_code: page.props.quote.transapp_code || null,
    lost_reason_id: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
    quoteStatusForm.patch(
        `/personal-quotes/bike/${page.props.quote.id}/update-status`,
        {
            preserveScroll: true,
            onError: errors => {
                console.log(errors);
            },
            onSuccess: () => {
                notification.success({
                    title: 'Quote Status Updated',
                    position: 'top',
                });
            },
        },
    );
};

</script>

<template>
    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
        <div>
            <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
            <x-divider class="mb-4 mt-1" />
        </div>
        <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
            <div class="w-full md:w-2/3">
                <x-textarea
                    v-model="quoteStatusForm.notes"
                    type="text"
                    label="Notes"
                    placeholder="Lead Notes"
                    class="w-full"
                    :error="quoteStatusForm.errors.notes"
                    :disabled="quote.quote_status_id == 15"
                />
            </div>
            <div class="w-full md:w-1/3">
                <div class="flex flex-col gap-4">
                    <x-select
                        v-model="quoteStatusForm.quote_status_id"
                        label="Status"
                        :error="quoteStatusForm.errors.quote_status_id"
                        :options="quoteStatusOptions"
                        :disabled="quote.quote_status_id == 15"
                        placeholder="Lead Status"
                        class="w-full"
                    />
                    <x-input
                        v-if="quoteStatusForm.leadStatus == 15"
                        v-model="leadStatusForm.trans_code"
                        label="TransApp Code"
                        placeholder="TransApp Code is required"
                        class="w-full"
                        :error="quoteStatusForm.errors.trans_code"
                    />
                    <x-select
                        v-if="quoteStatusForm.leadStatus == 17"
                        v-model="quoteStatusForm.lostReason"
                        label="Lost Reason"
                        :options="
                lostReasons?.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
                        placeholder="Lost Reason is required"
                        class="w-full"
                        :error="quoteStatusForm.errors.lostReason"
                    />
                </div>

                <div class="flex justify-end">
                    <x-button
                        class="mt-4"
                        color="emerald"
                        size="sm"
                        :loading="quoteStatusForm.processing"
                        @click.prevent="onLeadStatus"
                    >
                        Change Status
                    </x-button>
                </div>
            </div>
        </div>
    </div>
</template>
