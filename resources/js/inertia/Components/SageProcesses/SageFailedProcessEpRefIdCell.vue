<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  epRefIdDisplayText,
  getMainLeadRefDetailRoute,
} from '@/inertia/Composables/useSageProcessRefId';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const href = computed(() => getMainLeadRefDetailRoute(props.item));
const text = computed(() => epRefIdDisplayText(props.item));
</script>

<template>
  <Link
    v-if="href && text !== 'N/A'"
    :href="href"
    class="text-primary-500 hover:underline"
  >
    <span>{{ text }}</span>
  </Link>
  <span v-else-if="text !== 'N/A'">{{ text }}</span>
  <span v-else>N/A</span>
</template>
