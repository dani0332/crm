<script setup>
defineProps({
  plan: Object,
});
const tab = '1';
</script>

<template>
  <x-tab-group v-model="tab" class="pb-10 text-sm" variant="block" size="sm">
    <x-tab value="1" label="General Info" size="sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Provider Code</dt>
          <dd>{{ plan.code }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Provider Name</dt>
          <dd>{{ plan.providerName }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Actual Premium</dt>
          <dd>{{ plan.actualPremium }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Discount Premium</dt>
          <dd>{{ plan.discountPremium }}</dd>
        </div>
      </dl>
    </x-tab>
    <x-tab value="2" label="Members" size="sm">
      <div class="p-4">
        <x-table :items="plan.memberPremiumBreakdown">
          <template #item-dob="{ item }">
            {{ item.dob }}
          </template>
          <template #item-status="{ item }">
            <x-tag size="sm" color="primary" rounded>{{ item.status }}</x-tag>
          </template>
        </x-table>
        <div
          v-for="member in plan.memberPremiumBreakdown || []"
          :key="member.memberId"
          class="grid grid-cols-2 md:grid-cols-4 gap-2 my-4 border-b"
        >
          <div>{{ member.memberCategoryText }}</div>
          <div>{{ member.dob }}</div>
          <div>{{ member.gender }}</div>
          <x-input :value="member.premium" :disabled="true" size="sm" />
        </div>
      </div>
    </x-tab>
    <x-tab value="3" label="In Patient" size="sm"> In Patient </x-tab>
    <x-tab value="4" label="Out Patient" size="sm"> Out Patient </x-tab>
    <x-tab value="5" label="Co-pay/Co-insurance" size="sm">
      Co-pay/Co-insurance
    </x-tab>
    <x-tab value="6" label="Region coverage & Network list" size="sm">
      Region coverage & Network list
    </x-tab>
    <x-tab value="7" label="Maternity cover" size="sm"> Maternity cover </x-tab>
    <x-tab value="8" label="Exclusions" size="sm"> Exclusions </x-tab>
    <x-tab value="9" label="Policy Detail" size="sm"> Policy Detail </x-tab>
  </x-tab-group>
</template>
