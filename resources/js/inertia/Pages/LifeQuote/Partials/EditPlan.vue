<template>
    <x-modal
      v-model="shown"
      size="lg"
      :title="selectedPlan.providerName ?? 'View Plan'"
      show-close
      backdrop
      is-form
      @submit="onSubmit"
    >
    <div class="mx-auto p-6 bg-white rounded-lg w-full">
    <TabGroup>
        <TabList class="flex flex-row flex-wrap gap-2 rounded-xl bg-slate-100 p-1.5 w-full">
            <Tab
            v-for="{ index, label } in tabs"
            as="template"
            :key="index"
            v-slot="{ selected }"
            >
            <button
                :class="[
                'rounded-lg px-3 py-2 md:min-w-[15%] text-sm font-medium text-gray-800 transition duration-200 ease-in-out uppercase',
                'ring-white ring-opacity-60 ring-offset-2 ring-offset-primary-50 focus:outline-none focus:ring-2',
                selected
                    ? 'bg-white shadow text-primary-600'
                    : 'hover:bg-white/50',
                ]"
            >
                {{ label }}
            </button>
            </Tab>
        </TabList>          
    

        <TabPanels>
            <TabPanel>
                <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4 p-4">
                    <div class="grid sm:grid-cols-2 mb-3">
                        <x-toggle
                            v-model="planForm.is_disabled"
                            color="success"
                            label="Hide Plan?"
                            @change="onTogglePlans"
                            :loading="toggleLoader"
                        />
                    </div>
                </dl> 
                </TabPanel>
        </TabPanels>
    </TabGroup>
    </div>

    <template #actions>
        <div class="flex justify-end">
        </div>
    </template>
    </x-modal>
</template>
  
<script setup>
import { TabPanels } from '@headlessui/vue';

const tabs = ref([
  { index: 0, label: 'General Info' },
  { index: 1, label: 'Addons' },
  { index: 2, label: 'Inclusions' },
  { index: 3, label: 'Exclusions' },
  { index: 4, label: 'Policy Detail' },
]);


const planForm = useForm({
    is_disabled: false,
});
const props = defineProps({
    selectedPlan: Object,
});
</script>

<style scoped>
</style>
  