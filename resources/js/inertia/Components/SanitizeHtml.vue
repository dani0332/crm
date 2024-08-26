<script setup>
import sanitizeHtml from 'sanitize-html';
import { onMounted, ref, watchEffect } from 'vue';

const block = ref();
const props = defineProps({
  html: {
    type: String,
    required: true,
  },
});

const onUpdateContent = () => {
  if (block.value) block.value.innerHTML = sanitizeHtml(props.html);
};

watchEffect(
  () => props.html,
  () => {
    onUpdateContent();
  },
);

onMounted(() => onUpdateContent());
</script>

<template>
  <div ref="block"></div>
</template>
