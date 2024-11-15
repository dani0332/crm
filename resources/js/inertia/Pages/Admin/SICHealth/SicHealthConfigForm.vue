<script setup>
const props = defineProps({
  sicConfigurable: Object,
  relations: Object,
  nationalities: Object,
  memberCategories: Object,
  healthTypes: Object,
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();
let errors = reactive({});

const sicConfigurableForm = useForm({
  id: props.sicConfigurable?.id ?? null,
  min_age: props.sicConfigurable?.min_age ?? 0,
  price_starting_from: props.sicConfigurable?.price_starting_from ?? 0,
  max_age: props.sicConfigurable?.max_age ?? 0,
  plan_types: [],
  is_type: props.sicConfigurable?.is_type ?? false,
  nationalities: [],
  member_categories: [],
  is_nationality: props.sicConfigurable?.is_nationality ?? false,
  is_member_category: props.sicConfigurable?.is_member_category ?? false,
  is_age: props.sicConfigurable?.is_age ?? false,
  is_price_starting_from:
    props.sicConfigurable?.is_price_starting_from ?? false,
});

onMounted(() => {
  if (props.relations?.health_plan_types?.length > 0) {
    sicConfigurableForm.plan_types =
      props.relations?.health_plan_types.map(item => item.id) ?? [];
  }
  if (props.relations?.nationalities?.length > 0) {
    sicConfigurableForm.nationalities =
      props.relations?.nationalities.map(item => item.id) ?? [];
  }
  if (props.relations?.member_categories?.length > 0) {
    sicConfigurableForm.member_categories =
      props.relations?.member_categories.map(item => item.id) ?? [];
  }
});
function onSubmit(isValid) {
  if (isValid) {
    let method = 'post';
    let url = route('admin.sic-health-config.store');
    sicConfigurableForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          sicConfigurableForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  } else {
    console.log('error');
  }
}

const nationalitiesOptions = computed(() => {
  return page.props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const memberCategoriesOptions = computed(() => {
  return page.props.memberCategories.map(cat => ({
    value: cat.id,
    label: cat.text,
  }));
});

const healthTypesOptions = computed(() => {
  return page.props.healthTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const ageRangeValid = computed(() => {
  const minAge = parseInt(sicConfigurableForm.min_age);
  const maxAge = parseInt(sicConfigurableForm.max_age);
  if (minAge > maxAge) {
    errors.min_age = 'Min Age must be less than or equal to Max Age';
    return false;
  } else {
    errors.min_age = null;
  }
  return true;
});
</script>
<template>
  <Head>
    <title>SIC Health Configuration</title>
  </Head>
  <div class="card p-4 shadow-md rounded-lg mb-4">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">SIC Health Configuration</h2>
      <div>
        <Link @click="onSubmit">
          <x-button size="sm" color="#1d83bc" tag="div"> Update </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <div class="col-span-1 sm:col-span-1">
          <x-checkbox v-model="sicConfigurableForm.is_age" label="Age" />
          <div
            class="grid sm:grid-cols-2 gap-4"
            v-if="sicConfigurableForm.is_age"
          >
            <x-field label="Min Age" required>
              <x-input
                v-model="sicConfigurableForm.min_age"
                placeholder="Min Age"
                class="w-full"
                :rules="[isRequired, ageRangeValid]"
                :error="errors.min_age"
              />
            </x-field>
            <x-field label="Max Age" required>
              <x-input
                v-model="sicConfigurableForm.max_age"
                class="w-full"
                placeholder="Max Age"
                :rules="[isRequired]"
                :error="errors.max_age"
              />
            </x-field>
          </div>
        </div>
        <div class="col-span-1 sm:col-span-1">
          <x-checkbox v-model="sicConfigurableForm.is_type" label="Plan Type" />
          <div
            class="grid sm:grid-cols-1 gap-4"
            v-if="sicConfigurableForm.is_type"
          >
            <x-field label="Types">
              <ComboBox
                v-model="sicConfigurableForm.plan_types"
                :options="healthTypesOptions"
                :multiple="true"
                :autocomplete="true"
                :error="sicConfigurableForm?.errors.plan_types"
              />
            </x-field>
          </div>
        </div>
      </div>

      <div class="grid sm:grid-cols-2 gap-4 mt-4">
        <!-- Nationality -->
        <div class="col-span-1 sm:col-span-1">
          <x-checkbox
            v-model="sicConfigurableForm.is_nationality"
            label="Nationality"
          />
          <div
            class="grid sm:grid-cols-1 gap-4"
            v-if="sicConfigurableForm.is_nationality"
          >
            <x-field label="Nationalities">
              <ComboBox
                v-model="sicConfigurableForm.nationalities"
                :options="nationalitiesOptions"
                :multiple="true"
                :autocomplete="true"
                :error="sicConfigurableForm?.errors.nationalities"
              />
            </x-field>
          </div>
        </div>
        <div class="col-span-1 sm:col-span-1">
          <x-checkbox
            v-model="sicConfigurableForm.is_member_category"
            label="Member Category "
          />
          <div
            class="grid sm:grid-cols-1 gap-4"
            v-if="sicConfigurableForm.is_member_category"
          >
            <x-field label="Member Categories">
              <ComboBox
                v-model="sicConfigurableForm.member_categories"
                :options="memberCategoriesOptions"
                :multiple="true"
                :autocomplete="true"
                :error="sicConfigurableForm?.errors.member_categories"
              />
            </x-field>
          </div>
        </div>
        <div class="col-span-1 sm:col-span-1">
          <x-checkbox
            v-model="sicConfigurableForm.is_price_starting_from"
            label="Price Starting From"
          />
          <div
            class="grid sm:grid-cols-1 gap-4"
            v-if="sicConfigurableForm.is_price_starting_from"
          >
            <x-field label="Price Starting From" required>
              <x-input
                v-model="sicConfigurableForm.price_starting_from"
                required
                placeholder="Price Starting From"
                class="w-full"
                :rules="[isRequired]"
                :error="errors.price_starting_from"
              />
            </x-field>
          </div>
        </div>
      </div>
    </x-form>
  </div>
  <AuditLogs
    :type="'App\\Models\\SICConfig'"
    :quoteType="'SICConfig'"
    :id="$page.props.sicConfigurable?.id"
    :expanded="sectionExpanded"
  />
</template>
