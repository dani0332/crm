<script setup>
const props = defineProps({
  branch: Object,
});

</script>
<template>

  <Head title="Branch Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Branch Detail</h2>
    <div class="space-x-3">
      <Link :href="route('branches.index')">
      <x-button size="sm" color="#ff5e00" tag="div">
        Branch List
      </x-button>
      </Link>
      <Link :href="route('branches.edit', props.branch.id)">
      <x-button size="sm" color="primary" tag="div">
        Edit Branch
      </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">NAME</dt>
          <dd>{{ branch.name ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CODE</dt>
          <dd>{{ branch.code ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">TYPE</dt>
          <dd>{{ branch.type ?? 'N/A' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">STATUS</dt>
          <dd>{{ branch.status ? 'Active' : 'Inactive' }}</dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">CREATED AT</dt>
          <dd>
            {{
            branch.created_at
            ? branch.created_at.split('T')[0]
            : 'N/A'
            }}
          </dd>
        </div>

        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">UPDATED AT</dt>
          <dd>
            {{
            branch.updated_at
            ? branch.updated_at.split('T')[0]
            : 'N/A'
            }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
  <AuditLogs :url="'\\auditable'" :type="'App\\Models\\Branch'" :id="$page.props.branch.id" :quoteType="'Branch'" />
</template>
