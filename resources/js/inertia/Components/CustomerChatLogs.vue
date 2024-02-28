<script setup>
import axios from 'axios';
import { onMounted } from 'vue';
const props = defineProps({
  quoteId: {
    type: [Number, String],
    required: true,
  },
  customerName: {
    type: String,
  },
});

const showChatLogs = ref(false);
const formatted = date => useDateFormat(date, 'hh:mm:ss A').value;
const loader = ref(false);
const tableHeaders = reactive([
  {
    text: 'Created At',
    value: 'created_at',
  },
  {
    text: 'View',
    value: 'action',
  },
]);

const tableData = ref([]);

const chatMessages = ref({
  created_at: '',
  data: [],
});

const formatData = rawData => {
  for (const data of rawData) {
    // Find if the createddate already exists in formattedData
    const existingEntry = tableData.value.find(
      entry => entry.created_at === data.created_at.split('T')[0],
    );

    if (existingEntry) {
      // If exists, push the current data into the existing entry
      existingEntry.data.push({ ...data });
    } else {
      // If not, create a new entry
      tableData.value.push({
        created_at: data.created_at.split('T')[0],
        data: [{ ...data }],
      });
    }
  }
};
const getAllChat = () => {
  loader.value = true;
  axios.post('/get-alfred-chat', { quoteId: props.quoteId }).then(response => {
    let { data } = { ...response.data };
    loader.value = false;
    if (data && data.length > 0) formatData(data);
  });
};

const showChat = item => {
  chatMessages.value.created_at = item.created_at;
  chatMessages.value.data = tableData.value.find(
    entry => entry.created_at === item.created_at,
  ).data;
  showChatLogs.value = true;
};

onMounted(async () => {
  await getAllChat();
});
</script>
<template>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center">
        <h3 class="font-semibold text-primary-800 text-lg">
          InstantAlfred Chat Logs
        </h3>
      </div>
      <x-divider class="mb-4 mt-1"></x-divider>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="tableHeaders"
        :items="tableData || []"
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-action="item">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click="showChat(item)"
            :loading="loader"
          >
            View
          </x-button>
        </template>
      </DataTable>
    </div>

    <x-modal v-model="showChatLogs" backdrop size="xl">
      <template #header> Created At : {{ chatMessages.created_at }} </template>

      <div class="flex flex-col space-y-4">
        <div
          v-for="(message, index) in chatMessages.data"
          :key="index"
          :class="{
            'flex items-start': message.role == 'USER',
            'flex justify-end': message.role == 'AI',
          }"
        >
          <div>
            <p class="text-sm mb-2 text-gray-500" v-if="message.role == 'USER'">
              {{ customerName ??  message.role }}
            </p>
            <p class="text-sm mb-2 text-end text-gray-500" v-else>
              InstantAlfred
            </p>
            <div
              :class="{
                'bg-blue-500': message.role == 'USER',
                'bg-success-500 text-white': message.role == 'AI',
              }"
              class="rounded-[20px] relative max-w-[45rem]"
            >
              <p class="text-sm text-white py-4 px-4">
                {{ message.msg }}
                <p class="text-xs text-white text-right">
                {{ formatted(message.created_at) }}
              </p>
              </p>
             
              <div
                :class="{
                  'bg-blue-500 left-[-16px]': message.role == 'USER',
                  'bg-success-500 right-[-16px] rotate-180':
                    message.role == 'AI',
                }"
                class="absolute border-t-[6px] border-b-[6px] border-r-[17px] border-t-white border-b-white border-r-transparent h-0 w-0 top-3.5"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </x-modal>
  </div>
</template>