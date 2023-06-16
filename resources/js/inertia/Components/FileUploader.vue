<script setup>
const props = defineProps({
  modelValue: {
    type: [String, File, Object],
    default: '',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  accept: {
    type: String,
    default: '*',
  },
  helper: {
    type: String,
    default: '',
  },
  uploadRoute: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: '',
  },
});
const emit = defineEmits(['update:modelValue']);
const toast = useToast();
const filesData = ref([]);
const dropZoneRef = ref();
function onDrop(files) {
  filesData.value = [];
  if (files) {
    if (!props.multiple && files.length > 1) {
      toast.error({
        title: 'You can only upload one file at a time',
        position: 'top',
      });
      return;
    }
    axios
      .post(
        route(props.uploadRoute),
        {
          file: files[0],
          title: props.title,
        },
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        },
      )
      .then(res => {
        if (res.data.path) {
          toast.success({
            title: 'File uploaded successfully',
            position: 'top',
          });
          emit('update:modelValue', res.data.path);
        } else {
          toast.error({
            title: 'There is an issue with the file',
            position: 'top',
          });
          return;
        }
        filesData.value = files.map(file => ({
          name: file.name,
          size: file.size,
          type: file.type,
          blob: useObjectUrl(file),
        }));
      });
  }
}
const { isOverDropZone } = useDropZone(dropZoneRef, onDrop);
const { files, open, reset } = useFileDialog({
  multiple: props.multiple,
  accept: props.accept,
});
watch(files, async filesFromFileDialog => {
  if (filesFromFileDialog == null) {
    return;
  }
  const filesToUpload = Array.from(filesFromFileDialog);
  onDrop(filesToUpload);
});
</script>

<template>
  <div class="relative w-full overflow-hidden mb-3">
    <div
      ref="dropZoneRef"
      :class="{
        'ring-2 ring-primary': isOverDropZone,
        'items-center': filesData.length === 0,
      }"
      class="rounded flex flex-col w-full min-h-[6rem] h-auto bg-gray-100 justify-center p-4"
    >
      <div
        v-if="filesData.length > 0"
        class="flex flex-wrap items-center gap-4"
      >
        <div
          v-for="(file, index) in filesData"
          :key="index"
          class="flex flex-wrap items-center gap-4"
        >
          <div>
            <p class="truncate text-xs mb-2 max-w-xs">
              {{ file.name }}
            </p>
            <x-button
              tag="div"
              color="red"
              size="xs"
              outlined
              @click.prevent="filesData = []"
            >
              Change
            </x-button>
          </div>
        </div>
      </div>
      <div v-else class="overflow-hidden relative z-10">
        <p>
          Drop file to upload or &nbsp;
          <x-button tag="span" size="sm" @click.prevent="open">
            Choose file
          </x-button>
        </p>
      </div>
    </div>
    <p v-if="props.helper" class="text-xs text-gray-500 mt-1.5">
      {{ props.helper }}
    </p>
  </div>
</template>
