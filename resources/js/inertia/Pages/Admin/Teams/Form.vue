<script setup>
const props = defineProps({
  products: Array,
  team: Object,
});

const { isRequired } = useRules();
const isError = ref(false);
const notification = useToast();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const teamForm = useForm({
  id: props.team?.id ?? null,
  name: props.team?.name ?? null,
  type: props.team?.type ?? 1,
  slabs_count: props.team?.slabs_count ?? null,
  is_active:
    props.team?.is_active === 'True'
      ? true
      : props.team?.is_active === 'False'
        ? false
        : true,

  parent_team_id: props.team?.parent_team_id ?? null,
});

const validParentId = computed(() => {
  return (teamForm.parent_team_id == null && isError.value) ?? false;
});

const computedParent = computed(() => {
  if (teamForm.type)
    return props.products
      .filter(x => {
        if (x.type == 'Product' && teamForm.type == 2) return x;
        if (x.type == 'Team' && teamForm.type == 3) return x;
      })
      .map(item => ({
        value: item.id,
        label: item.name,
      }));
  else return [];
});

watch(
  () => teamForm.type,
  () => {
    if (teamForm.type == 1) {
      teamForm.parent_team_id = null;
    }
  },
);

function onSubmit(isValid) {
  if (teamForm.parent_team_id == 0) {
    isError.value = true;
  } else {
    isError.value = false;
  }
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('team.update', teamForm.id)
      : route('team.store');

    teamForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          teamForm.setError(key, errors[key]);
        });
      },
    });
  }
}

const setInitialState = () => {
  if (props.team)
    if (props.team?.type == 'Product') teamForm.type = 1;
    else if (props.team.type == 'Team') teamForm.type = 2;
    else if (props.team.type == 'Subteam') teamForm.type = 3;
};
onMounted(() => setInitialState());
</script>
<template>
  <Head :title="isEdit ? 'Edit Team' : 'Create Team'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">{{ isEdit ? 'Edit' : 'Create' }} Team</h2>
    <div>
      <Link :href="route('team.index')">
        <x-button size="sm" color="#1d83bc" tag="div"> Teams List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="NAME" required>
        <x-input
          v-model="teamForm.name"
          :rules="[isRequired]"
          class="w-full"
          :error="$page.props.errors.name"
        />
      </x-field>
      <x-field label="RECORD TYPE" required>
        <x-select
          v-model="teamForm.type"
          :rules="[isRequired]"
          class="w-full"
          :options="[
            { value: 1, label: 'Product' },
            { value: 2, label: 'Team' },
            { value: 3, label: 'SubTeam' },
          ]"
          :error="$page.props.errors.type"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="PARENT">
        <ComboBox
          :single="true"
          :rules="teamForm.type != 1 ? [isRequired] : []"
          v-model="teamForm.parent_team_id"
          :options="computedParent"
          :disabled="teamForm.type == 1"
          :hasError="validParentId"
        />
      </x-field>
      <x-field label="SLABS COUNT" required>
        <x-input
          v-model="teamForm.slabs_count"
          :rules="[isRequired]"
          class="w-full"
          type="number"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-1 gap-4">
      <x-field label="ACTIVE">
        <x-select
          v-model="teamForm.is_active"
          :options="[
            {
              value: true,
              label: 'Yes',
            },
            {
              value: false,
              label: 'No',
            },
          ]"
          class="w-full"
        ></x-select>
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
