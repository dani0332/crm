<script setup>
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
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const showChatLogs = ref(false);
const notification = useToast();

const formatted = date => useDateFormat(date, 'hh:mm:ss A').value;
const loader = ref(false);
const tableHeaders = reactive([
  {
    text: 'Created At',
    value: 'id',
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

const getAllChat = () => {
  loader.value = true;
  axios
    .post('/instant-alfred/chats', {
      quoteId: props.quoteId,
      quoteType: props.quoteType,
    })
    .then(response => {
      let { data } = { ...response.data };
      loader.value = false;
      tableData.value = data;
    })
    .catch(error => {
      loader.value = false;
    });
};

const showChat = item => {
  loader.value = true;
  axios
    .post('/instant-alfred/chats', {
      quoteId: props.quoteId,
      quoteType: props.quoteType,
    })
    .then(response => {
      if (response.data.message) {
        loader.value = false;
        notification.error({
          title: 'Chat not found',
          position: 'top',
        });
        return;
      }
      let { data } = { ...response.data };
      loader.value = false;
      chatMessages.value.data = data;
      chatMessages.value.id = props.quoteType + '-' + props.quoteId;
      showChatLogs.value = true;
    })
    .catch(error => {
      loader.value = false;
    });
};
</script>
<template>
  <div>
    <div class="p-4 rounded shadow mb-6 bg-white">
      <Collapsible :expanded="expanded">
        <template #header>
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-primary-800 text-lg">
              InstantAlfred Chat Logs
            </h3>
          </div>
        </template>
        <template #body>
          <x-divider class="mb-4 mt-1"></x-divider>
          <div class="text-center py-3">
            <x-button
              size="sm"
              color="primary"
              outlined
              @click.prevent="showChat()"
              :loading="loader"
            >
              Load Instant Chat
            </x-button>
          </div>
        </template>
      </Collapsible>

      <chat-logs-modal
        :showChatLogs="showChatLogs"
        :chatMessages="chatMessages"
        :customerName="customerName"
        @update:showChatLogs="showChatLogs = $event"
      />
    </div>
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
