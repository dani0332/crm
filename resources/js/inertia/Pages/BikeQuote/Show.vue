<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, usePage, router, useForm, Link } from '@inertiajs/vue3';
import { useDateFormat, useClipboard } from '@vueuse/core';

import { useNotifications } from '@indielayer/ui';

defineProps({
    bikeQuote: Object,
})

const page = usePage();

</script>

<template>
    <div>
        <Head title="Bike Quotes" />

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CDB ID</dt>
                        <dd>{{ bikeQuote.uuid }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED DATE</dt>
                        <dd>{{ bikeQuote.created_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR</dt>
                        <dd>{{ bikeQuote.advisor?.email }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SOURCE</dt>
                        <dd>{{ bikeQuote.source }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST MODIFIED DATE</dt>
                        <dd>{{ bikeQuote.updated_at }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">RENEWAL BATCH</dt>
                        <dd>{{ bikeQuote.renewal_batch }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LOST REASON</dt>
                        <dd>{{ bikeQuote.quoteDetail?.id }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DEVICE</dt>
                        <dd>{{ bikeQuote.device }}</dd>
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
                        <dd>{{ bikeQuote.first_name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST NAME</dt>
                        <dd>{{ bikeQuote.last_name }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">MOBILE NUMBER</dt>
                        <dd>{{ bikeQuote.mobile_no }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMAIL</dt>
                        <dd>{{ bikeQuote.email }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NATIONALITY</dt>
                        <dd>{{ bikeQuote.nationality?.text }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DATE OF BIRTH</dt>
                        <dd>{{ bikeQuote.dob }}</dd>
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
                        <dd>{{ bikeQuote.policy_number }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS QUOTE POLICY NUMBER</dt>
                        <dd>{{ bikeQuote.previous_quote_policy_number }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">POLICY START DATE</dt>
                        <dd>{{ bikeQuote.policy_start_date }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">POLICY END DATE</dt>
                        <dd>{{ bikeQuote.policy_issuance_date }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREMIUM</dt>
                        <dd>{{ bikeQuote.premium }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TRANSAPP CODE</dt>
                        <dd>{{ bikeQuote.transapp_code }}</dd>
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
                        <dd>{{ bikeQuote.previous_quote_policy_number }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
                        <dd>{{ bikeQuote.previous_policy_expiry_date }}</dd>
                    </div>

                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
                        <dd>{{ bikeQuote.previous_quote_policy_premium }}</dd>
                    </div>

                </dl>
            </div>

        </div>

</div>

</template>
