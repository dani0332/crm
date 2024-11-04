<script setup>
const props = defineProps({
  tmleadstatus: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>
<template>
  <Head title="TM Lead Status Detail" />
  <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
    <h2 class="text-xl font-semibold">TM Lead Status Detail</h2>
    <div class="flex gap-2">
      <Link
        :href="route('tmleadstatus.destroy', tmleadstatus.id)"
        preserve-scroll
        method="delete"
        v-if="can(permissionsEnum.TMLeadStatusDelete)"
      >
        <x-button size="sm" color="primary" tag="div"> Delete </x-button>
      </Link>
      <Link
        :href="route('tmleadstatus.edit', tmleadstatus.id)"
        v-if="can(permissionsEnum.TMLeadStatusEdit)"
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
      <Link :href="route('tmleadstatus.index')">
        <x-button size="sm" color="#ff5e00"> TM Lead Status List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Code</dt>
          <dd>{{ tmleadstatus.code }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Text En</dt>
          <dd>{{ tmleadstatus.text }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Text Ar</dt>
          <dd>
            {{ tmleadstatus.text_ar }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Sort Order</dt>
          <dd>
            {{ tmleadstatus.sort_order }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Is Active</dt>
          <dd>
            {{ tmleadstatus.is_active }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>
            {{ tmleadstatus.created_at }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>
            {{ tmleadstatus.updated_at }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    v-if="can(permissionsEnum.Auditable)"
    :type="'App\\Models\\TmLeadStatus'"
    :quoteType="'TmLeadStatus'"
    :id="$page.props.tmleadstatus.id"
  />
</template>
