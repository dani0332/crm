<script setup>
const props = defineProps({
  keyword: Object,
});
</script>
<template>
  <Head title="Commercial Keyword Detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Commercial Keyword Detail</h2>
    <div class="flex gap-2">
      <Link :href="route('admin.commercial.keywords')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Commercial Keyword List
        </x-button>
      </Link>
      <Link :href="route('admin.commercial.keywords.edit', keyword.id)">
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white text-sm">
    <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Keyword Name</dt>
        <dd>{{ keyword.name }}</dd>
      </div>

      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Keyword Key</dt>
        <dd>{{ keyword.key }}</dd>
      </div>
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Created At</dt>
        <dd>{{ keyword.created_at }}</dd>
      </div>
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Updated At</dt>
        <dd>{{ keyword.updated_at }}</dd>
      </div>
    </dl>
  </div>
</template>
