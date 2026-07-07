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
  eaManagerApprovedAt: {
    type: String,
    default: null,
  },
  eaManagerRejectedAt: {
    type: String,
    default: null,
  },
});

const { leadSource, eaModelEnum } = usePage().props;

const isVisible = computed(() => props.source === leadSource.EA_IMCRM);
const isEaCollaborateLead = computed(
  () =>
    props.source === leadSource.EA_IMCRM &&
    props.eaModel === eaModelEnum.Collaborate,
);
const hasManagerDecision = computed(
  () => !!props.eaManagerApprovedAt || !!props.eaManagerRejectedAt,
);
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
        <div v-if="eaModel === eaModelEnum.Collaborate" class="grid">
          <dt class="font-medium">EXPERT ADVISOR</dt>
          <dd class="mt-1">{{ expertAdvisor?.name ?? '—' }}</dd>
        </div>
      </dl>
    </div>

    <div
      v-if="isEaCollaborateLead && hasManagerDecision"
      class="mt-4 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium"
      :class="
        eaManagerApprovedAt
          ? 'bg-green-50 text-green-700'
          : 'bg-red-50 text-red-700'
      "
    >
      <span>{{ eaManagerApprovedAt ? '&#10003;' : '&#10007;' }}</span>
      <span>
        EA Manager has already
        {{ eaManagerApprovedAt ? 'approved' : 'rejected' }} this lead.
      </span>
    </div>
  </div>
</template>
