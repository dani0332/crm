<script setup>

const props = defineProps({
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
  },
  quote_type_id: {
    type: Number,
    required: true  
  }
})

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date => date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const optionError = ref(false);

const sendUpdatesTable = reactive({
  headers: [
    { text: 'SU-REF ID', value: 'code', tooltip: 'A unique reference identifier assigned to each "Send Update" request, allowing for easy tracking and reference.' },
    { text: 'Type', value: 'type', tooltip: 'The type of "Send Update" request, categorizing the nature of the action being taken.' },
    { text: 'Sub Type', value: 'sub_type', tooltip: 'A further classification of the "Send Update" request, providing additional context or details.' },
    { text: 'Status', value: 'status', tooltip: 'The current status of the "Send Update" request, indicating whether it is pending, transaction approved, or declined, among other possible states.' },
    { text: 'Created date', value: 'created_at', tooltip: 'The date when the "Send Update" request was created. It indicates when the action was initiated' },
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
  quote_type_id: null,
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

watch(
  () => form.option, 
  (value) => {
    if (value !== null && optionError.value) {
      optionError.value = false
    }
  },
  { deep: true, immediate: true }
)

onMounted(() => {
  // fetchLogs();
})

// const fetchLogs = () => {
//   axios.get(route('send-update-logs.get-by-id', { id: props.reportableId }))
//     .then(res => sendUpdatesTable.data = res.data.logs)
//     .catch(err => console.log('err', err))
// }

const setOption = (next_step, value) => {
  switch (next_step) {
    case 'step1':
      form.parentCategory = null;
      modals.step = next_step
      break;
    case 'step2':
      form.parentCategory = value
      modals.step = next_step
      break;
    case 'step3':
      form.childCategory = value
      modals.step = next_step

      if (['CPD', 'CPU'].includes(form.childCategory.slug)) {
        modals.step = 'step1';
        modals.show = false;
        onAddUpdate(true);
      }

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

const onAddUpdate = (autoSubmit) => {
  if (! autoSubmit) {
    if (form.option === null) {
      optionError.value = true;
      return;
    }
  }

  optionError.value = false;

  form
    .transform(data => ({
      ...data,
      quote_type_id: props.quote_type_id,
      reportable_type: `App\\Models\\${data.reportable_type}`,
      refURL: page.url,
    }))
    .post(route('send-update-logs.store'), {
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
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Send Update
          </h3>
        </div>
      </template>
      <template #body>
        <div class="my-4 flex justify-end">
          <x-button
            v-if="can(permissionsEnum.SEND_UPDATE_CREATE)"
            size="sm"
            color="orange"
            @click="modals.show = true"
          >
            Add Update
          </x-button>
        </div>
        <DataTable
          table-class-name="tablefixed compact w-100"
          :headers="sendUpdatesTable.headers"
          :items="sendUpdatesTable.data"
          border-cell
          hide-rows-per-page
          :rows-per-page="10"
          :hide-footer="sendUpdatesTable.data.length <= 10"
        >
          <template #header-code="{ text, tooltip }">
            <x-tooltip position="right">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-type="{ text, tooltip }">
            <x-tooltip position="bottom">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-sub_type="{ text, tooltip }">
            <x-tooltip position="bottom">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-status="{ text, tooltip }">
            <x-tooltip position="bottom">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-created_at="{ text, tooltip }">
            <x-tooltip position="left">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #item-code="{ code, uuid }">
            <Link :href="route('send-update-logs.show', {uuid: uuid, refURL: $page.url})" replace class="text-primary-800 underline">{{ code }}</Link>
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
    </Collapsible>

    <x-modal v-model="modals.show" size="lg" show-close backdrop @update:modelValue="resetForm">
      <template #header>
        <div class="flex gap-3">
          <x-icon icon="prev" size="md" class="text-primary-800 mt-1 cursor-pointer" @click="goBack" v-if="modals.step !== 'step1'"/>
          <span class="text-primary-800 font-semibold">
            Add Update
          </span>
        </div>
      </template>

      <!-- modal 1 -->
      <div class="w-full flex gap-5 justify-center text-center my-10 mb-20 items-stretch" v-if="modals.step === 'step1'">
        <template v-for="option in options" :key="option.title">
          <x-tooltip position="bottom" class="arrow-t">
            <x-button color="primary" class="py-8 px-6 rounded-xl w-[200px] whitespace-break-spaces" @click="setOption('step2', option)">
              {{ option.title }}
            </x-button>
            <template #tooltip> <div class="truncate w-40">{{ option.description }}</div> </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 2 -->
      <div class="w-full flex gap-5 justify-center text-center py-5 items-stretch" v-else-if="modals.step === 'step2'">
        <template v-for="category in form.parentCategory?.childs" :key="category.title">
          <x-tooltip position="bottom" class="arrow-t">
            <x-button color="primary" class="py-8 px-6 rounded-xl w-[200px] whitespace-break-spaces" @click="setOption('step3', category)">
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
            <ComboBox
              v-model="form.option"
              :single="true"
              :hasError="optionError"
              :options="form.childCategory.childs.map(item => ({ label: item.title, value: item.id, tooltip: item.tooltip }))"
              :rules="[isRequired]"
              :placeholder="['EF', 'EN'].includes(form.childCategory.slug) ? 'Select Subtype' : 'Select Reason'"
              class="w-full"
            />
          </x-field>
          <div class="flex justify-end mt-2">
            <x-button 
              size="sm" 
              color="primary" 
              @click="onAddUpdate(false)"
              :disabled="form.processing"
              :loading="form.processing"
            >
              Add
            </x-button>  
          </div>
        </div>
      </div>
    </x-modal>
  </div>
</template>