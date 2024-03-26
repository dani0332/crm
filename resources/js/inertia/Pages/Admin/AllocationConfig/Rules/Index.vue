<script setup>
defineProps({});
</script>
<template>
  <Head title="Rules List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Rules List</h2>
    <div class="space-x-3">
      <Link :href="route('tier.create')">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Rules </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
</template>