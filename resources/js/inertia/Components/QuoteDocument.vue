<script setup>
import {fileUploadErrorMessage} from "@/inertia/Composables/utilities.js";

defineProps({
  quote: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  storageUrl: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true
  },
  docUploadURL: String,
});

const page = usePage();
const selectedTab = ref(0); 
const isUploading = ref(false);

const quoteDocumentsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Document Type',
      value: 'document_type_text',
    },
    {
      text: 'Document Name',
      value: 'original_name',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Created By',
      value: 'created_by.email',
    },
  ],
});


const modals = reactive({
  doc: false,
  docConfirm: false,
});

const notification = useNotifications('toast');

const docForm = useForm({
  quote_id: usePage().props.quote.id || null,
  quote_uuid: usePage().props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const uploadFile = (doc, filesWithInfo) => {
    let url = '/personal-quotes/' + docForm.quote_id + '/documents';
    const { files, rejectReason} = filesWithInfo;
    if (files.length == 0) {
        notification.error({
            title: 'File upload failed',
            position: 'top',
        });
        docForm.setError({error: fileUploadErrorMessage(doc, rejectReason)});
        return false
    };
  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      file: files[0].file,
    }))
    .post(url, {
      preserveScroll: true,
      preserveState: true,
      onError: errors => {
        docForm.setError(errors.error);
        console.log(errors);
        notification.error({
          title: 'File upload failed',
          position: 'top',
        });
      },
      onFinish: () => {
        isUploading.value = false;
      },
    });
};

const copyUploadURL = () => {
  copy(page.props.docUploadURL);
  if (copied)
    notification.success({
      title: 'Link copied to clipboard',
      position: 'top',
    });
};
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
          Documents - Code Refactor
          <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />  

        <div class="flex gap-2 mb-4 justify-end">
          <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
            Upload Documents
          </x-button>
        </div>
    <DataTable
      table-class-name="compact"
      :headers="quoteDocumentsTable.columns"
      :items="quoteDocuments || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="quoteDocuments.length < 15"
    >
      <template #item-original_name="item">
        <a
          :href="storageUrl + item.doc_url"
          target="_blank"
          class="text-primary-600"
        >
          {{ item.original_name }}
        </a>
      </template>
        </DataTable>
      </template>
    </Collapsible>

    <x-modal v-model="modals.doc" size="xl" show-close backdrop>
      <template #header> Upload Documents </template>
      <x-alert
        color="error"
        class="mb-5"
        v-if="Object.keys(docForm.errors).length"
      >
      <ul>
        <li v-for="error in docForm?.errors" :key="error">{{ error }}</li>
      </ul>
      </x-alert>

    <x-tab-group v-model="selectedTab" class="pb-10" variant="block">
      <x-tab :value="index" :label="key.replace(/_/g, ' ')" v-for="(docType, key, index) in documentTypes">
        <div
            v-for="documentType in docType"
            :key="documentType.id"
            class="grid md:grid-cols-2 gap-2 my-4 border-b"
          >
            <div class="flex flex-col gap-1">
              <h5 class="text-sm font-semibold">
                {{ documentType.text }} <span class="text-red-500"> {{documentType.is_required ? '*' : ''  }}</span>
              </h5>
              <p class="text-xs">Max files: {{ documentType.max_files }}</p>
              <p class="text-xs">
                Supported: {{ documentType.accepted_files }}
              </p>
              <p class="text-xs">
                Max file size: {{ documentType.max_size }} MB
              </p>
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
              <template
                v-for="quoteDocument in quoteDocuments.filter(
                  d => d.document_type_code == documentType.code,
                )"
                :key="quoteDocument.id"
                >
                  <a
                    :href="storageUrl + quoteDocument.doc_url"
                    target="_blank"
                    class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate"
                  >
                    {{ quoteDocument.original_name || quoteDocument.doc_name }}
                  </a>
              </template>
            </div>
          </div>
      </x-tab>
    </x-tab-group>
    </x-modal>
  </div>
</template>
