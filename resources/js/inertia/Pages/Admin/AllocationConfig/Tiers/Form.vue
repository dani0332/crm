<script setup>
const props = defineProps({
  tier: Object,
  id: String,
  usersList: Object,
});
const { isRequired, isNumber } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const tierForm = useForm({
  id: props.tier?.id ?? null,
  name: props.tier?.name ?? null,
  min_price: props.tier?.min_price ?? null,
  max_price: props.tier?.max_price ?? null,
  cost_per_lead:
    props.tier?.cost_per_lead == 0
      ? props.tier?.cost_per_lead.toString()
      : props.tier?.cost_per_lead,
  can_handle_ecommerce: props.tier?.can_handle_ecommerce ? true : false,
  can_handle_null_value: props.tier?.can_handle_null_value ? true : false,
  is_tpl_renewals: props.tier?.is_tpl_renewals ? true : false,
  is_active: props.tier?.is_active ? true : false,
  tier_user: props.tier?.users.map(x => x.id) ?? null,
  can_handle_tpl: props.tier?.can_handle_tpl ? true : false,
});

const tierUsers = computed(() => {
  let tier_users = Object.values(props.usersList);
  return tier_users.map(user => {
    return {
      value: user.id,
      label: user.name,
    };
  });
});

const selectedUsers = computed(() => {
  if (!props.tier || !props.tier.users) {
    return [];
  }

  return props.tier.users.map(user => user.id);
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('tiers.update', tierForm.id)
      : route('tiers.store');

    tierForm.processing = true;
    tierForm.cost_per_lead = Number(tierForm.cost_per_lead);
    tierForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          tierForm.setError(key, errors[key]);
        });
        tierForm.processing = false;
        return false;
      },
      onSuccess: () => {
        tierForm.processing = false;
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
      <Link :href="route('tiers.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Tier List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Tiers Users">
        <ComboBox
          v-model="tierForm.tier_user"
          :multiple="true"
          :options="tierUsers"
          :selected="selectedUsers"
          :error="tierForm.errors.tier_user"
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
          :error="tierForm.errors.can_handle_ecommerce"
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
          :error="tierForm.errors.can_handle_null_value"
        />
      </x-field>

      <x-field label="IS TPL?">
        <x-select
          v-model="tierForm.can_handle_tpl"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
          :error="tierForm.errors.can_handle_tpl"
        />
      </x-field>

      <x-field label="Renewal (TPL_RENEWALS)?">
        <x-select
          v-model="tierForm.is_tpl_renewals"
          class="w-full"
          :options="[
            { value: true, label: 'Yes' },
            { value: false, label: 'No' },
          ]"
          :error="tierForm.errors.is_tpl_renewals"
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
          :error="tierForm.errors.is_active"
        />
      </x-field>
      <x-field label="Tier Name" required>
        <x-input
          v-model="tierForm.name"
          class="w-full"
          :error="tierForm.errors.name"
        />
      </x-field>
      <x-field label="Min Price">
        <x-input
          v-model="tierForm.min_price"
          :rule="[isNumber]"
          type="number"
          class="w-full"
          :error="tierForm.errors.min_price"
        />
      </x-field>
      <x-field label="Max Price">
        <x-input
          v-model="tierForm.max_price"
          class="w-full"
          :rule="[isNumber]"
          type="number"
          :error="tierForm.errors.max_price"
        />
      </x-field>
      <x-field label="Cost Per Lead">
        <x-input
          v-model="tierForm.cost_per_lead"
          class="w-full"
          type="number"
          :error="tierForm.errors.cost_per_lead"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button
        size="md"
        color="emerald"
        type="submit"
        :loading="tierForm.processing"
      >
        {{ isEdit ? 'Update' : 'Create' }}
      </x-button>
    </div>
  </x-form>
</template>
