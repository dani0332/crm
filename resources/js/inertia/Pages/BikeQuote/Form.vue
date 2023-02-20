<script setup>

import { computed, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import ComboBox from '@/inertia/Components/ComboBox.vue';
import {useNotifications} from "@indielayer/ui";


const notification = useNotifications('toast');

const props = defineProps({
    genderOptions: Object,
    nationalities: Object,
    uaeLicenses: Object,
    insuranceProviders: Object,
    yearOfManufacture: Object,
    dropdownSource: Object,
    model: String,
    quote: {type: Object, default: null},
});


const quoteForm = useForm({
    model: props.model,
    first_name: props.quote?.first_name || '',
    last_name: props.quote?.last_name || '',
    email: props.quote?.email || '',
    mobile_no: props.quote?.mobile_no || '',
    dob: props.quote?.dob || '',
    nationality_id: props.quote?.nationality_id || null,
    uae_license_held_for_id: props.quote?.bike_quote?.uae_license_held_for_id || null,
    bike_company_to_insure: props.quote?.bike_quote?.bike_company_to_insure || null,
    asset_value: props.quote?.asset_value || null,
    currently_insured_with_id: props.quote?.currently_insured_with_id || null,
    year_of_manufacture: props.quote?.bike_quote?.year_of_manufacture?.id || null,
});

const rules = {
    isEmail: v =>
        /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
        'E-mail must be valid',
    isRequired: v => !!v || 'This field is required',
};

const isEmptyField = ref(false);
function onSubmit(isValid) {
    if (quoteForm.nationality_id == null) {
        isEmptyField.value = true;
    } else {
        isEmptyField.value = false;
    }

    if (isValid) {

        let method = 'post';
        let url = `/personal-quotes/bike/`;
        if(props.quote)
        {
            method = "put";
            url = url + props.quote.uuid
        }

        quoteForm.submit(method, url, {
            onError: errors => {
                console.log(quoteForm.setError(errors));
            },
            onSuccess: () => {

                notification.success({
                    title: 'Quote saved successfully',
                    position: 'top'
                });

                setTimeout(function(){
                    router.get(`/personal-quotes/bike`);
                }, 500);
            },
        });
    }
}

</script>

<template>
    <div>
        <Head title="Bike Quote" />
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold">Bike Quote <span v-if="quote">{{quote?.uuid}}</span></h2>
            <div>
                <Link href="/personal-quotes/bike">
                    <x-button size="sm" color="#ff5e00"> Bike Quotes List </x-button>
                </Link>
            </div>
        </div>
        <x-divider class="my-4" />
        <x-form @submit="onSubmit" :auto-focus="false">

            <x-alert color="error" class="mb-5" v-if="quoteForm.errors.error" >{{quoteForm?.errors?.error}}</x-alert>

            <div class="grid sm:grid-cols-2 gap-4">

                <x-input
                    v-model="quoteForm.first_name"
                    type="text"
                    label="FIRST NAME"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.first_name"
                />

                <x-input
                    v-model="quoteForm.last_name"
                    type="text"
                    label="LAST NAME"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.last_name"
                />

                <x-input
                    v-model="quoteForm.email"
                    type="email"
                    label="EMAIL"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.email"
                />

                <x-input
                    v-model="quoteForm.mobile_no"
                    type="tel"
                    label="MOBILE NUMBER"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.mobile_no"
                />

                <x-input
                    v-model="quoteForm.dob"
                    type="date"
                    label="DATE OF BIRTH"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.dob"
                />

                <ComboBox
                    v-model="quoteForm.nationality_id"
                    label="NATIONALITY"
                    :single="true"
                    :options="
            nationalities.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
                    :hasError="isEmptyField"
                    :error="quoteForm.errors.nationality_id"
                />

                <x-select
                    v-model="quoteForm.uae_license_held_for_id"
                    label="UAE licence held for"
                    :rules="[rules.isRequired]"
                    :options="
                        uaeLicenses.map(item => ({
                          value: item.id,
                          label: item.text,
                        }))
                    "
                    class="w-full"
                    :error="quoteForm.errors.uae_license_held_for_id"
                />

                <x-input
                    v-model="quoteForm.bike_company_to_insure"
                    type="text"
                    label="Bike(s) to insure"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.bike_company_to_insure"
                />

                <x-input
                    v-model="quoteForm.asset_value"
                    type="number"
                    label="BiKe Value"
                    :rules="[rules.isRequired]"
                    class="w-full"
                    :error="quoteForm.errors.asset_value"
                />

                <x-select
                    v-model="quoteForm.year_of_manufacture"
                    label="Year Of Manufacture"
                    :rules="[rules.isRequired]"
                    :options="
                        yearOfManufacture.map(item => ({
                          value: item.text,
                          label: item.text,
                        }))
                    "
                    class="w-full"
                    :error="quoteForm.errors.year_of_manufacture"
                />

                <x-select
                    v-model="quoteForm.currently_insured_with_id"
                    label="Currently Insured With"
                    :rules="[rules.isRequired]"
                    :options="
                        insuranceProviders.map(item => ({
                          value: item.id,
                          label: item.text,
                        }))
                    "
                    class="w-full"
                    :error="quoteForm.errors.currently_insured_with_id"
                />

            </div>

            <x-divider class="my-4" />
            <div class="flex justify-end gap-3 mb-4">
                <x-button
                    size="md"
                    color="emerald"
                    type="submit"
                    :loading="quoteForm.processing"
                >
                    Save
                </x-button>
            </div>
        </x-form>
    </div>
</template>
