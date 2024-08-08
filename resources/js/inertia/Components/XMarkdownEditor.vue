<script setup>
import MdEditor from 'md-editor-v3';
import 'md-editor-v3/lib/style.css';

const props = defineProps({
  id: {
    type: String,
    default: '',
    required: true,
  },
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  toolBarProp: {
    type: Array,
    default: ['bold', 'italic', 'link', 'preview'],
  },
  required: {
    type: Boolean,
    default: false,
  },
  height: {
    type: String,
    default: 'max-h-56',
  },
});

const emit = defineEmits(['update:modelValue']);

const currentValue = computed({
  get() {
    return props.modelValue;
  },
  set(newValue) {
    emit('update:modelValue', newValue);
  },
});

const classes = ref('');

const onFocus = () => {
  classes.value = 'ring-2 ring-sky-500';
};

const onBlur = () => {
  classes.value = '';
};
</script>

<template>
  <div>
    <MdEditor
      v-model="currentValue"
      :editor-id="props.id"
      language="en-US"
      :toolbars="props.toolBarProp"
      no-upload-img
      no-highlight
      no-mermaid
      no-katex
      no-prettier
      :preview="false"
      :footers="[]"
      :class="classes + ' ' + props.height"
      :placeholder="props.placeholder"
      class="mb-3 rounded-md w-full"
      @on-focus="onFocus"
      @on-blur="onBlur"
    />
    <div
      v-if="props.required && currentValue == ''"
      class="text-red-500 text-sm mt-1"
    >
      This field is required
    </div>
  </div>
</template>

<style>
.md-editor-content .md-editor-input-wrapper textarea,
.cm-scroller {
  @apply !font-sans;
}
</style>
