<script setup>
const props = defineProps({
  source: {
    type: String,
    default: null,
  },
  eaModel: {
    type: String,
    default: null,
  },
  leadGenerator: {
    type: Object,
    default: null,
  },
  expertAdvisor: {
    type: Object,
    default: null,
  },
});

const isVisible = computed(() => props.source === 'EA_IMCRM');
</script>

<template>
  <div v-if="isVisible" class="p-4 rounded shadow mb-6 bg-white border-l-4">
    <h3 class="font-semibold text-primary-800 text-lg">EA Lead Information</h3>
    <x-divider class="mb-4 mt-1" />
    <div class="text-sm">
      <dl
        class="grid sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-4 break-words"
      >
        <div class="grid">
          <dt class="font-medium">EA MODEL</dt>
          <dd class="mt-1">
            {{ eaModel ?? '—' }}
          </dd>
        </div>
        <div class="grid">
          <dt class="font-medium">LEAD GENERATOR</dt>
          <dd class="mt-1">{{ leadGenerator?.name ?? '—' }}</dd>
        </div>
        <div v-if="eaModel === 'collaborate'" class="grid">
          <dt class="font-medium">EXPERT ADVISOR</dt>
          <dd class="mt-1">{{ expertAdvisor?.name ?? '—' }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
