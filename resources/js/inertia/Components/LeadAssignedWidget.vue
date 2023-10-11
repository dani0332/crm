<script setup>
const props = defineProps({
  leads: {
    type: Object,
    default: () => ({}),
  },
});
const mounted = ref(false);

onMounted(() => {
  mounted.value = true;
});
</script>
<template>
  <template v-if="mounted">
    <teleport to="#headerportal">
      <div class="flex justify-between text-sm w-full gap-3">
        <x-tooltip position="bottom" align="top" class="arrow-t">
          <x-tag
            color="gray"
            class="underline decoration-dotted decoration-primary-700"
            outlined
          >
            <span>Assigned Yesterday: </span>
            <strong>38</strong>
          </x-tag>
          <template #tooltip> Auto 30 | Manual 8 </template>
        </x-tooltip>

        <x-tooltip position="bottom" align="top" class="arrow-t">
          <x-tag
            color="gray"
            class="underline decoration-dotted decoration-primary-700"
            outlined
          >
            <span>Assigned Today: </span>
            <strong>38</strong>
          </x-tag>
          <template #tooltip> Auto 30 | Manual 8 </template>
        </x-tooltip>
      </div>
    </teleport>
  </template>
</template>
