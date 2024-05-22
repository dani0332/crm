<script setup>
import { computed } from 'vue';

const props = defineProps({
  reportable: {
    type: Object,
    required: true,
  },
  data: {
    type: Array,
    required: true,
    default: () => [],
  },
  options: {
    type: Array,
    required: true,
  },
  quote_type_id: {
    type: Number,
    required: true,
  },
});

const page = usePage();
const { isRequired } = useRules();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const optionError = ref(false);

const sendUpdatesTable = reactive({
  headers: [
    {
      text: 'SU REF ID',
      value: 'code',
      tooltip:
        'A unique reference identifier assigned to each "Send Update" request, allowing for easy tracking and reference.',
    },
    {
      text: 'Type',
      value: 'type',
      tooltip:
        'The type of "Send Update" request, categorizing the nature of the action being taken.',
    },
    {
      text: 'Sub Type',
      value: 'sub_type',
      tooltip:
        'A further classification of the "Send Update" request, providing additional context or details.',
    },
    {
      text: 'Status',
      value: 'status',
      tooltip:
        'The current status of the "Send Update" request, indicating whether it is pending, transaction approved, or declined, among other possible states.',
    },
    {
      text: 'Created date',
      value: 'created_at',
      tooltip:
        'The date when the "Send Update" request was created. It indicates when the action was initiated',
    },
  ],
  data: props.data,
});

const modals = reactive({
  step: 'step1',
  show: false,
  confirm: false,
});

watch(
  () => modals.show,
  value => {
    if (!value) {
      resetForm();
    }
  }
);

const form = useForm({
  parentCategory: null,
  childCategory: null,
  option: null,
  quote_type_id: null,
});

const resetForm = () => {
  (form.parentCategory = null),
    (form.childCategory = null),
    (form.option = null);
  modals.step = 'step1';
};

watch(
  () => form.option,
  value => {
    if (value !== null && optionError.value) {
      optionError.value = false;
    }
  },
  { deep: true, immediate: true },
);

onMounted(() => {
  // fetchLogs();
});

const authenticatedSendUpdateOptions = computed(() => {
  let filteredOptions = props.options;

  if (!(can(permissionsEnum.SEND_UPDATE_ENDO_FIN_ADD) || can(permissionsEnum.SEND_UPDATE_ENDO_NON_FIN_ADD))) {
    // it will remove the main Button. 
    filteredOptions = filteredOptions.filter((option, index) => index !== 0);
  } else {
    if (!can(permissionsEnum.SEND_UPDATE_ENDO_FIN_ADD)) {
      // it will remove only sub button. 
      filteredOptions[0].childs = filteredOptions[0]?.childs.filter((option, index) => index !== 0);
    }
    if (!can(permissionsEnum.SEND_UPDATE_ENDO_NON_FIN_ADD)) {
      // it will remove only sub button. 
      filteredOptions[0].childs = filteredOptions[0]?.childs.filter((option, index) => index !== 1);
    }
  }

  if (!(can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD) || can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD))) {
    filteredOptions = filteredOptions.filter((option, index) => index !== 1);
  } else {
    if (!can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD)) {
      filteredOptions[1].childs = filteredOptions[1]?.childs.filter((option, index) => index !== 0);
    }
    if (!can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD)) {
      filteredOptions[1].childs = filteredOptions[1]?.childs.filter((option, index) => index !== 1);
    }
  }

  if (!(can(permissionsEnum.SEND_UPDATE_CORRECT_POLICY_UPLOAD_ADD) || can(permissionsEnum.SEND_UPDATE_CORRECT_POLICY_DETAILS_ADD))) {
    filteredOptions = filteredOptions.filter((option, index) => index !== 2);
  } else {
    if (!can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_ADD)) {
      filteredOptions[2].childs = filteredOptions[2]?.childs.filter((option, index) => index !== 0);
    }
    if (!can(permissionsEnum.SEND_UPDATE_CANCEL_FROM_INCEPTION_AND_REISSUE_ADD)) {
      filteredOptions[2].childs = filteredOptions[2]?.childs.filter((option, index) => index !== 1);
    }
  }

  return filteredOptions;
});

// const fetchLogs = () => {
//   axios.get(route('send-update-logs.get-by-id', { id: props.reportableId }))
//     .then(res => sendUpdatesTable.data = res.data.logs)
//     .catch(err => console.log('err', err))
// }

