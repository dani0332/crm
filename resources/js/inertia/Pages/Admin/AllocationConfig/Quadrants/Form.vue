<script setup>
const props = defineProps({
  quadrant: Object,
  id: String,
  quad_users: Object,
  quad_tiers: Object,
});
const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const quadrantForm = useForm({
  id: props.quadrant?.id ?? null,
  name: props.quadrant?.name ?? null,
  quad_tiers: props.quadrant?.tiers.map(x => x.id) ?? [],
  quad_users: props.quadrant?.users.map(x => x.id) ?? [],
  is_active: props.quadrant?.is_active ? true : false,
});

const quad_users = computed(() => {
  let quad_users = Object.values(props.quad_users);
  return quad_users.map(users => {
    return {
      value: users.id,
      label: users.name,
    };
  });
});

const quad_tiers = computed(() => {
  let quad_tiers = Object.values(props.quad_tiers);
  return quad_tiers.map(tiers => {
    return {
      value: tiers.id,
      label: tiers.name,
    };
  });
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('quadrants.update', quadrantForm.id)
      : route('quadrants.store');

    quadrantForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          quadrantForm.setError(key, errors[key]);
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
      <Link :href="route('quadrants.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Quadrant List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-input
        required
        label="Quadrant Name"
        v-model="quadrantForm.name"
        class="w-full"
        :error="quadrantForm.errors.name"
        placeholder="Enter Quadrant Name"
      />
      <x-select
        v-model="quadrantForm.quad_tiers"
        label="Tiers Name"
        :options="quad_tiers"
        multiple
        filterable
        filterPlaceholder="Filter Tiers...."
        placeholder="Select Tiers"
        required
        :rules="[isRequired]"
        :error="quadrantForm.errors.quad_tiers"
      />
      <x-select
        v-model="quadrantForm.quad_users"
        label="Quad Users"
        :options="quad_users"
        multiple
        filterable
        filterPlaceholder="Filter Quad Users...."
        placeholder="Select Quad Users"
        required
        :rules="[isRequired]"
        :error="quadrantForm.errors.quad_users"
        truncate
        class="w-full"
      >
        <template #content-footer>
          <ui-select-actions
            @select-all="
              quadrantForm.quad_users = quad_users.map(item => item.value)
            "
            @clear="quadrantForm.quad_users = []"
          />
        </template>
      </x-select>

      <x-field label="Is Active">
        <x-select
          v-model="quadrantForm.is_active"
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
