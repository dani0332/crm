<script setup>
import markdownit from 'markdown-it';
const props = defineProps({
  quoteId: {
    type: [Number, String],
    required: true,
  },
  quoteType: {
    type: String,
  },
  customerName: {
    type: String,
  },
});

const showChatLogs = ref(false);

const md = new markdownit();

const renderMarkdown = markdownString => {
  // Parse the markdown string
  const initialHtml = md.render(markdownString);

  // Adjust links to open in a new tab
  const adjustedHtml = initialHtml.replace(/<a /g, '<a target="_blank" ');

  return adjustedHtml;
};

// const markdownOptions = ref({
//   html: true,
//   linkify: true,
//   typographer: true,
//   breaks: true,
//   link_open: function (tokens, idx, options, env, self) {
//     console.log('here to get the link');
//     const token = tokens[idx];
//     const hrefIndex = token.attrIndex('href');
//     if (hrefIndex !== -1) {
//       // Add target="_blank" to the link
//       token.attrs[hrefIndex][1] += ' " target="_blank"';
//     }
//     return self.renderToken(tokens, idx, options, env, self);
//   },
// });

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

// const tableData = reactive([
//   {
//     created_at: '2022-03-15 08:30:00',
//     action: 'View Details',
//   },
//   {
//     created_at: '2022-03-16 11:45:00',
//     action: 'View Details',
//   },
//   {
//     created_at: '2022-03-17 14:15:00',
//     action: 'View Details',
//   },
//   // Add more fake data as needed
// ]);

const chatMessages = ref({
  created_at: '',
  data: [],
});

// const chatMessages = ref({
//   created_at: '2022-03-01 12:30:00',
//   data: [
//     {
//       role: 'USER',
//       customerName: 'John',
//       msg: 'Hello there!',
//       created_at: '2022-03-01 12:30:00',
//     },
//     {
//       _id: '65dedc3a1e758b05af67f299',
//       role: 'AI',
//       msg: "For LIVA Insurance (previously known as RSA Insurance/Royal and Sun Alliance), we have the following Third Party Liability (TPL) plan available:\n\n- Third Party Only premium: 612 +VAT. [Payment Link](https://testing.alfred.ae/car-insurance/quote/Q5R9YUSG/payment/?planId=1&providerCode=RSA)\n\nTPL plans, also known as Third Party Insurance, cover damages to another person's vehicle or property or injuries to other people in an accident that you're found responsible for. They do not cover damages to your own vehicle. \n\nWould you like to proceed with this plan?",
//       created_at: '28-Feb-2024 07:09am',
//     },
//     {
//       role: 'USER',
//       customerName: 'Alice',
//       msg: 'I have a question.',
//       created_at: '2022-03-01 12:35:00',
//     },
//     {
//       role: 'AI',
//       msg: 'Sure, go ahead and ask.',
//       created_at: '2022-03-01 12:38:00',
//     },
//     // Add more fake data as needed
//   ],
// });

const formatData = rawData => {
  for (const data of rawData) {
    // Find if the createddate already exists in formattedData
    const existingEntry = tableData.value.find(
      entry => entry.created_at === data.created_at.split(' ')[0],
    );

    if (existingEntry) {
      // If exists, push the current data into the existing entry
      existingEntry.data.push({ ...data });
    } else {
      // If not, create a new entry
      tableData.value.push({
        created_at: data.created_at.split(' ')[0],
        data: [{ ...data }],
      });
    }
  }
};

