<script setup>
// const emit = defineEmits(["update:uploadedFiles"]);
defineProps({
  docTypes: Object,
  docs: Array,
  cdn: String,
});
const memberTabs = ref('quote-documents');
const isUploading = ref(false);
const notification = useNotifications('toast');

const docForm = useForm({
  quote_id: usePage().props.quote.id || null,
  quote_uuid: usePage().props.quote.uuid || null,
  quote_type_id: null,
  document_type_code: null,
  folder_path: null,
  member_detail_id: null,
  file: null,
});

const uploadFile = (doc, memberId, files) => {
  if (files.length == 0) return;
  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      member_detail_id: memberId || null,
      file: files[0].file,
    }))
    .post('/quotes/car/documents/store', {
      preserveScroll: true,
      preserveState: true,
      only: ['quoteDocuments'],
      onFinish: () => {
        isUploading.value = false;
        notification.success({
          title: 'File Uploaded',
          position: 'top',
        });
      },
    });
};
</script>

<template>
  <div>
    <div>
      <x-tab-group v-model="memberTabs" class="pb-10" variant="block">
        <x-tab value="quote-documents" label="Documents">
          <div
            v-for="docType in docTypes['QUOTE']"
            :key="docType.id"
            class="grid md:grid-cols-2 gap-2 my-4 border-b"
          >
            <div class="flex flex-col gap-1">
              <h5 class="text-sm font-semibold">
                {{ docType.text }}
              </h5>
              <p class="text-xs">Max files: {{ docType.max_files }}</p>
              <p class="text-xs">Supported: {{ docType.accepted_files }}</p>
              <p class="text-xs">Max file size: {{ docType.max_size }} MB</p>
            </div>
            <div class="pb-4">
              <Dropzone
                :id="docType.id"
                :accept="docType.accepted_files"
                :max-files="docType.max_files"
                :max-size="docType.max_size"
                :loading="docForm.processing"
                @change="uploadFile(docType, null, $event)"
              />
              <a
                v-for="doc in docs.filter(
                  d => d.document_type_code == docType.code,
                )"
                :key="doc.id"
                :href="cdn + doc.doc_url"
                target="_blank"
                class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
              >
                {{ doc.original_name || doc.doc_name }}
              </a>
            </div>
          </div>
        </x-tab>
      </x-tab-group>
    </div>
  </div>
</template>
