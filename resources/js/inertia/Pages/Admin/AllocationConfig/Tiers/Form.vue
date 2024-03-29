<script setup>
const props = defineProps({
  tier: Object,
  id: String,
  model: Object,
  dropdownSource: Object,
  customTitles: Object,
});
const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const tierForm = useForm({
  name: props.tier?.name ?? null,
  min_price: props.tier?.min_price ?? null,
  max_price: props.tier?.max_price ?? null,
  cost_per_lead: props.tier?.cost_per_lead ?? null,
  can_handle_ecommerce: props.tier?.can_handle_ecommerce ?? null,
  can_handle_null_value: props.tier?.can_handle_null_value ?? null,
  is_tpl_renewals: props.tier?.is_tpl_renewals ?? null,
  is_active: props.tier?.is_active ? true : false,
  tier_user: props.tier?.tier_user ?? null,
});

const tierUsers = computed(() => {
  let { tier_users } = { ...props.dropdownSource };
  return tier_users.map(users => {
    return {
      value: users.id,
      label: users.name,
    };
  });
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('tier.update', tierForm.id)
      : route('tier.store');

    tierForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          tierForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Tier' : 'Create Tier'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Tiers
    </h2>
    <div>
      <Link :href="route('tier.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Tier List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <input type="hidden" name="model" :value="model.properties" />
      <input type="hidden" name="modelSkipProperties" value="" />
      <input type="hidden" name="modelType" value="" />
      <input type="hidden" name="addon_id" value="" />
      <x-field label="Tier Name" required>
        <x-input v-model="tierForm.name" class="w-full" />
      </x-field>
      <x-field label="Min Price">
        <x-input v-model="tierForm.min_price" class="w-full" />
      </x-field>
      <x-field label="Max Price">
        <x-input v-model="tierForm.max_price" class="w-full" />
      </x-field>
      <x-field label="Cost Per Lead">
        <x-input v-model="tierForm.cost_per_lead" class="w-full" />
      </x-field>
      <x-field label="Tiers Users">
        <ComboBox
          v-model="tierForm.tier_user"
          :single="true"
          :options="tierUsers"
        />
      </x-field>
      <x-field label="Is Ecommerce?">
        <x-select
          v-model="tierForm.can_handle_ecommerce"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />
      </x-field>
      <x-field label="Null Value?">
        <x-select
          v-model="tierForm.can_handle_null_value"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />
      </x-field>
      <x-field label="Renewal">
        <x-select
          v-model="tierForm.is_tpl_renewals"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />
      </x-field>
      <x-field label="Is Active?">
        <x-select
          v-model="tierForm.is_active"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>