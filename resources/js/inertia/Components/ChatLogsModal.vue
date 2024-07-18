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

const source = ref([]);
const channel = ref([]);

const md = new markdownit();

const computedMessages = computed(() => {
  if (source.value && source.value.length > 0) {
    return props.chatMessages.data.filter(
      message => message.role.toLowerCase() === source.value[0].toLowerCase(),
    );
  } else if (channel.value && channel.value.length > 0) {
    return props.chatMessages.data.filter(
      message =>
        message.channel.toLowerCase() === channel.value[0].toLowerCase(),
    );
  } else return props.chatMessages.data;
});

const renderMarkdown = markdownString => {
  if(!markdownString){
    return 'no content available.'
  }
  // Parse the markdown string
  const initialHtml = md.render(markdownString);

  // Adjust links to open in a new tab
  const adjustedHtml = initialHtml.replace(/<a /g, '<a target="_blank" ');

  return adjustedHtml;
};
</script>
<template>
  <AppModal
    class="max-w-6xl md:min-w-[1000px]"
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
            :maxLimit="1"
          >
          </combo-box>
        </x-field>
        <x-field label="Message Channel" required>
          <combo-box
            v-model="channel"
            :options="[
              { label: 'Whatsapp', value: 'Whatsapp' },
              { label: 'Website', value: 'Website' },
              { label: 'Email', value: 'Email' },
            ]"
            placeholder="Select a Channel"
            class="w-full"
            :maxLimit="1"
          >
          </combo-box>
        </x-field>
      </div>
      <x-divider class="my-3" />
      <template v-if="computedMessages.length > 0">
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
              <span
                v-if="message.channel"
                class="py-[4px] rounded-md px-4 text-white ml-2 text-xs capitalize"
                :class="
                  message.channel.toLowerCase() == 'website'
                    ? 'bg-sky-400'
                    : 'bg-emerald-400'
                "
                >{{ message.channel.toLowerCase() }}</span
              >
            </div>
            <div class="chat-bubble text-sm relative flex items-center">
              <div v-if="message.whatsapp_request?.type.toLowerCase() === 'image'">
                <span>User has shared an image</span>
              </div>
              <div v-else-if="['document', 'location', 'contacts', 'video', 'sticker'].includes(message.whatsapp_request?.type.toLowerCase())">
                <span>User has shared a {{message.whatsapp_request.type}}</span>
              </div>
              <div v-else="message.msg" v-html="renderMarkdown(message.msg)"></div>
              <div
                class="absolute right-[-30px] text-red-600"
                v-if="message?.whatsapp_request?.type == 'audio'||  message?.whatsapp_request?.type == 'voice'"
              >
                <x-icon icon="audio" />
              </div>
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
      <template v-else>
        <div class="flex items-center justify-center h-60">
          <x-icon icon="chat" class="h-12 w-12 text-gray-400" />
          <span class="text-gray-400 text-lg ml-2">No Chat Logs Found</span>
        </div>
      </template>
    </template>
  </AppModal>
</template>
