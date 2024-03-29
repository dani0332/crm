<script setup>
const props = defineProps({
  qurdant: Object,
  id: String,
  dropdownSource: Object,
});
const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const qurdantForm = useForm({
  id: props.qurdant?.id ?? null,
  name: props.qurdant?.name ?? null,
  tier_names: props.qurdant?.tier_names ?? [],
  tier_users: props.qurdant?.tier_users ?? [],
  is_active: false,
});

const quad_users = computed(() => {
  let { quad_users } = { ...props.dropdownSource };
  return quad_users.map(users => {
    return {
      value: users.id,
      label: users.name,
    };
  });
});

const quad_tiers = computed(() => {
  let { quad_tiers } = { ...props.dropdownSource };
  return quad_tiers.map(users => {
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
      ? route('quadrant.update', qurdantForm.id)
      : route('quadrant.store');

    qurdantForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          qurdantForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Quadrant' : 'Create Quadrant'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Quadrant
    </h2>
    <div>
      <Link :href="route('quadrant.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Quadrant List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Quadrant Name" required>
        <x-input v-model="qurdantForm.name" class="w-full" />
      </x-field>
      <x-field label="Tiers Name">
        <ComboBox
          v-model="qurdantForm.tier_names"
          :single="true"
          :options="quad_tiers"
        />
      </x-field>
      <x-field label="Tiers Users">
        <ComboBox
          v-model="qurdantForm.tier_users"
          :single="true"
          :options="quad_users"
        />
      </x-field>
      <x-field label="Is Active">
        <x-select
          v-model="qurdantForm.is_active"
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