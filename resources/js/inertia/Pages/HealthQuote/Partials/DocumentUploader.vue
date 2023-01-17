<script setup>
import { ref } from 'vue';

// const emit = defineEmits(["update:action"]);
defineProps({
  members: Array,
});
const memberTabs = ref('quote-documents');
</script>

<template>
  <div>
    <div>
      <x-tab-group v-model="memberTabs" class="pb-10" variant="block">
        <x-tab value="quote-documents" label="Documents">
          Documents Uploads
        </x-tab>
        <x-tab
          v-for="member in members"
          :key="member.id"
          :value="`member-${member.id}`"
          :label="member.name"
        >
          {{ member.name }} Uploads
        </x-tab>
      </x-tab-group>
    </div>
  </div>
</template>
