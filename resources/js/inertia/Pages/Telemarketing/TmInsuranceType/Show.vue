<script setup>
const props = defineProps({
  tminsurancetype: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>
<template>
  <Head title="TM Insurance Type Detail" />
  <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
    <h2 class="text-xl font-semibold">TM Insurance Type Detail</h2>
    <div class="flex gap-2">
      <Link
        :href="route('tminsurancetype.destroy', tminsurancetype.id)"
        preserve-scroll
        method="delete"
        v-if="can(permissionsEnum.TMInsuranceTypeDelete)"
      >
        <x-button size="sm" color="primary" tag="div"> Delete </x-button>
      </Link>
      <Link
        :href="route('tminsurancetype.edit', tminsurancetype.id)"
        v-if="can(permissionsEnum.TMInsuranceTypeEdit)"
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
      <Link :href="route('tminsurancetype.index')">
        <x-button size="sm" color="#ff5e00"> TM Insurance Type List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Code</dt>
          <dd>{{ tminsurancetype.code }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Text En</dt>
          <dd>{{ tminsurancetype.text }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Text Ar</dt>
          <dd>
            {{ tminsurancetype.text_ar }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Sort Order</dt>
          <dd>
            {{ tminsurancetype.sort_order }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Active</dt>
          <dd>
            {{ tminsurancetype.is_active }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>
            {{ tminsurancetype.created_at }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>
            {{ tminsurancetype.updated_at }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    v-if="can(permissionsEnum.Auditable)"
    :type="'App\\Models\\TmInsuranceType'"
    :quoteType="'TmInsuranceType'"
    :id="$page.props.tminsurancetype.id"
  />
</template>
