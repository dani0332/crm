<script setup>
const showChatLogs = ref(false);
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

const tableData = reactive([
  { created_at: '2022-01-01', time: '10:00 AM' },
  { created_at: '2022-01-02', time: '12:30 PM' },
  { created_at: '2022-01-03', time: '03:45 PM' },
  // Add more data as needed
]);

const chatMessages = ref([
  {
    text: 'Hello, AlfredInstant! How can I help you?',
    isCustomer: true,
    customerName: 'John',
  },
  { text: 'Hi John! How can I assist you today?', isCustomer: false },
  {
    text: 'I am facing issues with my account',
    isCustomer: true,
    customerName: 'John',
  },
  {
    text: 'I am sorry to hear that. Let me check your account',
    isCustomer: false,
  },
  { text: 'Thank you', isCustomer: true, customerName: 'John' },
  { text: 'Hi there! How can I assist you today?', isCustomer: false },
]);
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
            @click="showChatLogs = !showChatLogs"
          >
            View
          </x-button>
        </template>
      </DataTable>
    </div>

    <x-modal v-model="showChatLogs" backdrop size="xl">
      <template #header> Created At : 2-21-2024 </template>

      <div class="flex flex-col space-y-4">
        <div
          v-for="(message, index) in chatMessages"
          :key="index"
          :class="{
            'flex items-start': message.isCustomer,
            'flex justify-end': !message.isCustomer,
          }"
        >
          <div>
            <p class="text-sm mb-2 text-gray-500" v-if="message.isCustomer">
              {{ message.customerName }}
            </p>
            <p class="text-sm mb-2 text-end text-gray-500" v-else>
              InstantAlfred
            </p>
            <div
              :class="{
                'bg-blue-500': message.isCustomer,
                'bg-success-500 text-white': !message.isCustomer,
              }"
              class="rounded-[20px] relative max-w-[45rem]"
            >
              <p class="text-sm text-white py-4 px-4">
                {{ message.text }}
              </p>
              <div
                :class="{
                  'bg-blue-500 left-[-16px]': message.isCustomer,
                  'bg-success-500 right-[-16px] rotate-180':
                    !message.isCustomer,
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