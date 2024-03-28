<script setup>
const props = defineProps({
  carMake: Array,
});
</script>
<template>
  <Head title="Configure Commercial car make & model detail" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Commercial Vehicle Detail</h2>
    <div class="flex gap-2">
      <Link :href="route('admin.configure.commerical.vehicles')">
        <x-button size="sm" color="#1d83bc" tag="div">
          Commercial Vehicles List
        </x-button>
      </Link>
      <Link
        :href="route('admin.configure.commerical.vehicles.edit', carMake.id)"
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-primary-50/50"></div>
</template>