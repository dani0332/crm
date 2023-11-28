<script setup>

const props = defineProps({
  showAddButton: {
    type: Boolean,
    required: true,
    default: false
  },
  reportableType: {
    type: String,
    required: true,
    default: ''
  },
  reportableUuid: {
    type: Number,
    required: true
  },
  data: {
    type: Array,
    required: true,
    default: () => []  
  },
  options: {
    type: Array,
    required: true
  }
})

const sendUpdatesTable = reactive({
  headers: [
    { text: 'SU-REF ID', value: 'ref_id' },
    { text: 'Type', value: 'type' },
    { text: 'Sub Type', value: 'sub_type' },
    { text: 'Status', value: 'status' },
    { text: 'Created date', value: 'created_at' },
  ],
  data: props.data
})

const modals = reactive({
  step: 'step1',
  show: false
})

const state = reactive({
  parentCategory: null,
  childCategory: null,
  option: null
})

const resetState = () => {
    state.parentCategory = null,
    state.childCategory =  null,
    state.option = null
    modals.step = 'step1'
}

const setOption = (step_next, value) => {
  console.log('value', value);
  switch (step_next) {
    case 'step1':
      state.parentCategory = null;
      modals.step = step_next
      break;
    case 'step2':
      state.parentCategory = value
      modals.step = step_next
      break;
    case 'step3':
      state.childCategory = value
      modals.step = step_next
      break;
  
    default:
      modals.step = 'step1';
      modals.show = false;
      break;
  }
}

const goBack = () => {
  if (modals.step === 'step2') {
    modals.step = 'step1'
    state.parentCategory = null;
  } else if (modals.step === 'step3') {
    modals.step = 'step2'
    state.childCategory = null;
  }
}

</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <x-collapse expanded show-icon>
      <div class="flex justify-between gap-4 items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Send Update
        </h3>
      </div>
      <template #content>
        <div class="my-4 flex justify-end">
          <x-button
            v-if="showAddButton"
            size="sm"
            color="orange"
            @click="modals.show = true"
          >
            Add Update
          </x-button>
        </div>
        <DataTable
          table-class-name="tablefixed compact"
          :headers="sendUpdatesTable.headers"
          :items="sendUpdatesTable.data"
          border-cell
          hide-rows-per-page
          :hide-footer="sendUpdatesTable.data <= 10"
        >

        </DataTable>
      </template>
    </x-collapse>
    <x-modal v-model="modals.show" size="xl" show-close backdrop @update:modelValue="resetState">
      <template #header>
        <div class="flex gap-3">
          <x-icon icon="prev" size="md" class="text-primary-800 mt-1 cursor-pointer" @click="goBack" v-if="modals.step !== 'step1'"/>
          <span class="text-primary-800 font-semibold">
            Add Update
          </span>
        </div>
      </template>

      <!-- modal 1 -->
      <div class="w-full flex gap-5 justify-center text-center mb-10" v-if="modals.step === 'step1'">
        <template v-for="option in options" :key="option.title">
          <x-tooltip position="bottom" class="arrow-t">
            <x-button color="primary" class="py-8 px-6 rounded-xl" @click="setOption('step2', option)">
              {{ option.title }}
            </x-button>
            <template #tooltip> <div>{{ option.description }}</div> </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 2 -->
      <div class="w-full flex gap-5 justify-center text-center mb-10" v-else-if="modals.step === 'step2'">
        <template v-for="category in state.parentCategory?.childs" :key="category.title">
          <x-tooltip position="bottom" class="arrow-t">
            <x-button color="primary" class="py-8 px-6 rounded-xl" @click="setOption('step3', category)">
              {{ category.title }}
            </x-button>
            <template #tooltip> <div>{{ category.description }}</div> </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 3 -->
      <div class="w-full flex gap-5 mb-10" v-else-if="modals.step === 'step3' && state.childCategory.childs.length > 0">
        <div class="flex flex-col gap-2 flex-grow w-75">
          <x-field :label="state.childCategory.title" required>
            <x-select
              v-model="state.option"
              :options="state.childCategory.childs.map(item => ({ label: item.title, value: item.id }))"
              :rules="[isRequired]"
              placeholder="Select Reason"
              class="w-full"
            />
          </x-field>
          <div class="flex justify-end mt-2">
            <x-button class="" size="sm" color="primary" @click="addUpdate">Add Update</x-button>  
          </div>
        </div>
      </div>
    </x-modal>
  </div>
</template>