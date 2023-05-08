<script setup>
import {reactive} from "vue";

const page = usePage();
const notification = useNotifications('toast');

const loader = reactive({
    table: false,
    export: false,
});

defineProps({
    dynamic_route: String,
    leadStatuses: Array,
    advisors: Array,
});

</script>

<template>
    <div>
        <Head title="Car Detail" />
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Car Reveival Detail</h2>
            <div class="flex gap-2">
                <x-button size="sm" color="#ff5e00">
                    Duplicate Lead
                </x-button>

                <Link href="/quotes/health" preserve-scroll>
                    <x-button size="sm" color="primary" tag="div"> Car Revival List </x-button>
                </Link>

                <Link href="/quotes/health">
                    <x-button size="sm" tag="div">Edit</x-button>
                </Link>
            </div>
        </div>
        <x-divider class="my-4" />

        <div class="p-4 rounded shadow mb-6 bg-white">
            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">E-COM Details</h3>
                <x-divider class="mb-4 mt-1" />
            </div>
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREMIUM</dt>
                        <!--                    <dd>{{ quote.code }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PAID AT</dt>
                        <!--                    <dd>{{ quote.created_at }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PAYMENT STATUS
                        </dt>
                        <!--                    <dd>{{ quote.health_team_type }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PROVIDER NAME</dt>
                        <!--                    <dd>{{ quote.advisor_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PAYMENT METHOD</dt>
                        <!--                    <dd>{{ quote.source }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PLAN NAME</dt>
                        <!--                    <dd>{{ quote.updated_at }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADDONS</dt>
                        <!--                    <dd>{{ quote.parent_duplicate_quote_id }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">IS ECOMMERCE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">QUOTE LINK</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ORDER REFERENCE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PAYMENT REFERENCE</dt>
                    </div>
                </dl>
            </div>
            <x-divider class="mb-4 mt-1" />
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CDB ID</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED DATE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SUBTEAM</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SOURCE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST MODIFIED DATE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PARENT CDB ID</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">IS ECOMMERCE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">IS EBP RENEWAL</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">RENEWAL BATCH</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LOST REASON</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TRANSAPP CODE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DEVICE</dt>
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
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LAST NAME</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">MOBILE NUMBER</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMAIL</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">GENDER</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">MARITAL STATUS</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NATIONALITY</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DATE OF BIRTH</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMIRATE OF VISA</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">MEMBER CATEGORY</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SALARY BAND</dt>
                    </div>
                </dl>
            </div>
            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">Quote Details</h3>
                <x-divider class="mb-4 mt-1" />
            </div>

            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">
                            FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?
                        </dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TYPE OF PLAN</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DETAILS</dt>
                    </div>
                </dl>
            </div>

            <div class="mt-6">
                <h3 class="font-semibold text-primary-800">
                    Last Year's Policy Details
                </h3>
                <x-divider class="mb-4 mt-1" />
            </div>
            <div class="text-sm">
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>
