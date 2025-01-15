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
  if (!markdownString) {
    return 'no content available.';
  }
  // Parse the markdown string
  const initialHtml = md.render(markdownString);

  // Adjust links to open in a new tab
  const adjustedHtml = initialHtml.replace(/<a /g, '<a target="_blank" ');

  return adjustedHtml;
};

const checkCaption = (whatsapp_request, UserAudio) => {
  const data =
    whatsapp_request?.document ??
    whatsapp_request?.location ??
    whatsapp_request?.contacts ??
    whatsapp_request?.video ??
    whatsapp_request?.image ??
    whatsapp_request?.sticker;

  if (data) {
    const { caption, mime_type } = JSON.parse(data);
    if (whatsapp_request && whatsapp_request?.type === 'image') {
      return caption
        ? `User has shared an image with a message: ${caption}`
        : `User has shared an image`;
    }
    return caption
      ? `User has shared a ${mime_type} with a message: ${caption}`
      : `User has shared a ${whatsapp_request.type}`;
  }

  const { blob_payload_type } = whatsapp_request;

  const message =
    whatsapp_request?.payload?.body?.image?.text ??
    whatsapp_request?.payload?.body?.document?.text ??
    whatsapp_request?.payload?.body?.file?.text ??
    whatsapp_request?.payload?.body?.location?.text ??
    whatsapp_request?.payload?.body?.contacts?.text ??
    whatsapp_request?.payload?.body?.video?.text ??
    whatsapp_request?.payload?.body?.sticker?.text;

  if (blob_payload_type === 'audio' && UserAudio != null && UserAudio != '') {
    return `${UserAudio}`;
  }

  if (blob_payload_type === 'image') {
    return message
      ? `User has shared an ${blob_payload_type} with a message: ${message}`
      : blob_payload_type
        ? `User has shared an ${blob_payload_type}`
        : false;
  }

  return message
    ? `User has shared a ${blob_payload_type} with a message: ${message}`
    : blob_payload_type
      ? `User has shared a ${blob_payload_type}`
      : false;
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
    <template #header> Ref ID: {{ chatMessages.id }} </template>
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
                v-if="message.channel && typeof message.channel === 'string'"
                class="py-[4px] rounded-md px-4 text-white ml-2 text-xs capitalize"
                :class="{
                  'bg-sky-400': message.channel.toLowerCase() === 'website',
                  'bg-orange-600': message.channel.toLowerCase() === 'email',
                  'bg-emerald-400':
                    message.channel.toLowerCase() !== 'website' &&
                    message.channel.toLowerCase() !== 'email',
                }"
                >{{ message.channel.toLowerCase() }}</span
              >
            </div>
            <div class="chat-bubble text-sm relative flex items-center">
              <div
                v-if="
                  message.whatsapp_request
                    ? checkCaption(message.whatsapp_request, message.msg)
                    : false
                "
              >
                <span>
                  {{
                    checkCaption(message.whatsapp_request, message.msg)
                  }}</span
                >
              </div>

              <SanitizeHtml
                :key="message.id"
                v-else="message.msg"
                :html="renderMarkdown(message.msg)"
              />
              <div
                class="absolute right-[-30px] text-red-600"
                v-if="
                  message?.whatsapp_request?.type == 'audio' ||
                  message?.whatsapp_request?.type == 'voice'
                "
              >
                <x-icon icon="audio" />
              </div>
            </div>

            <div class="chat-footer opacity-50 text-right">
              {{ message.created_at }}
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
              <SanitizeHtml :html="renderMarkdown(message.msg)" />
            </div>
            <div class="chat-footer opacity-50">
              {{ message.created_at }}
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
