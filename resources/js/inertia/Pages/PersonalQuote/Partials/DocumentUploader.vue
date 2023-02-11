<script setup>

import { ref } from 'vue';
import Dropzone from '@/inertia/Components/Dropzone.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useNotifications } from '@indielayer/ui';

defineProps({
    documentTypes: Object,
    quoteDocuments: Object,
    cdn: String,
});


const isUploading = ref(false);
const notification = useNotifications('toast');

const docForm = useForm({
  quote_id: usePage().props.quote.id || null,
  quote_uuid: usePage().props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  folder_path: null,
  file: null,
});

const uploadFile = (doc,  files) => {
  if (files.length == 0) return;
  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      file: files[0].file,
    }))
    .post('/personal-quotes/bike/documents', {
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
    <div
        v-for="documentType in documentTypes"
        :key="documentType.id"
        class="grid md:grid-cols-2 gap-2 my-4 border-b"
    >
        <div class="flex flex-col gap-1">
            <h5 class="text-sm font-semibold">
                {{ documentType.text }}
            </h5>
            <p class="text-xs">Max files: {{ documentType.max_files }}</p>
            <p class="text-xs">Supported: {{ documentType.accepted_files }}</p>
            <p class="text-xs">Max file size: {{ documentType.max_size }} MB</p>
        </div>
        <div class="pb-4">
            <Dropzone
                :id="documentType.id"
                :accept="documentType.accepted_files"
                :max-files="documentType.max_files"
                :max-size="documentType.max_size"
                :loading="docForm.processing"
                @change="uploadFile(documentType, $event)"
            />
            <a
                v-for="quoteDocument in quoteDocuments.filter(
              d => d.document_type_code == documentType.code,
            )"
                :key="quoteDocument.id"
                :href="storageUrl + quoteDocument.doc_url"
                target="_blank"
                class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
            >
                {{ quoteDocument.original_name || quoteDocument.doc_name }}
            </a>
        </div>
    </div>
</template>
