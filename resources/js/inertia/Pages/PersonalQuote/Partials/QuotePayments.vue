<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';
import axios from 'axios';

const notification = useNotifications('toast');
const page = usePage();

defineProps({
    payments: Object,
    isBetaUser: Boolean,
    can: Object,
    quoteRequest: Object,
    paymentMethods: Object,
    insuranceProviders: Object,
    personalPlans: Object,
    quote: Object,
    quoteType: String
});

const createPaymentModal = ref(false);

const rules = {
    isRequired: v => !!v || 'This field is required',
    reference: v => {
        if (paymentForm.payment_method !== 'CC') {
            return !!v || 'This field is required';
        }
        return true;
    },
    amount: v => {
        const regex = /^\d+(\.\d{1,2})?$/;
        if (regex.test(v)) {
            return true;
        }
        return 'Amount must be a valid number';
    },
};



const collectionTypes = [
    { value: '', label: 'Select Collection Type' },
    { value: 'broker', label: 'Broker' },
    { value: 'insurer', label: 'Insurer' },
];

const paymentMethodOptions = computed(() => {
    return page.props.paymentMethods.map(method => ({
        value: method.code,
        label: method.name,
    }));
});

const insuranceProviderOptions = computed(() => {
    return page.props.insuranceProviders.map(method => ({
        value: method.id,
        label: method.text,
    }));
});

const personalPlanOptions = computed(() => {
    return page.props.personalPlans.map(method => ({
        value: method.id,
        label: method.text,
    }));
});



const addPaymentModal = () => {
    paymentForm.reset();
    paymentForm.payment_method = '';
    paymentForm.collection_type = '';
    paymentForm.amount = '';
    paymentForm.payment_reference = '';
    paymentForm.paymentCode = '';
    paymentForm.status = 'create';
    createPaymentModal.value = true;
};

const paymentForm = useForm({
    collection_type: '',
    captured_amount: '',
    payment_methods_code: '',
    insurance_provider_id: '',
    plan_id: '',
    reference: '',
    paymentCode: '',
    status: 'create',
});

const addPayment = isValid => {
    console.log('kejk')
    if (!isValid) return;

    let url = '/personal-quotes/' + page.props.quoteType.toLowerCase() + '/' + page.props.quote.id + '/payments';
    paymentForm
        .post(url, {
            preserveScroll: true,
            onSuccess: () => {
                notification.success({
                    title: 'Payment Added',
                    position: 'top',
                });
                createPaymentModal.value = false;
            },
            onError: () => {

                notification.error({
                    title: 'Payment Add Failed',
                    position: 'top',
                });
            },
        });

    // let data = {
    //     captured_amount: paymentMethodsForm.amount,
    //     code: paymentMethodsForm.payment_method,
    //     modelType: page.props.modelType,
    //     quote_id: page.props.quote.id,
    //     plan_id: null,
    //     insurance_provider_id: providerId.value,
    //     collection_type: paymentMethodsForm.collection_type,
    //     payment_methods: paymentMethodsForm.payment_method,
    //     reference: paymentMethodsForm.payment_reference,
    //     isInertia: true,
    // };

    if (paymentForm.status === 'edit') {
        let editData = {
            ...data,
            paymentCode: paymentForm.paymentCode,
        };
        paymentForm
            .transform(data => editData)
            .post('/payments/Health/update', {
                preserveScroll: true,
                onSuccess: () => {
                    notification.success({
                        title: 'Payment Updated',
                        position: 'top',
                    });
                    createPaymentModal.value = false;
                },
                onError: () => {
                    notification.error({
                        title: 'Payment Update Failed',
                        position: 'top',
                    });
                },
            });
        return;
    }

};



const getPlanName = computed(() => {
    const plan = page.props.quote.plan;
    return plan ? plan.text : 'Not Available';
});


const paymentTableHeaders = [
    { text: 'Payment ID', value: 'code', align: 'center' },
    { text: 'Payment Status', value: 'payment_status.code' },
    { text: 'Plan Name', value: 'personal_plan.text' },
    { text: 'Captured Amount', value: 'captured_amount', sortable: true },
    { text: 'Status Change Date', value: 'payment_status_log.created_at' },
    { text: 'Captured At', value: 'captured_at' },
    { text: 'Authorized At', value: 'authorized_at' },
    { text: 'Reference', value: 'reference' },
    { text: 'Actions', value: 'actions', sortable: false },
];


</script>

<template>
    <div class="p-4 rounded shadow mb-6 bg-white" v-if="isBetaUser">

        <div class="flex justify-between gap-4 items-center mb-4">
            <h3 class="font-semibold text-primary-800 text-lg">Payments</h3>
            <x-button
                size="sm"
                color="orange"
                @click="addPaymentModal"
            >
                Add Payment
            </x-button>
        </div>

        <DataTable
            table-class-name="tablefixed compact"
            :headers="paymentTableHeaders"
            :items="payments || []"
            border-cell
            hide-rows-per-page
            hide-footer>

            <template #item-code="{ code }">
                {{ code.toUpperCase() }}
            </template>




        </DataTable>


        <x-modal v-model="createPaymentModal" size="lg" show-close backdrop>
            <template #header>
            <span class="text-primary-800 font-semibold">
              {{
                    paymentForm.status == 'create'
                        ? 'New Payment'
                        : 'Update Payment'
                }}
            </span>
            </template>
            <x-form @submit="addPayment" :auto-focus="false">
                <div class="w-full grid md:grid-cols-2 gap-5">
                    <x-input
                        class="w-full"
                        :rules="[rules.isRequired]"
                        label="Capture Amount*"
                        v-model="paymentForm.captured_amount"
                        :error="paymentForm.errors.captured_amount"
                    />

                    <x-select
                        class="w-full"
                        v-model="paymentForm.collection_type"
                        :options="collectionTypes"
                        label="Collection Type*"
                        :rules="[rules.isRequired]"
                        :error="paymentForm.errors.collection_type"
                    >
                    </x-select>

                    <x-select
                        class="w-full md:col-span-2"
                        v-model="paymentForm.payment_methods_code"
                        :options="paymentMethodOptions"
                        label="Payment Method*"
                        :rules="[rules.isRequired]"
                        :error="paymentForm.errors.payment_methods_code"
                    >
                    </x-select>

                    <x-select
                        class="w-full"
                        v-model="paymentForm.insurance_provider_id"
                        :options="insuranceProviderOptions"
                        label="Insurance Provider*"
                        :rules="[rules.isRequired]"
                        :error="paymentForm.errors.insurance_provider_id"
                    >
                    </x-select>

                    <x-select
                        class="w-full"
                        v-model="paymentForm.plan_id"
                        :options="personalPlanOptions"
                        label="Plan*"
                        :rules="[rules.isRequired]"
                        :error="paymentForm.errors.plan_id"
                    >
                    </x-select>

                    <x-input
                        class="w-full md:col-span-2"
                        label="Payment Reference*"
                        :rules="[rules.isRequired, rules.reference]"
                        v-show="paymentForm.payment_method != 'CC'"
                        v-model="paymentForm.reference"
                        :error="paymentForm.errors.reference"
                    />

                    <div
                        class="w-full md:col-span-2 flex justify-end"
                        v-if="
                          paymentForm.status == 'create' ||
                          paymentForm.status == 'edit'
                        "
                    >
                        <x-button color="primary" type="submit">
                            {{ paymentForm.status == 'create' ? 'Create' : 'Update' }}
                            Payment
                        </x-button>
                    </div>
                </div>
            </x-form>
        </x-modal>
    </div>
</template>
