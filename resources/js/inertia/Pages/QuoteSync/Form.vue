<script setup>
const props = defineProps({
  quote_sync: Object,
  quote_sync_status: Object,
});

const page = usePage();

const form = useForm({
  is_synced: props.quote_sync?.is_synced || 0,
  updated_fields: props.quote_sync?.updated_fields || '',
  status: props.quote_sync?.status || 0,
  sync_followed_entries: 0,
});

const { isRequired } = useRules();

const quoteSyncStatusOptions = computed(() => {
  return Object.keys(page.props.quote_sync_status).map(id => ({
    label: page.props.quote_sync_status[id],
    value: +id,
  }));
});

function onSubmit(isValid) {
  if (isValid) {
    const method = 'put';
    const url = route('admin.quotesync.update', page.props.quote_sync.id);

    const options = {
      onError: errors => {
        form.setError(errors);
      },
    };

    form.submit(method, url, options);
  }
}
</script>

<template>
  <div>
    <Head title="Quote Sync" />
    <div class="flex justify-between items-center">
      <h2 class="text-xl font-semibold">Quote Sync</h2>
      <div>
        <Link :href="route('admin.quotesync')">
          <x-button size="sm" color="primary"> Quote sync list </x-button>
        </Link>
      </div>
    </div>
    <x-divider class="my-4" />
    <x-form @submit="onSubmit" :auto-focus="false">
      <x-alert color="error" class="mb-5" v-if="form.errors.error">
        {{ form?.errors?.error }}
      </x-alert>

      <div class="grid gap-4">
        <x-field label="Is Synced?" required>
          <x-toggle v-model="form.is_synced" color="success" />
        </x-field>

        <x-field label="Status" required>
          <x-select
            v-model="form.status"
            placeholder="Select Status"
            :options="quoteSyncStatusOptions"
            class="w-full"
          />
        </x-field>

        <x-field label="Fields" required>
          <x-input
            v-model="form.updated_fields"
            :rules="[isRequired]"
            class="w-full"
            :error="form.errors.updated_fields"
          />
        </x-field>
      </div>

      <div v-if="quote_sync?.error" class="p-4 rounded shadow mb-6 bg-white">
        <div class="text-sm">
          <dt class="font-medium font-black mb-4">Error</dt>
          <dd>{{ quote_sync?.error }}</dd>
        </div>
      </div>

      <x-field label="Sync Followed entries">
        <x-toggle v-model="form.sync_followed_entries" color="success" />
      </x-field>

      <div class="flex justify-end gap-3 my-4">
        <x-button
          size="md"
          color="emerald"
          type="submit"
          class="px-6"
          :loading="form.processing"
        >
          Update
        </x-button>
      </div>
    </x-form>
  </div>
</template>
