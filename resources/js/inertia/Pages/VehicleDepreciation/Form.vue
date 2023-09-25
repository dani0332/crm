<script setup>
</script>
<template>
  <Head title="Create Vehical Depreciation" />
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Vehical Depreciation</h2>
    <div class="space-x-3">
      <Link :href="route('home.create')">
        <x-button size="sm" color="#ff5e00" tag="div">
          Depreciation List
        </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-3 gap-4">
      <x-field label="Insurance Provider">
        <x-select
          v-model="valuationForm.make_code"
          :options="
            props.carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Car Make">
        <x-select
          v-model="valuationForm.make_code"
          :options="
            props.carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
      <x-field label="Car Model">
        <x-select
          v-model="valuationForm.make_code"
          :options="
            props.carMakes.map(item => ({
              value: item.id,
              label: item.text,
            }))
          "
          class="w-full"
        />
      </x-field>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <x-field title="First Year" required>
        <x-input
          v-model="valuationForm.yearOfManufacture"
          type="number"
          class="w-full"
        />
      </x-field>
    </div>
  </x-form>
</template>