const getAllChat = () => {
  loader.value = true;
  axios
    .post('/get-alfred-chat', {
      quoteId: props.quoteId,
      quoteType: props.quoteType,
    })
    .then(response => {
      let { data } = { ...response.data };
      loader.value = false;
      if (data && data.length > 0) formatData(data);
    })
    .catch(error => {
      loader.value = false;
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

    <AppModal
      class="max-w-6xl"
      v-model="showChatLogs"
      show-close
      :backdropClose="false"
      show-header
    >
      <template #header>
        Created At : {{ chatMessages.created_at.split(' ')[0] }}
      </template>
      <template #default>
        <div v-for="(message, index) in chatMessages.data" :key="index">
          <div class="chat chat-start" v-if="message.role == 'USER'">
            <div class="chat-image avatar">
              <div class="w-8 rounded-full">
                <img
                  class="rounded-full"
                  alt="Tailwind CSS chat bubble component"
                  src="/image/alfred-theme.png"
                />
              </div>
            </div>
            <div class="chat-header">
              {{ customerName ?? message.role }}
            </div>
            <div class="chat-bubble text-sm">
              <div v-html="renderMarkdown(message.msg)"></div>
            </div>
            <div class="chat-footer opacity-50 text-right">
              {{ message.created_at.split(' ')[1] }}
            </div>
          </div>
          <div class="chat chat-end" v-else>
            <div class="chat-image avatar">
              <div class="w-8 rounded-full">
                <img
                  class="rounded-full"
                  alt="Tailwind CSS chat bubble component"
                  src="/image/alfred-theme.png"
                />
              </div>
            </div>
            <div class="chat-header">InstantAlfred</div>
            <div class="chat-bubble text-sm">
              <div v-html="renderMarkdown(message.msg)"></div>
            </div>
            <div class="chat-footer opacity-50">
              {{ message.created_at.split(' ')[1] }}
            </div>
          </div>
        </div>

        <!-- <div class="flex flex-col space-y-4">
          <div
            v-for="(message, index) in chatMessages.data"
            :key="index"
            :class="{
              'flex items-start': message.role == 'USER',
              'flex justify-end': message.role == 'AI',
            }"
          >
            <div>
              <p
                class="text-sm mb-2 text-gray-500"
                v-if="message.role == 'USER'"
              >
                {{ customerName ?? message.role }}
              </p>
              <p class="text-sm mb-2 text-end text-gray-500" v-else>
                InstantAlfred
              </p>
              <div
                :class="{
                  'bg-blue-500': message.role == 'USER',
                  'bg-success-500 text-white': message.role == 'AI',
                  'mr-16': message.role == 'USER',
                }"
                class="rounded-[20px] relative max-w-[45rem]"
              >
                <div class="text-sm text-white py-4 px-4">
                  <vue-markdown :source="message.msg"></vue-markdown>
                </div>

                <p class="text-xs text-white text-right uppercase">
                  {{ message.created_at.split(' ')[1] }}
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
        </div> -->
      </template>
    </AppModal>
    <!-- <x-modal v-model="showChatLogs" backdrop size="xl">
      <template #header> Created At : {{ chatMessages.created_at.split(' ')[0] }} </template>

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
                <p class="text-xs text-white text-right uppercase">
                {{ message.created_at.split(' ')[1] }}
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
    </x-modal> -->
  </div>
</template>
<style>
.chat {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
  padding-top: 0.25rem;
}

.chat-image {
  grid-row: span 2;
  align-self: end;
}

.chat-header {
  grid-row: 1;
  font-size: 0.875rem;
}

.chat-footer {
  grid-row: 3;
  font-size: 0.875rem;
}

.chat-bubble {
  position: relative;
  display: block;
  width: -moz-fit-content;
  width: fit-content;
  padding: 0.5rem 1rem;
  max-width: 90%;
  border-radius: 1rem;
  min-height: 2.75rem;
  min-width: 2.75rem;
  @apply bg-primary-200;
}

.chat-bubble a {
  text-decoration: underline;
  font-weight: 900;
}
.chat-bubble:before {
  position: absolute;
  bottom: 0;
  height: 2rem;
  width: 0.85rem;
  background-color: inherit;
  content: '';
  -webkit-mask-size: contain;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-position: center;
  mask-position: center;
}

.chat-start {
  justify-items: start;
  grid-template-columns: auto 1fr;
}

.chat-start .chat-header,
.chat-start .chat-footer {
  grid-column: 2;
}

.chat-start .chat-image {
  grid-column: 1;
}

.chat-start .chat-bubble {
  grid-column: 2;
}

.chat-start .chat-bubble:before {
  inset-inline-start: -0.749rem;
  mask-image: url("data:image/svg+xml,%3csvg width='3' height='3' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill='black' d='m 0 3 L 3 3 L 3 0 C 3 1 1 3 0 3'/%3e%3c/svg%3e");
}

[dir='rtl'] .chat-start .chat-bubble:before {
  mask-image: url("data:image/svg+xml,%3csvg width='3' height='3' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill='black' d='m 0 3 L 1 3 L 3 3 C 2 3 0 1 0 0'/%3e%3c/svg%3e");
}

.chat-end {
  justify-items: end;
  grid-template-columns: 1fr auto;
}

.chat-end .chat-header,
.chat-end .chat-footer {
  grid-column: 1;
}

.chat-end .chat-image {
  grid-column: 2;
}

.chat-end .chat-bubble {
  grid-column: 1;
}

.chat-end .chat-bubble:before {
  inset-inline-start: 99.9%;
  height: 2.1rem;
  width: 0.85rem;
  mask-image: url("data:image/svg+xml,%3csvg width='3' height='3' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill='black' d='m 0 3 L 1 3 L 3 3 C 2 3 0 1 0 0'/%3e%3c/svg%3e");
}

[dir='rtl'] .chat-end .chat-bubble:before {
  mask-image: url("data:image/svg+xml,%3csvg width='3' height='3' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill='black' d='m 0 3 L 3 3 L 3 0 C 3 1 1 3 0 3'/%3e%3c/svg%3e");
}
</style>