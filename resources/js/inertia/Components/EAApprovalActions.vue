<script setup>
import axios from 'axios';
import { useHasRole } from '../Composables/can';

const props = defineProps({
  quoteType: {
    type: String,
    required: true,
  },
  quoteId: {
    type: Number,
    required: true,
  },
  source: {
    type: String,
    default: null,
  },
  eaModel: {
    type: String,
    default: null,
  },
  quoteStatusId: {
    type: Number,
    default: null,
  },
  advisorId: {
    type: Number,
    default: null,
  },
  expertAdvisorId: {
    type: Number,
    default: null,
  },
  eaAssignedAdvisorApprovedAt: {
    type: String,
    default: null,
  },
  eaExpertAdvisorApprovedAt: {
    type: String,
    default: null,
  },
  eaAssignedAdvisorRejectedAt: {
    type: String,
    default: null,
  },
  eaExpertAdvisorRejectedAt: {
    type: String,
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

const emit = defineEmits(['updated']);

const { auth } = usePage().props;
const currentUserId = auth.user.id;
const isEaManager = useHasRole('EA_MANAGER');

// PolicyIssued = 33
const POLICY_ISSUED = 33;

const isEaCollaborateLead = computed(
  () =>
    props.source === 'EA_IMCRM' &&
    props.eaModel === 'collaborate' &&
    props.quoteStatusId === POLICY_ISSUED,
);

const isAdvisor = computed(
  () =>
    props.advisorId === currentUserId ||
    props.expertAdvisorId === currentUserId,
);

const isVisible = computed(
  () => isEaCollaborateLead.value && isAdvisor.value && !isEaManager,
);

const isAssignedAdvisor = computed(() => props.advisorId === currentUserId);

const myApprovedAt = computed(() =>
  isAssignedAdvisor.value
    ? props.eaAssignedAdvisorApprovedAt
    : props.eaExpertAdvisorApprovedAt,
);

const myRejectedAt = computed(() =>
  isAssignedAdvisor.value
    ? props.eaAssignedAdvisorRejectedAt
    : props.eaExpertAdvisorRejectedAt,
);

const advisorAlreadyActed = computed(
  () => !!myApprovedAt.value || !!myRejectedAt.value,
);

const isLoading = ref(false);
const actionError = ref(null);

const takeAdvisorAction = async action => {
  isLoading.value = true;
  actionError.value = null;

  try {
    await axios.post(
      route(`ea-leads.${action}`, {
        quoteType: props.quoteType,
        quoteId: props.quoteId,
      }),
    );
    emit('updated');
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'An error occurred.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div
    v-if="isVisible"
    class="p-4 rounded shadow mb-6 bg-white border-l-4 border-blue-500"
  >
    <h3 class="font-semibold text-primary-800 text-lg mb-3">
      EA Collaborate Approval
    </h3>

    <p v-if="advisorAlreadyActed" class="text-sm text-gray-600">
      <span v-if="myApprovedAt" class="text-green-600 font-medium"
        >✓ You have approved this lead.</span
      >
      <span v-if="myRejectedAt" class="text-red-600 font-medium"
        >✗ You have rejected this lead.</span
      >
    </p>

    <div v-else class="flex gap-3">
      <x-button
        size="sm"
        color="emerald"
        :loading="isLoading"
        @click="takeAdvisorAction('approve')"
      >
        Approve
      </x-button>
      <x-button
        size="sm"
        color="red"
        :loading="isLoading"
        @click="takeAdvisorAction('reject')"
      >
        Reject
      </x-button>
    </div>

    <p v-if="actionError" class="mt-2 text-sm text-red-600">
      {{ actionError }}
    </p>
  </div>
</template>