const setOption = (next_step, value) => {
  switch (next_step) {
    case 'step1':
      form.parentCategory = null;
      modals.step = next_step;
      break;
    case 'step2':
      form.parentCategory = value;
      modals.step = next_step;
      break;
    case 'step3':
      form.childCategory = value;
      modals.step = next_step;

      if (['CPD', 'CPU'].includes(form.childCategory.slug)) {
        modals.step = 'step1';
        modals.show = false;
        onAddUpdate(true);
      }

      break;

    default:
      modals.step = 'step1';
      modals.show = false;
      modals.confirm = false;
      break;
  }
};

const goBack = () => {
  if (modals.step === 'step2') {
    modals.step = 'step1';
    form.parentCategory = null;
  } else if (modals.step === 'step3') {
    modals.step = 'step2';
    form.childCategory = null;
    form.option = null;
    optionError.value = false;
  } else if (modals.step === 'step4') {
    modals.step = 'step3';
    modals.confirm = false;
  }
};

const confirmOrAddUpdate = autoSubmit => {
    if (!autoSubmit) {
        if (form.option === null) {
            optionError.value = true;
            return;
        }
    }
    if (!['CIR'].includes(form.childCategory.slug)) {
        onAddUpdate(true);
    }else {
        modals.step = 'step4';
        modals.confirm = true;
        optionError.value = false;
    }

};

const onAddUpdate = autoSubmit => {
  if (!autoSubmit) {
    if (form.option === null) {
      optionError.value = true;
      return;
    }
  }

  optionError.value = false;

  form
    .transform(data => {
      let childCatgeory = { ...data.childCategory };
      let option = childCatgeory.childs.find(item => item.id === data.option);
      childCatgeory.option = option || null;
      delete childCatgeory.childs;

      return {
        quote_type_id: props.quote_type_id,
        refURL: page.url,
        childCategory: childCatgeory,
        option_id: option?.id || null,
        quote_uuid: props.reportable.uuid,
        status: page.props.sendUpdateEnum.NEW_REQUEST,
        ref_id: props.reportable.id,
      };
    })
    .post(route('send-update-logs.store'), {
      onSuccess: () => {
        modals.step = 'step1';
        form.parentCategory = null;
        form.childCategory = null;
        form.option = null;
        optionError.value = false;
        modals.show = false;
        modals.confirm = false;

        // resetForm()
        // sendUpdatesTable.data = [...sendUpdatesTable.data, form.data]
      },
    });
};

