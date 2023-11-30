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
    type: String,
    required: true
  },
  reportableId: {
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

const page = usePage();
const dateFormat = date => date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const sendUpdatesTable = reactive({
  headers: [
    { text: 'SU-REF ID', value: 'code' },
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

const form = useForm({
  parentCategory: null,
  childCategory: null,
  option: null,
  reportable_type: props.reportableType,
  reportable_uuid: props.reportableUuid,
  reportable_id: props.reportableId,
  status: page.props.sendUpdateEnum.NEW_REQUEST
})

const resetForm = () => {
  form.parentCategory = null,
  form.childCategory =  null,
  form.option = null
  modals.step = 'step1'
}

const setOption = (step_next, value) => {
  switch (step_next) {
    case 'step1':
      form.parentCategory = null;
      modals.step = step_next
      break;
    case 'step2':
      form.parentCategory = value
      modals.step = step_next
      break;
    case 'step3':
      form.childCategory = value
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
    form.parentCategory = null;
  } else if (modals.step === 'step3') {
    modals.step = 'step2'
    form.childCategory = null;
  }
}

const onAddUpdate = () => {
  form
    .transform(data => ({
      ...data,
      model_type: data.reportable_type,
      reportable_type: `App\\Models\\${data.reportable_type}`
    }))
    .post(`/send-update-logs`, {
      onSuccess: () => {
        modals.show = false
        resetForm()
        // sendUpdatesTable.data = [...sendUpdatesTable.data, form.data]
      }    
    })
}

const findOption = (item, key) => {
  let title = '';
  props.options.forEach(option => {
    if (option.id === item[key]) {
      title = option.title;
    } else {
      option.childs.forEach(child => {
        if (child.id === item[key]) {
          title = child.title;
        } else {
          child.childs.forEach(grandChild => {
            if (grandChild.id === item[key]) {
              title = grandChild.title;
            }
          })
        }
      })
    }
  })
  return title;
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
          <template #item-code="{ code, uuid }">
            <Link :href="route('quotes.car.view-update-log', {id: reportableUuid, uuid: uuid})" class="text-primary-800 underline">{{ code }}</Link>
          </template>

          <template #item-type="item">
            <span>{{ findOption(item, 'category_id') }}</span>
          </template>

          <template #item-sub_type="item">
            <span>{{ findOption(item, 'option_id') }}</span>
          </template>

          <template #item-created_at="{ created_at }">
            <span>{{ dateFormat(created_at) }}</span>
          </template>
        </DataTable>
      </template>
    </x-collapse>
    <x-modal v-model="modals.show" size="xl" show-close backdrop @update:modelValue="resetForm">
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
        <template v-for="category in form.parentCategory?.childs" :key="category.title">
          <x-tooltip position="bottom" class="arrow-t">
            <x-button color="primary" class="py-8 px-6 rounded-xl" @click="setOption('step3', category)">
              {{ category.title }}
            </x-button>
            <template #tooltip> <div>{{ category.description }}</div> </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 3 -->
      <div class="w-full flex gap-5 mb-10" v-else-if="modals.step === 'step3' && form.childCategory.childs.length > 0">
        <div class="flex flex-col gap-2 flex-grow w-75">
          <x-field :label="form.childCategory.title" required>
            <x-select
              v-model="form.option"
              :options="form.childCategory.childs.map(item => ({ label: item.title, value: item.id }))"
              :rules="[isRequired]"
              placeholder="Select Reason"
              class="w-full"
            />
          </x-field>
          <div class="flex justify-end mt-2">
            <x-button 
              size="sm" 
              color="primary" 
              @click="onAddUpdate"
              :disabled="form.processing"
              :loading="form.processing"
            >
              Add Update
            </x-button>  
          </div>
        </div>
      </div>
    </x-modal>
  </div>
</template>