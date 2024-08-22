<script setup>
import { useDropzone } from 'vue3-dropzone';
const props = defineProps({
  modelValue: Array,
  accept: {
    type: [Array, String],
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  maxSize: {
    type: Number,
    default: 10,
  },
  maxFiles: {
    type: Number,
    default: 1,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  customDisplay: {
    type: Boolean,
    default: false,
  },
  isDisabled: {
    type: Boolean,
    default: false,
  },
  documentTypeCode: String,
});

const page = usePage();
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;
const emit = defineEmits(['update:modelValue', 'change', 'changeMethod']);
const onDrop = (f, rejectReasons) => {
  if (
    !(props.documentTypeCode == documentTypeCodeEnum.AUDIT && props.isDisabled)
  ) {
    const files = f.map(file => ({ file }));
    let rejectReason = null;
    if (rejectReasons.length > 0) {
      rejectReason = rejectReasons[0]['errors'][0] ?? null;
    }
    const filesWithInfo = {
      files,
      rejectReason,
    };
    emit('update:modelValue', files);
    emit('changeMethod', files);
    emit('change', filesWithInfo);
  }
};

const { getRootProps, getInputProps, open, isDragActive } = useDropzone({
  onDrop,
  multiple: props.multiple,
  accept: props.accept,
  noClick: true,
  maxFiles: props.maxFiles,
  maxSize: props.maxSize * 1024 * 1024,
});
</script>

<template>
  <div
    v-bind="getRootProps()"
    class="relative bg-primary-50 rounded-md text-center flex flex-col gap-4 items-center border border-primary-300 ease-linear transition-all duration-150"
    :class="[isDragActive ? 'border-primary-600 bg-primary-100' : '']"
  >
    <div v-if="customDisplay" class="p-1">
      <input v-bind="getInputProps()" />
      <x-button @click="open" size="xs" :loading="loading">
        Upload Documents
      </x-button>
    </div>
    <div v-else class="p-4">
      <div class="p-4" v-if="!isDisabled">
        <input v-bind="getInputProps()" />
        <span class="block text-gray-700 text-xs"> Drop file here </span>
        <span class="block mb-2 mt-1 text-gray-700 text-xs"> or </span>
        <x-button @click="open" size="xs" :loading="loading">
          Click to browse
        </x-button>
      </div>
      <div class="p-8" v-else>
        <x-tooltip placement="bottom">
          <x-button :disabled="isDisabled" size="xs">
            Click to browse
          </x-button>
          <template #tooltip>
            This section is for audit purposes only. Only authorised users can
            upload files here
          </template>
        </x-tooltip>
      </div>
    </div>
  </div>
</template>
