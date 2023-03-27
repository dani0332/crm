<script setup>
import { ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';

const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  homePossessionTypeEnum: Object,
  model: String,
});

const hasContentOrBuilding = ref(true);

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: props.quote.first_name,
  last_name: props.quote.last_name,
  email: props.quote.email,
  mobile_no: props.quote.mobile_no,
  premium: props.quote.premium,
  policy_number: props.quote.policy_number,
  iam_possesion_type_id: props.quote.iam_possesion_type_id,
  ilivein_accommodation_type_id: props.quote.ilivein_accommodation_type_id,
  address: props.quote.address,
  has_contents: props.quote.has_contents,
  has_building: props.quote.has_building,
  has_personal_belongings: props.quote.has_personal_belongings,
  contents_aed: props.quote.contents_aed,
  building_aed: props.quote.building_aed,
  personal_belongings_aed: props.quote.personal_belongings_aed,
});

const rules = {
  isEmail: v =>
    /^\w+([.-]?\w+)*@\w+([.-]?\w+)*(\.\w{2,3})+$/.test(v) ||
    'E-mail must be valid',
  isRequired: v => !!v || 'This field is required',
};

const handleConditionalFields = () => {
  if (
    quoteForm.iam_possesion_type_id !== props.homePossessionTypeEnum.LANDLORD
  ) {
    quoteForm.has_building = false;
  }
  if (!Boolean(quoteForm.has_building)) {
    quoteForm.building_aed = null;
  }
  if (!Boolean(quoteForm.has_contents)) {
    quoteForm.contents_aed = null;
    quoteForm.has_personal_belongings = false;
  }
  if (!Boolean(quoteForm.has_personal_belongings)) {
    quoteForm.personal_belongings_aed = null;
  }
  if (quoteForm.has_contents || quoteForm.has_building) {
    hasContentOrBuilding.value = true;
  }
};

function onSubmit(isValid) {
  if (quoteForm.has_contents || quoteForm.has_building) {
    if (isValid) {
      quoteForm
        .transform(data => ({
          ...data,
          has_contents: data.has_contents ? true : false,
          has_personal_belongings: data.has_personal_belongings ? true : false,
          has_building: data.has_building ? true : false,
        }))
        .put(`/quotes/home/${props.quote.uuid}`, {
          onError: errors => {
            console.log(errors);
          },
          onSuccess: () => {
            notification.success({
              title: 'Quote updated successfully',
              position: 'top',
            });
            router.get(`/quotes/home/${props.quote.uuid}`);
          },
        });
    }
  } else {
    hasContentOrBuilding.value = false;
  }
}
</script>

<template>
  <div>
    <Head title="Edit Home" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Edit Home</h2>
      <div class="space-x-4">
        <Link :href="`/quotes/home/${props.quote.uuid}`">
          <x-button size="sm" tag="div"> View </x-button>
        </Link>
        <Link href="/quotes/home">
          <x-button size="sm" color="#ff5e00" tag="div"> Home List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-input
          v-model="quoteForm.first_name"
          type="text"
          label="FIRST NAME"
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.last_name"
          type="text"
          label="LAST NAME"
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.email"
          type="email"
          label="EMAIL"
          :disabled="true"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          :disabled="true"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.premium"
          type="text"
          label="PREMIUM"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.policy_number"
          type="text"
          label="POLICY NUMBER"
          class="w-full"
        />

        <x-select
          v-model="quoteForm.iam_possesion_type_id"
          label="I AM"
          :rules="[rules.isRequired]"
          :options="
            dropdownSource.iam_possesion_type_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          @change="handleConditionalFields"
          class="w-full"
        />

        <x-select
          v-model="quoteForm.ilivein_accommodation_type_id"
          label="I LIVE IN"
          :rules="[rules.isRequired]"
          :options="
            dropdownSource.ilivein_accommodation_type_id.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />

        <x-textarea
          v-model="quoteForm.address"
          type="text"
          label="ADDRESS"
          class="w-full"
        />

        <div class="grid grid-cols-2 gap-2">
          <x-checkbox
            v-model="quoteForm.has_contents"
            label="HAS CONTENTS"
            color="primary"
            @change="handleConditionalFields"
          />

          <x-checkbox
            v-if="quoteForm.has_contents"
            v-model="quoteForm.has_personal_belongings"
            label="HAS PERSONAL BELONGINGS"
            color="primary"
            @change="handleConditionalFields"
          />

          <x-checkbox
            v-if="
              quoteForm.iam_possesion_type_id == homePossessionTypeEnum.LANDLORD
            "
            v-model="quoteForm.has_building"
            label="HAS BUILDING"
            color="primary"
            @change="handleConditionalFields"
          />

          <p v-if="!hasContentOrBuilding" class="text-sm text-red-500">
            Must be selected at least one of the above
          </p>
        </div>

        <x-input
          v-if="quoteForm.has_contents"
          v-model="quoteForm.contents_aed"
          label="CONTENTS AED"
          type="number"
          class="w-full"
          :rules="[rules.isRequired]"
        />

        <x-input
          v-if="quoteForm.has_personal_belongings"
          v-model="quoteForm.personal_belongings_aed"
          label="PERSONAL BELONGINGS AED"
          type="number"
          class="w-full"
          :rules="[rules.isRequired]"
        />

        <x-input
          v-if="quoteForm.has_building"
          v-model="quoteForm.building_aed"
          label="BUILDING AED"
          type="number"
          class="w-full"
          :rules="[rules.isRequired]"
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
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
