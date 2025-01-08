<script setup>
const notification = useNotifications('toast');

const props = defineProps({
  quote: Object,
  dropdownSource: Object,
  homePossessionTypeEnum: Object,
  model: String,
});
const page = usePage();
const hasContentOrBuilding = ref(true);

const quoteForm = useForm({
  modelType: '"Home"',
  model: props.model,
  first_name: props.quote?.first_name || null,
  last_name: props.quote?.last_name || null,
  email: props.quote?.email || null,
  mobile_no: props.quote?.mobile_no || null,
  company_name: props.quote?.home_company_name || null,
  company_address: props.quote?.home_company_address || null,
  premium: props.quote?.premium || null,
  policy_number: props.quote?.policy_number || null,
  iam_possesion_type_id: props.quote?.iam_possesion_type_id || null,
  ilivein_accommodation_type_id:
    props.quote?.ilivein_accommodation_type_id || null,
  address: props.quote?.address || null,
  has_contents: props.quote?.has_contents || null,
  has_building: props.quote?.has_building || null,
  has_personal_belongings: props.quote?.has_personal_belongings || null,
  contents_aed: props.quote?.contents_aed || null,
  building_aed: props.quote?.building_aed || null,
  personal_belongings_aed: props.quote?.personal_belongings_aed || null,
});
const isEdit = computed(() => {
  return route().current().includes('edit');
});
const { isRequired, isEmail, isMobileNo } = useRules();

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

function successResponse() {
  notification.success({
    title: 'Quote updated successfully',
    position: 'top',
  });
  if (isEdit.value) {
    router.get(route('home.show', page.props?.quote?.uuid));
  } else {
    quoteForm.reset();
  }
}
function onSubmit(isValid) {
  if (quoteForm.has_contents || quoteForm.has_building) {
    if (isValid) {
      if (isEdit.value) {
        quoteForm
          .transform(data => ({
            ...data,
            has_contents: data.has_contents ? true : false,
            has_personal_belongings: data.has_personal_belongings
              ? true
              : false,
            has_building: data.has_building ? true : false,
          }))
          .put(route('home.update', props.quote.uuid), {
            onError: errors => {
              console.log(errors);
            },
            onSuccess: () => {},
          });
      } else {
        quoteForm.post(route('home.store'), {
          onError: errors => {
            quoteForm.setError(errors);
          },
          onSuccess: () => {},
        });
      }
    }
  } else {
    hasContentOrBuilding.value = false;
  }
}
</script>

<template>
  <div>
    <Head :title="isEdit ? 'Edit Home' : 'Create Home'" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        {{ isEdit ? 'Edit' : 'Create' }} Home
      </h2>
      <div class="space-x-4">
        <Link
          v-if="$page.props?.quote?.uuid"
          :href="route('home.show', $page.props?.quote?.uuid)"
        >
          <x-button size="sm" tag="div"> Cancel </x-button>
        </Link>
        <Link :href="route('home.index')">
          <x-button size="sm" color="#ff5e00" tag="div"> Home List </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="FIRST NAME" required>
          <x-input
            v-model="quoteForm.first_name"
            type="text"
            :rules="[isRequired]"
            class="w-full"
            maxLength="20"
            :error="quoteForm.errors.first_name"
          />
        </x-field>
        <x-field label="LAST NAME" required>
          <x-input
            v-model="quoteForm.last_name"
            type="text"
            maxLength="50"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm.errors.last_name"
          />
        </x-field>
        <x-field label="EMAIL" required>
          <x-input
            v-model="quoteForm.email"
            type="email"
            :disabled="isEdit"
            :rules="[isRequired]"
            class="w-full"
            :error="quoteForm?.errors?.email"
          />
        </x-field>
        <x-field label="MOBILE NUMBER" required>
          <x-input
            v-model="quoteForm.mobile_no"
            type="tel"
            :disabled="isEdit"
            :rules="[isRequired, isMobileNo]"
            class="w-full"
            :error="quoteForm?.errors?.mobile_no"
          />
        </x-field>
        <x-field label="COMPANY NAME">
          <x-input
            v-model="quoteForm.company_name"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_name"
          />
        </x-field>
        <x-field label="COMPANY ADDRESS">
          <x-input
            v-model="quoteForm.company_address"
            type="text"
            class="w-full"
            :error="quoteForm?.errors?.company_address"
          />
        </x-field>
        <x-field label="PRICE">
          <x-input v-model="quoteForm.premium" type="text" class="w-full" />
        </x-field>
        <x-field label="POLICY NUMBER">
          <x-input
            v-model="quoteForm.policy_number"
            type="text"
            maxLength="100"
            class="w-full"
          />
        </x-field>
        <x-field label="I AM" required>
          <x-select
            v-model="quoteForm.iam_possesion_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.iam_possesion_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            @change="handleConditionalFields"
            class="w-full"
          />
        </x-field>
        <x-field label="I LIVE IN" required>
          <x-select
            v-model="quoteForm.ilivein_accommodation_type_id"
            :rules="[isRequired]"
            :options="
              dropdownSource.ilivein_accommodation_type_id.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
          />
        </x-field>
        <x-field label="ADDRESS" required>
          <x-textarea
            v-model="quoteForm.address"
            type="text"
            maxLength="2000"
            class="w-full"
          />
        </x-field>

        <div class="grid grid-cols-2 gap-2">
          <x-field label="HAS CONTENTS" required>
            <x-checkbox
              v-model="quoteForm.has_contents"
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>
          <x-field
            label="HAS PERSONAL BELONGINGS"
            v-if="quoteForm.has_contents"
          >
            <x-checkbox
              v-model="quoteForm.has_personal_belongings"
              label=""
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>
          <x-field
            label="HAS BUILDING"
            v-if="
              quoteForm.iam_possesion_type_id == homePossessionTypeEnum.LANDLORD
            "
          >
            <x-checkbox
              v-model="quoteForm.has_building"
              color="primary"
              @change="handleConditionalFields"
            />
          </x-field>

          <p v-if="!hasContentOrBuilding" class="text-sm text-red-500">
            Must be selected at least one of the above
          </p>
        </div>
        <x-field label="CONTENTS AED" v-if="quoteForm.has_contents" required>
          <x-input
            v-if="quoteForm.has_contents"
            v-model="quoteForm.contents_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field
          label="PERSONAL BELONGINGS AED"
          v-if="quoteForm.has_personal_belongings"
          required
        >
          <x-input
            v-model="quoteForm.personal_belongings_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="BUILDING AED" v-if="quoteForm.has_building" required>
          <x-input
            v-model="quoteForm.building_aed"
            type="number"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
      </div>
      <x-divider class="my-4" />
      <div class="flex justify-end gap-3 mb-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          :loading="quoteForm.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }}
        </x-button>
      </div>
    </x-form>
  </div>
</template>
