<script setup>
import markdownit from 'markdown-it';
const props = defineProps({
  chatMessages: {
    type: Object,
    required: true,
  },
  quoteType: {
    type: String,
  },
  customerName: {
    type: String,
    default: 'User',
  },
  showChatLogs: {
    type: Boolean,
    required: true,
  },
});

const source = ref(null);

const md = new markdownit();

const computedMessages = computed(() => {
  if (source.value && source.value.length > 0)
    return props.chatMessages.data.filter(
      message => message.role.toLowerCase() === source.value.toLowerCase(),
    );
  else return props.chatMessages.data;
});

const renderMarkdown = markdownString => {
  // Parse the markdown string
  const initialHtml = md.render(markdownString);

  // Adjust links to open in a new tab
  const adjustedHtml = initialHtml.replace(/<a /g, '<a target="_blank" ');

  return adjustedHtml;
};
</script>
<template>
  <AppModal
    class="max-w-6xl"
    :modelValue="showChatLogs"
    show-close
    :backdropClose="false"
    show-header
    @update:modelValue="$emit('update:showChatLogs', $event)"
  >
    <template #header>
      Ref ID: {{ chatMessages.id }} - Created At:
      {{ chatMessages.created_at.split(' ')[0] }}
    </template>
    <template #default>
      <div>
        <x-field label="Message Source" required>
          <combo-box
            v-model="source"
            :options="[
              { label: 'User', value: 'User' },
              { label: 'AI', value: 'AI' },
            ]"
            placeholder="Select a source"
            class="w-full"
            single
          >
          </combo-box>
        </x-field>
      </div>
      <x-divider class="my-3" />
      <div v-for="(message, index) in computedMessages" :key="index">
        <div class="chat chat-start" v-if="message.role == 'USER'">
          <div class="chat-image avatar">
            <div
              class="flex items-center justify-center border rounded-full h-10 w-10 bg-gray-600 text-white"
            >
              <span v-if="customerName && customerName != 'User'">
                {{
                  customerName.split(' ')[0].charAt(0) +
                  customerName.split(' ')[1].charAt(0)
                }}
              </span>
              <span v-else>{{ customerName.charAt(0) }}</span>
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
    </template>
  </AppModal>
</template>
