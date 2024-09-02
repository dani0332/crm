<script setup>
const props = defineProps({
  tmuploadlead: Object,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
</script>
<template>
  <Head title="TeleMarkating Details" />
  <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
    <h2 class="text-xl font-semibold">Upload TM Lead Detail</h2>
    <div class="flex gap-2">
      <!-- <Link
        :href="route('tmuploadlead-delete', tmuploadlead.id)"
        preserve-scroll
      >
        <x-button size="sm" color="primary" tag="div"> Delete </x-button>
      </Link>
      <Link :href="route('tmuploadlead-edit', tmuploadlead.id)">
        <x-button size="sm" tag="div">Edit</x-button>
      </Link> -->
      <Link :href="route('tmuploadlead-list')">
        <x-button size="sm" color="#ff5e00"> Upload TM Leads List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">File</dt>
          <dd>{{ tmuploadlead.file_name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Good</dt>
          <dd>{{ tmuploadlead.good }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>
            {{ tmuploadlead.created_at }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Submitted By</dt>
          <dd>{{ tmuploadlead?.createdby?.name }}</dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs
    v-if="can(permissionsEnum.Auditable)"
    :type="'App\\Models\\TmUploadLead'"
    :quoteType="'TmUploadLead'"
    :id="$page.props.tmuploadlead.id"
  />
</template>
