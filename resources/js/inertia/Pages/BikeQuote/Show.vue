<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, usePage, router, useForm, Link } from '@inertiajs/vue3';
import { useDateFormat, useClipboard } from '@vueuse/core';

import { useNotifications } from '@indielayer/ui';

defineProps({
    bikeQuote: Object,
})

const page = usePage();

const historyLoading = ref(false);

// history data
const historyData = ref(null);

const onLoadHistoryData = async () => {
    historyLoading.value = true;
    const res = await fetch(
        `/quotes/getLeadHistory?modelType=health&recordId=${page.props.quote.id}`,
    );
    const finalRes = await res.json();
    historyData.value = finalRes;
    historyLoading.value = false;
};

const historyDataTable = [
    { text: 'Modified At', value: 'ModifiedAt' },
    { text: 'Modified By', value: 'ModifiedBy' },
    { text: 'Notes', value: 'NewNotes' },
    { text: 'Lead Status', value: 'NewStatus' },
];

</script>

<template>
    <div>
        <Head title="Bike Quotes" />

        <div class="flex justify-between items-center flex-wrap gap-2 mb-5" >
            <h2 class="text-xl font-semibold">Bike Detail</h2>
            <div class="flex gap-2">

                <Link :href="`/personal-quotes/bike/${quote.uuid}/edit`">
                    <x-button size="sm" tag="div">Edit</x-button>
                </Link>

                <Link href="/personal-quotes/bike" preserve-scroll>
                    <x-button size="sm" color="primary" tag="div"> Bike Quotes </x-button>
                </Link>
            </div>
        </div>

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CDB ID</dt>
                        <dd>{{ quote.uuid }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED DATE</dt>
                        <dd>{{ quote.created_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR</dt>
                        <dd>{{ quote.advisor?.email }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SOURCE</dt>
                        <dd>{{ quote.source }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST MODIFIED DATE</dt>
                        <dd>{{ quote.updated_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">RENEWAL BATCH</dt>
                        <dd>{{ quote.renewal_batch }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LOST REASON</dt>
                        <dd>{{ quote.quoteDetail?.id }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DEVICE</dt>
                        <dd>{{ quote.device }}</dd>
                    </div>
                </dl>
            </div>


            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">Customer Profile</h3>
                <x-divider class="mb-4 mt-1" />
            </div>

            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">FIRST NAME</dt>
                        <dd>{{ quote.first_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST NAME</dt>
                        <dd>{{ quote.last_name }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">MOBILE NUMBER</dt>
                        <dd>{{ quote.mobile_no }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMAIL</dt>
                        <dd>{{ quote.email }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NATIONALITY</dt>
                        <dd>{{ quote.nationality?.text }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DATE OF BIRTH</dt>
                        <dd>{{ quote.dob }}</dd>
                    </div>

                </dl>
            </div>


            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">Policy Details</h3>
                <x-divider class="mb-4 mt-1" />
            </div>

            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">POLICY NUMBER</dt>
                        <dd>{{ quote.policy_number }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS QUOTE POLICY NUMBER</dt>
                        <dd>{{ quote.previous_quote_policy_number }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">POLICY START DATE</dt>
                        <dd>{{ quote.policy_start_date }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">POLICY END DATE</dt>
                        <dd>{{ quote.policy_issuance_date }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREMIUM</dt>
                        <dd>{{ quote.premium }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TRANSAPP CODE</dt>
                        <dd>{{ quote.transapp_code }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">Policy Policy Details</h3>
                <x-divider class="mb-4 mt-1" />
            </div>

            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS QUOTE POLICY NUMBER</dt>
                        <dd>{{ quote.previous_quote_policy_number }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
                        <dd>{{ quote.previous_policy_expiry_date }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
                        <dd>{{ quote.previous_quote_policy_premium }}</dd>
                    </div>

                </dl>
            </div>

        </div>

        <!--  show lead history data -->
        <div class="p-4 rounded shadow mb-6 bg-white">
            <div>
                <h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
                <x-divider class="mb-4 mt-1" />
            </div>
            <div v-if="historyData === null" class="text-center py-3">
                <x-button
                    size="sm"
                    color="primary"
                    outlined
                    @click.prevent="onLoadHistoryData"
                    :loading="historyLoading"
                >
                    Load History Data
                </x-button>
            </div>
            <DataTable
                v-else
                table-class-name="compact"
                :headers="historyDataTable"
                :items="historyData || []"
                border-cell
                hide-rows-per-page
                :rows-per-page="15"
                :hide-footer="historyData.length < 15"
            />
        </div>

</div>

</template>
