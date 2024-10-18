<script setup>
const props = defineProps({
  text: {
    type: String,
    default: '',
    required: true,
  },
});

const { copy, copied } = useClipboard();

const isCopied = ref(false);

watch(copied, value => {
  if (value) {
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2000);
  }
});
</script>

<template>
  <div class="flex items-center gap-1">
    <!-- <span>{{ props.text }}</span> -->
    <x-tooltip placement="top">
      <div
        class="cursor-pointer"
        :class="
          isCopied ? 'text-green-600' : 'text-gray-500 hover:text-gray-600'
        "
        @click="copy(props.text)"
      >
        <x-icon v-if="!isCopied" icon="copy" class="text-primary" size="md" />
        <x-icon v-else icon="copyCheck" class="text-primary" size="md" />
      </div>
      <template #tooltip>
        {{ isCopied ? 'Copied' : 'Copy' }} to clipboard
      </template>
    </x-tooltip>
  </div>
</template>
