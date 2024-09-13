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
      <div class="flex justify-between text-sm w-full gap-3">
        <x-tooltip placement="top">
          <x-tag
            class="underline decoration-dotted decoration-primary-700"
            outlined
            style="border: 1px solid #ebebeb;font-size: 13px"
          >
            <span>Assigned Yesterday : &nbsp;</span>
            <strong> {{ yesterdayAutoCount + yesterdayManualCount }}</strong>
          </x-tag>
          <template #tooltip>
            Auto : {{ yesterdayAutoCount }} | Manual :
            {{ yesterdayManualCount }}
          </template>
        </x-tooltip>

        <x-tooltip placement="top">
          <x-tag
            class="underline decoration-dotted decoration-primary-700"
            outlined
            style="border: 1px solid #ebebeb;font-size: 13px"

          >
            <span>Assigned Today : &nbsp;</span>
            <strong>{{ todayAutoCount + todayManualCount }}</strong>
          </x-tag>
          <template #tooltip>
            Auto : {{ todayAutoCount }} | Manual : {{ todayManualCount }}
          </template>
        </x-tooltip>

        <x-tag color="gray" class="decoration-primary-700" outlined
               style="border: 1px solid #ebebeb;font-size: 13px"

        >
          <span>Max Capacity : &nbsp;</span>
          <strong>{{ userMaxCap }}</strong>
        </x-tag>
      </div>
    </teleport>
  </template>
</template>
