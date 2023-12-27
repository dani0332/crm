<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    require: false,
    default: () => false,
  },
});

const emit = defineEmits(['update:modelValue']);

const showModal = computed({
  get() {
    return props.modelValue;
  },
  set(newValue) {
    emit('update:modelValue', newValue);
  },
});
</script>
<template>
  <x-modal v-model="showModal" show-close backdrop>
    <template #header>
      <p class="font-bold m-0">Notes</p>
    </template>

    <div>
      <div class="flex justify-end">
        <x-button size="sm" color="orange"> Add Notes </x-button>
      </div>
    </div>
    <template #actions>
      <div class="text-right space-x-4">
        <x-button>Cancel</x-button>
        <x-button color="success">Confirm</x-button>
      </div>
    </template>
  </x-modal>

  <x-modal v-model="addNotes" show-close backdrop>
    <template #header>
      <p class="font-bold m-0">Notes</p>
    </template>

    <div>
      <div class="flex justify-end">
        <x-button size="sm" color="orange"> Add Notes </x-button>
      </div>
    </div>
    <template #actions>
      <div class="text-right space-x-4">
        <x-button>Cancel</x-button>
        <x-button color="success">Confirm</x-button>
      </div>
    </template>
  </x-modal>
</template>