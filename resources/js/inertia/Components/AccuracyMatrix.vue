<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  quote: {
    type: Object,
    required: true,
  },
  modelType: {
    type: String,
    required: true,
  },
});

const matrixStatus = ref(null);
const loading = ref(false);
const error = ref(null);

const shouldShow = computed(() => {
  return matrixStatus.value?.show_matrix === true;
});

const matrixClass = computed(() => {
  if (!shouldShow.value) return '';
  
  const baseClasses = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-colors duration-200';
  
  switch (matrixStatus.value?.status) {
    case 'green':
      return `${baseClasses} bg-green-100 text-green-800 border border-green-200`;
    case 'red':
      return `${baseClasses} bg-red-100 text-red-800 border border-red-200`;
    default:
      return `${baseClasses} bg-gray-100 text-gray-600 border border-gray-200`;
  }
});

const statusIcon = computed(() => {
  if (!shouldShow.value) return null;
  
  switch (matrixStatus.value?.status) {
    case 'green':
      return '✓';
    case 'red':
      return '✗';
    default:
      return '?';
  }
});

const tooltipText = computed(() => {
  return matrixStatus.value?.tooltip || 'Accuracy Matrix Status';
});

const fetchMatrixStatus = async () => {
  if (!props.quote?.id) {
    return;
  }
  
  const eligibleTypes = ['Home','Business'];
  const modelType = props.modelType ? props.modelType.charAt(0).toUpperCase() + props.modelType.slice(1).toLowerCase() : '';

  if (!eligibleTypes.includes(modelType)) {
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await axios.get(`/accuracy-matrix/${modelType}/${props.quote.id}`);
    
    // Validate response structure
    if (response.data && typeof response.data === 'object') {
      matrixStatus.value = response.data;
    } else {
      matrixStatus.value = null;
    }
  } catch (err) {
    // Don't show errors for 404 or validation errors - just hide the matrix
    if (err.response?.status === 404 || err.response?.status === 400) {
      matrixStatus.value = null;
    } else {
      error.value = err.message;
      matrixStatus.value = null;
    }
  } finally {
    loading.value = false;
  }
};

const handleOcrNotification = (event) => {
  const { status, userId } = event.detail || {};
  const currentUserId = usePage().props.auth.user.id;

  if (userId !== currentUserId) {
    return;
  }

  if (status === 'end' || status === 'fail') {
    setTimeout(() => {
      fetchMatrixStatus();
    }, 1000);
  }
};

const handleDocumentDeletion = () => {
  setTimeout(() => {
    fetchMatrixStatus();
  }, 500);
};

const handlePageReload = () => {
  setTimeout(() => {
    fetchMatrixStatus();
  }, 1000);
};

onMounted(() => {
  fetchMatrixStatus();
  window.addEventListener('ocr-notification', handleOcrNotification);
  
  // Listen for Inertia page updates (document deletions, etc.)
  const unsubscribe = usePage().props.app?.router?.on?.('success', handlePageReload);
  
  // Also listen for manual document deletion events
  window.addEventListener('document-deleted', handleDocumentDeletion);
});

onUnmounted(() => {
  window.removeEventListener('ocr-notification', handleOcrNotification);
  window.removeEventListener('document-deleted', handleDocumentDeletion);
});

watch(() => props.quote?.id, () => {
  fetchMatrixStatus();
});
</script>

<template>
  <div v-if="shouldShow" class="accuracy-matrix">
    <x-tooltip>
      <div :class="matrixClass">
        <span class="mr-2 text-lg leading-none">{{ statusIcon }}</span>
        <span class="font-semibold">Accuracy Matrix</span>
      </div>
      <template #tooltip>
        <span>{{ tooltipText }}</span>
      </template>
    </x-tooltip>
  </div>
</template>

<style scoped>
.accuracy-matrix {
  transition: all 0.3s ease-in-out;
}
</style>
