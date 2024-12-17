<script setup>
defineProps({
  quote: Object,
  documentTypes: Object,
  storageUrl: String,
});

const page = usePage();

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
      text: 'Original Document',
      value: 'doc_name',
    },
    {
      text: 'Created At',
      value: 'created_at',
    },
    {
      text: 'Created By',
      value: 'created_by.email',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const documentsTableItems = computed(() => {
  return page.props.quote.documents.map(doc => {
    return {
      document_type_text:
        doc.document_type_text.length > 0 ? doc.document_type_text : '',
      doc_name: doc.doc_name,
      original_name: doc.original_name,
      created_at: doc.created_at,
      doc_uuid: doc.doc_uuid,
      doc_url: doc.doc_url,
      created_by: doc.created_by ? doc.created_by.name : '',
      watermarked_doc_url: doc.watermarked_doc_url ?? doc.doc_url,
    };
  });
});

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

const confirmDeleteDoc = () => {
  quoteDocumentsTable.isLoading = true;
  router.post(
    `/documents/delete`,
    {
      docName: confirmDeleteData.docs,
      quoteId: page.props.quote.id,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        quoteDocumentsTable.isLoading = false;
        notification.error({
          title: 'File Deleted',
          position: 'top',
        });
      },
    },
  );
};

const modals = reactive({
  doc: false,
  docConfirm: false,
});

const isUploading = ref(false);
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
  const { files, rejectReason } = filesWithInfo;
  if (files.length == 0) {
    notification.error({
      title: 'File upload failed',
      position: 'top',
    });
    docForm.setError({ error: fileUploadErrorMessage(doc, rejectReason) });
    return false;
  }
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
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Documents
        <x-tag size="sm">{{ quote.documents.length || 0 }}</x-tag>
      </h3>
      <div class="flex gap-2">
        <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
          Upload Documents
        </x-button>
      </div>
    </div>

    <DataTable
      table-class-name="compact"
      :headers="quoteDocumentsTable.columns"
      :items="documentsTableItems || []"
      border-cell
      hide-rows-per-page
      :rows-per-page="15"
      :hide-footer="documentsTableItems.length < 15"
    >
      <template #item-original_name="item">
        <a
          :href="storageUrl + item.watermarked_doc_url"
          target="_blank"
          class="text-primary-600"
        >
          {{ item.original_name }}
        </a>
      </template>
      <template #item-doc_name="item">
        <a
          :href="storageUrl + item.doc_url"
          target="_blank"
          class="text-primary-600"
        >
          {{ item.doc_name }}
        </a>
      </template>
      <template #item-action="{ doc_name }">
        <div>
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="onDocDelete(doc_name)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="modals.doc" size="xl" show-close backdrop>
      <template #header> Upload Documents </template>

      <x-alert
        color="error"
        class="mb-5"
        v-if="Object.keys(docForm.errors).length"
      >
        <ul>
          <li v-for="error in docForm?.errors">{{ error }}</li>
        </ul>
      </x-alert>

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
            v-for="quoteDocument in quote.documents.filter(
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
    </x-modal>

    <x-modal v-model="modals.docConfirm" show-close backdrop>
      <template #header> Delete Document </template>
      <p>Are you sure you want to delete this document?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.docConfirm = false">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="confirmDeleteDoc"
            :loading="quoteDocumentsTable.isLoading"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
