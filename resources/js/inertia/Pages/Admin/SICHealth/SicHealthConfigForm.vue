<script setup>
const props = defineProps({
    sicHealthConfig: Object,
    nationalities: Object,
    memberCategories: Object
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();

const SICHealthConfigForm = useForm({
  id : props.sicHealthConfig?.id ?? null,
  min_age: props.sicHealthConfig?.min_age ?? 0,
  max_age: props.sicHealthConfig?.max_age ?? 0,
  plan_types: props.sicHealthConfig?.plan_types ?? [],
  is_type: props.sicHealthConfig?.is_type ?? false,
  nationalities: props.sicHealthConfig?.nationalities ?? [],
  member_categories: props.sicHealthConfig?.member_categories ?? [],
  is_nationality: props.sicHealthConfig?.is_nationality ?? false,
  is_member_category: props.sicHealthConfig?.is_member_category ?? false,
  is_age: props.sicHealthConfig?.is_age ?? false,

});
const subTeamOptions = [
  { value: 'Best', label: 'Best' },
  { value: 'Good', label: 'Good' },
  { value: 'Entry-Level', label: 'Entry-Level'},
];
function onSubmit(isValid) {
    console.log("test");
  if (isValid) {
    let method = 'post';
    let url = route('admin.sic-health-config-store')
    SICHealthConfigForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
            SICHealthConfigForm.setError(key, errors[key]);
        });
        return false;
      },
    });
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
</script>
<template>
    <Head :title=" 'SIC Health Configuration'" />
    <div class="card p-4 shadow-md rounded-lg">
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">
        SIC Health Configuration
      </h2>
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
              <x-checkbox
                v-model="SICHealthConfigForm.is_age"
                label="Age"
              />
              <div class="grid sm:grid-cols-2 gap-4">
                <x-field label="Min Age" required>
                    <x-input
                      v-model="SICHealthConfigForm.min_age"
                      placeholder="Min Age"
                      class="w-full"
                      :rules="[isRequired]"
                      :error="$page.props.errors.min_age"
                    />
                  </x-field>
                  <x-field label="Max Age" required>
                    <x-input
                      v-model="SICHealthConfigForm.max_age"
                      class="w-full"
                      placeholder="Max Age"
                      :rules="[isRequired]"
                      :error="$page.props.errors.max_age"
                    />
                  </x-field>
              </div>
            </div>
            <div class="col-span-1 sm:col-span-1">
              <x-checkbox
                v-model="SICHealthConfigForm.is_type"
                label="Plan Type"
              />
              <div class="grid sm:grid-cols-1 gap-4">
                <x-field label="Types">
                    <ComboBox
                        v-model="SICHealthConfigForm.plan_types"
                        :options="subTeamOptions"
                    :multiple="true"
                        :autocomplete="true"
                        :error="SICHealthConfigForm?.errors.plan_types"
                        />
                  </x-field>
              </div>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mt-4">
                <!-- Nationality -->
            <div class="col-span-1 sm:col-span-1">
              <x-checkbox
                v-model="SICHealthConfigForm.is_nationality"
                label="Nationality"
              />
              <div class="grid sm:grid-cols-1 gap-4">
                <x-field label="Nationalities">
                <ComboBox
                        v-model="SICHealthConfigForm.nationalities"
                        :options="nationalitiesOptions"
                        :multiple="true"
                        :autocomplete="true"
                        :error="SICHealthConfigForm?.errors.nationalities"
                        />
                  </x-field>
              </div>
            </div>
            <div class="col-span-1 sm:col-span-1">
              <x-checkbox
                v-model="SICHealthConfigForm.is_member_category"
                label="Member Category "
              />
              <div class="grid sm:grid-cols-1 gap-4">
                <x-field label="Member Categories">
                    <ComboBox
                        v-model="SICHealthConfigForm.member_categories"
                        :options="memberCategoriesOptions"
                        :multiple="true"
                        :autocomplete="true"
                        :error="SICHealthConfigForm?.errors.member_categories"
                        />
                  </x-field>
              </div>
            </div>
        </div>
    </x-form>
</div>
<AuditLogs
:type="'App\\Models\\SICHealthConfig'"
:id="$page.props.sicHealthConfig?.id"
:expanded="sectionExpanded"
/>

</template>
