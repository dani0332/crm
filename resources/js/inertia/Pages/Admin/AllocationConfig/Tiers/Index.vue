<script setup>
const props = defineProps({
  tiers: Object,
  model: Object,
  dropdownSource: Object,
  customTitles: Object,
});
</script>
<template>
  <Head title="Tier List" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Tier List</h2>
    <div class="space-x-3">
      <Link href="">
        <x-button size="sm" color="#ff5e00" tag="div"> Create Tier </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
</template>