<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
  dropdownSource: Object,
  model: String,
  genderOptions: Object,
  homePossessionTypeEnum: Object,
});

const hasContentOrBuilding = ref(true);

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: '',
  last_name: '',
  email: '',
  mobile_no: '',
  premium: null,
  policy_number: null,
  iam_possesion_type_id: null,
  ilivein_accommodation_type_id: null,
  address: '',
  has_contents: false,
  has_building: false,
  has_personal_belongings: false,
  contents_aed: null,
  building_aed: null,
  personal_belongings_aed: null,
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

const isEmptyField = ref(false);
function onSubmit(isValid) {
  if (quoteForm.has_contents || quoteForm.has_building) {
    if (isValid) {
      quoteForm.post(`/quotes/save`, {
        onError: errors => {
          console.log(errors);
        },
        onSuccess: () => {
          router.get(`/quotes/home/`);
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
    <Head title="Create Home" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Create Home</h2>
      <div>
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
          :rules="[rules.isRequired]"
          class="w-full"
        />

        <x-input
          v-model="quoteForm.mobile_no"
          type="tel"
          label="MOBILE NUMBER"
          :rules="[rules.isRequired]"
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
          :rules="[rules.isRequired]"
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
            v-model="quoteForm.has_personal_belongings"
            v-if="quoteForm.has_contents"
            label="HAS PERSONAL BELONGINGS"
            color="primary"
            @change="handleConditionalFields"
          />

          <x-checkbox
            v-model="quoteForm.has_building"
            v-if="
              quoteForm.iam_possesion_type_id == homePossessionTypeEnum.LANDLORD
            "
            label="HAS BUILDING"
            color="primary"
            @change="handleConditionalFields"
          />

          <p v-if="!hasContentOrBuilding" class="text-sm text-red-500">
            Must be selected at least one of the above
          </p>
        </div>

        <x-input
          v-model="quoteForm.contents_aed"
          v-if="quoteForm.has_contents"
          label="CONTENTS AED"
          type="number"
          class="w-full"
          :rules="[rules.isRequired]"
        />

        <x-input
          v-model="quoteForm.personal_belongings_aed"
          v-if="quoteForm.has_personal_belongings"
          label="PERSONAL BELONGINGS AED"
          type="number"
          class="w-full"
          :rules="[rules.isRequired]"
        />

        <x-input
          v-model="quoteForm.building_aed"
          v-if="quoteForm.has_building"
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
          Create
        </x-button>
      </div>
    </x-form>
  </div>
</template>
