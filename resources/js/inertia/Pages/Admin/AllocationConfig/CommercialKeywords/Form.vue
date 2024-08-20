<script setup>
const props = defineProps({
  keyword: Object,
});
const { isRequired } = useRules();

const isEdit = computed(() => {
  return route().current().includes('edit');
});

const commercialForm = useForm({
  id: props.keyword?.id ?? null,
  name: props.keyword?.name ?? null,
});

function onSubmit(isValid) {
  if (isValid) {
    let method = isEdit.value ? 'put' : 'post';
    let url = isEdit.value
      ? route('admin.commercial.keywords.update', commercialForm.id)
      : route('admin.commercial.keywords.store');

    commercialForm.submit(method, url, {
      onError: errors => {
        Object.keys(errors).forEach(function (key) {
          commercialForm.setError(key, errors[key]);
        });
        return false;
      },
    });
  }
}
</script>
<template>
  <Head :title="isEdit ? 'Edit Keyword' : 'Create Keyword'" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">
      {{ isEdit ? 'Edit' : 'Create' }} Keyword
    </h2>
    <div>
      <Link :href="route('admin.commercial.keywords')">
        <x-button size="sm" color="#1d83bc" tag="div"> Keyword List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-1 gap-4">
      <x-field label="Keyword Name" required>
        <x-input
          v-model="commercialForm.name"
          class="w-full"
          :rules="[isRequired]"
          :error="$page.props.errors.name"
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
