<script setup>
const props = defineProps({
  claim: Object,
  expanded: {
    type: Boolean,
    default: false,
    required: false,
  },
});
 
 
</script>

<template>
  <div class="p-4 rounded shadow my-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Customer Details
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="text-sm">
          <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 break-words py-8">
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">FIRST NAME</dt>
              <dd>{{ claim.first_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">LAST NAME</dt>
              <dd>{{ claim.last_name }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">EMAIL</dt>
              <dd>{{ claim.email }}</dd>
            </div>
            <div class="grid sm:grid-cols-2">
              <dt class="font-medium">MOBILE NUMBER</dt>
              <dd>{{ claim.mobile_no }}</dd>
            </div>
          </dl>
        </div>
      </template>
    </Collapsible>
  </div>
</template>
