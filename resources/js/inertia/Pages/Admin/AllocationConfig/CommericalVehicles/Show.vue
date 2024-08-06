<script setup>
const props = defineProps({
  carMake: Object,
});

const formatted = date => useDateFormat(date, 'DD-MM-YYYY').value;

const models = computed(() => {
  if (props.carMake)
    return props.carMake.car_models.map(car => car.text).join(', ');
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
  <div class="p-4 rounded shadow mb-6 bg-white text-sm">
    <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words">
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Car Make</dt>
        <dd>{{ carMake.text }}</dd>
      </div>

      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Code Key</dt>
        <dd>{{ carMake.code }}</dd>
      </div>
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Commercial Car Models</dt>
        <dd>{{ models }}</dd>
      </div>
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Created At</dt>
        <dd>{{ formatted(carMake.created_at) }}</dd>
      </div>
      <div class="grid sm:grid-cols-2">
        <dt class="font-medium">Updated At</dt>
        <dd>{{ formatted(carMake.updated_at) }}</dd>
      </div>
    </dl>
  </div>
</template>
