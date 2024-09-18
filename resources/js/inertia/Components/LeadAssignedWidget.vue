<script setup>
const props = defineProps({
  todayAutoCount: {
    required: true,
    type: Number,
  },
  todayManualCount: {
    required: true,
    type: Number,
  },
  yesterdayAutoCount: {
    required: true,
    type: Number,
  },
  yesterdayManualCount: {
    required: true,
    type: Number,
  },
  userMaxCap: {
    required: true,
    type: Number,
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
      <div class="lg:flex gap-1 hidden justify-between text-xs w-full mb-1">
        <x-tooltip placement="top">
          <x-tag
            class="underline decoration-dotted decoration-primary-700 border-gray-200"
            size="xs"
            outlined
          >
            <span>Assigned Yesterday: &nbsp;</span>
            <strong> {{ yesterdayAutoCount + yesterdayManualCount }}</strong>
          </x-tag>
          <template #tooltip>
            Auto : {{ yesterdayAutoCount }} | Manual :
            {{ yesterdayManualCount }}
          </template>
        </x-tooltip>

        <x-tooltip placement="top">
          <x-tag
            class="underline decoration-dotted decoration-primary-700 border-gray-200"
            size="xs"
            outlined
          >
            <span>Assigned Today: &nbsp;</span>
            <strong>{{ todayAutoCount + todayManualCount }}</strong>
          </x-tag>
          <template #tooltip>
            Auto : {{ todayAutoCount }} | Manual : {{ todayManualCount }}
          </template>
        </x-tooltip>

        <x-tag
          size="xs"
          class="decoration-primary-700 border-gray-200"
          outlined
        >
          <span>Max Capacity: &nbsp;</span>
          <strong>{{ userMaxCap }}</strong>
        </x-tag>
      </div>
    </teleport>
  </template>
</template>