const findOption = (item, key) => {
  let title = '';
  props.options.forEach(option => {
    if (option.id === item[key]) {
      title = option.title;
    } else {
      option?.childs?.forEach(child => {
        if (child.id === item[key]) {
          title = child.title;
        } else {
          child?.childs?.forEach(grandChild => {
            if (grandChild.id === item[key]) {
              title = grandChild.title;
            }
          });
        }
      });
    }
  });
  return title;
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible expanded>
      <template #header>
        <div class="flex justify-between gap-4 items-center">
          <h3 class="font-semibold text-primary-800 text-lg">Send Update</h3>
        </div>
      </template>
      <template #body>
        <div class="mt-4 flex justify-end">
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
          table-class-name="table-fixed-width mt-4"
          :headers="sendUpdatesTable.headers"
          :items="sendUpdatesTable.data"
          border-cell
          hide-rows-per-page
          :rows-per-page="10"
          :hide-footer="sendUpdatesTable.data.length <= 10"
        >
          <template #header-code="{ text, tooltip }">
            <x-tooltip align="left" position="right">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span
                  class="whitespace-break-spaces !normal-case"
                  style="margin-top: 50px"
                >
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-type="{ text, tooltip }">
            <x-tooltip align="left" position="right">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-sub_type="{ text, tooltip }">
            <x-tooltip align="left" position="right">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-status="{ text, tooltip }">
            <x-tooltip align="left" position="right">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #header-created_at="{ text, tooltip }">
            <x-tooltip align="left" position="bottom">
              <span class="underline decoration-dotted">{{ text }}</span>
              <template #tooltip>
                <span class="whitespace-break-spaces !normal-case">
                  {{ tooltip }}
                </span>
              </template>
            </x-tooltip>
          </template>
          <template #item-code="{ code, uuid }">
            <Link
              :href="
                route('send-update-logs.show', {
                  uuid: uuid,
                  refURL: $page.url,
                })
              "
              class="text-primary-800 underline"
              >{{ code }}</Link
            >
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

    <AppModal
      class="h-auto md:overflow-visible max-sm:overflow-auto"
      v-model="modals.show"
      show-close
      backdrop
      show-header
    >
      <template #header>
        <div class="flex gap-3">
          <x-icon
            icon="prev"
            size="md"
            class="text-primary-800 mt-1 cursor-pointer"
            @click="goBack"
            v-if="modals.step !== 'step1'"
          />
          <span class="text-primary-800 font-semibold"> Add Update </span>
        </div>
      </template>

      <!-- modal 1 -->
      <div
        class="w-full flex flex-wrap gap-5 justify-center text-center my-10 mb-20 items-stretch !h-100"
        v-if="modals.step === 'step1'"
      >
        <template v-for="option in authenticatedSendUpdateOptions" :key="option.title">
          <x-tooltip align="left" position="bottom" class="arrow-t">
            <x-button
              color="primary"
              class="py-8 px-6 rounded-xl min-h-[150px] w-[200px] whitespace-break-spaces underline decoration-dotted !h-100"
              @click="setOption('step2', option)"
            >
              {{ option.title }}
            </x-button>
            <template #tooltip>
              <span class="">
                {{ option.description }}
              </span>
            </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 2 -->
      <div
        class="w-full flex gap-5 justify-center text-center py-5 items-stretch"
        v-else-if="modals.step === 'step2'"
      >
        <template
          v-for="category in form.parentCategory?.childs"
          :key="category.title"
        >
          <x-tooltip position="bottom" class="arrow-t">
            <x-button
              color="primary"
              class="py-8 px-6 rounded-xl w-[200px] min-h-[150px] whitespace-break-spaces underline decoration-dotted"
              @click="setOption('step3', category)"
            >
              {{ category.title }}
            </x-button>
            <template #tooltip>
              <div>{{ category.description }}</div>
            </template>
          </x-tooltip>
        </template>
      </div>

      <!-- modal 3 -->
      <div
        class="w-full flex gap-5 mb-10"
        v-else-if="
          modals.step === 'step3' && form.childCategory?.childs?.length > 0
        "
      >
        <div class="flex flex-col gap-2 flex-grow w-75">
          <x-field :label="form.childCategory.title" required>
            <ComboBox
              v-model="form.option"
              :single="true"
              :hasError="optionError"
              :options="
                form.childCategory.childs.map(item => ({
                  label: item.title,
                  value: item.id,
                  tooltip: item.description,
                }))
              "
              :rules="[isRequired]"
              :placeholder="
                ['EF', 'EN'].includes(form.childCategory.slug)
                  ? 'Select Subtype'
                  : 'Select Reason'
              "
              class="w-full"
            />
            <!-- <x-select
              class="w-full"
              v-model="form.option"
              :options="form.childCategory.childs.map(item => ({ label: item.title, value: item.id, tooltip: item.tooltip }))"
              :placeholder="['EF', 'EN'].includes(form.childCategory.slug) ? 'Select Subtype' : 'Select Reason'"
              :rules="[isRequired]"
            /> -->
          </x-field>
          <div class="flex justify-end mt-2">
            <x-button
              size="sm"
              color="primary"
              @click="confirmOrAddUpdate(false)"
            >
              Add
            </x-button>
          </div>
        </div>
      </div>
<!--   Modal 4 - Confirmation and causation     -->
        <div
            class="w-full flex gap-5 mb-10"
            v-else-if="modals.step === 'step4' && modals.confirm === true "
        >
            <div class="flex flex-col gap-2 flex-grow w-75">
                <div>
                    <p class="text-red-600 font-bold text-2xl"> Please note the following when performing these updates:</p>
                    <div class="my-4">
                        <ul class="list-disc pl-4 font-medium">
                            <li>The new lead for the reissued policy counts as a sale only once the lead status is policy issued</li>
                            <li class="my-2">When a policy is cancelled and reissued or simply cancelled, it will no longer count as sale.</li>
                            <li>Lead details will be moved to new lead.</li>
                        </ul>
                    </div>

                </div>
                <div class="flex justify-end mt-2">
                    <x-button
                        class="mr-2"
                        size="sm"
                        color="primary"
                        @click="onAddUpdate(false)"
                        :disabled="form.processing"
                        :loading="form.processing"
                    >
                        Confirm
                    </x-button>
                    <x-button
                        size="sm"
                        color="primary"
                        :disabled="form.processing"
                        :loading="form.processing"
                        @click="setOption('step3', form.childCategory)"
                    >
                        Cancel
                    </x-button>

                </div>
            </div>
        </div>

    </AppModal>
  </div>
</template>
