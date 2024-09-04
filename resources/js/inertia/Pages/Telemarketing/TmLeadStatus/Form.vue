<script setup>
const props = defineProps({
  tmleadstatus: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const { isRequired, isEmail, isMobileNo } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const leadForm = useForm({
  id: props.tmleadstatus?.id ?? null,
  code: props.tmleadstatus?.code ?? null,
  text: props.tmleadstatus?.text ?? null,
  text_ar: props.tmleadstatus?.text_ar ?? null,
  sort_order: props.tmleadstatus?.sort_order ?? null,
  is_active: props.tmleadstatus?.is_active ? 'on' : 'off',
});

function onSubmit(isValid) {
  if (isValid) {
    leadForm.clearErrors();
    let method = isEdit.value ? 'put' : 'post';
    const url = isEdit.value
      ? route('tmleadstatus.update', leadForm.id)
      : route('tmleadstatus.store');

    leadForm.submit(method, url, {
      onError: errors => {
        leadForm.setError(errors);
      },
      onSuccess: response => {
        leadForm.reset();
      },
    });
  }
}
</script>
<template>
  <Head title="Add TM Lead Status" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Update' : 'Create' }} TM Lead Status
    </h2>
    <div>
      <Link :href="route('tmleadstatus.index')">
        <x-button size="sm" color="#ff5e00"> TM Lead Status List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field label="Code" required>
        <x-input
          v-model="leadForm.code"
          type="text"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="Text En" required>
        <x-input
          v-model="leadForm.text"
          type="text"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="Text Ar" required>
        <x-input
          v-model="leadForm.text_ar"
          type="text"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="Sort Order" required>
        <x-input
          v-model="leadForm.sort_order"
          type="text"
          :rules="[isRequired]"
          class="w-full"
        />
      </x-field>
      <x-field label="Is Active" required>
        <x-select
          v-model="leadForm.is_active"
          :options="[
            {
              value: 'on',
              label: 'Yes',
            },
            {
              value: 'off',
              label: 'No',
            },
          ]"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
    </div>
    <x-divider class="my-4" />
    <div class="flex justify-end gap-3 mb-4">
      <x-button size="md" color="emerald" type="submit">
        {{ isEdit ? 'Update' : 'Save' }}
      </x-button>
    </div>
  </x-form>
</template>
