<script setup>
const props = defineProps({
  claim: Object,
  documents: Object,
  documentTypes: Object,
});

const emit = defineEmits(['update', 'documentUploaded', 'documentDeleted']);

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const permissionsEnum = page.props.permissionsEnum;
const notification = useToast();

// Document-related reactive data
const modals = ref({
  doc: false,
  docConfirm: false,
});

const documentToDelete = ref(null);

// Mock data - you'll need to implement these based on your actual document structure
const quoteDocuments = computed(() => props.documents || []);
const documentTypes = ref([]); // You'll need to get this from props or API
const cdnPath = ref(''); // You'll need to get this from config

const quoteDocumentsTable = ref({
  columns: [
    { text: 'Document Name', value: 'original_name' },
    { text: 'Type', value: 'type' },
    { text: 'Uploaded At', value: 'created_at' },
    { text: 'Actions', value: 'action' },
  ],
  isLoading: false,
});

// Document management functions
const onDocDelete = docName => {
  documentToDelete.value = docName;
  modals.value.docConfirm = true;
};

const confirmDeleteDoc = async () => {
  try {
    quoteDocumentsTable.value.isLoading = true;

    modals.value.docConfirm = false;
    documentToDelete.value = null;

    notification.success({
      title: 'Document deleted successfully',
      position: 'top',
    });

    emit('documentDeleted', documentToDelete.value);
  } catch (error) {
    notification.error({
      title: 'Failed to delete document',
      position: 'top',
    });
  } finally {
    quoteDocumentsTable.value.isLoading = false;
  }
};

const readOnlyMode = computed(() => {
  return {
    isDisable: can(permissionsEnum.CLAIM_DOCUMENT_UPLOAD), // Adjust based on your permissions
  };
});

const permissions = computed(() => {
  return {
    notProductionApproval: true, // Implement based on your logic
    isQuoteDocumentEnabled: true, // Implement based on your logic
  };
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="true">
      <template #header>
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-primary-800 text-lg">
            Documents
            <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <div class="flex justify-end items-center mb-4">
          <x-button
            @click.prevent="modals.doc = true"
            size="sm"
            color="primary"
          >
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
              :href="cdnPath + item.doc_url"
              target="_blank"
              class="text-primary-600"
            >
              {{ item.original_name }}
            </a>
          </template>
          <template #item-action="{ doc_name }">
            <div>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="onDocDelete(doc_name)"
                v-if="readOnlyMode.isDisable === true"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>

        <!-- Document Upload Modal -->
      </template>
    </Collapsible>

    <x-modal
      v-model="modals.doc"
      size="xl"
      title="Upload Documents"
      show-close
      backdrop
    >
      <x-tab-group v-model="selectedTab" variant="block">
        <x-tab
          :value="index"
          :label="key.replace(/_/g, ' ')"
          v-for="(docType, key, index) in documentTypes"
          :key="index"
          :disabled="
            key === $page.props.documentTypeEnum.ISSUING_DOCUMENTS &&
            !quote.insurance_provider_id &&
            !quote.plan_id
          "
        >
          <div
            v-for="documentType in docType"
            :key="documentType.id"
            class="grid md:grid-cols-2 gap-2 my-4 border-b"
          >
            <div class="flex flex-col gap-1">
              <h5 class="text-sm font-semibold">
                {{ documentType.text }}
                <span class="text-red-500">
                  {{ documentType.is_required ? '*' : '' }}</span
                >
              </h5>
              <p class="text-xs">Max files: {{ documentType.max_files }}</p>
              <p class="text-xs">
                Supported: {{ documentType.accepted_files }}
              </p>
              <p class="text-xs">
                Max file size: {{ documentType.max_size }} MB
              </p>

              <x-alert
                v-if="successStatus[documentType.id]"
                type="success"
                color="success"
                light
              >
                <p class="text-sm">File uploaded successfully</p>
              </x-alert>

              <x-alert
                v-if="errorMsg[documentType.id]"
                type="error"
                color="error"
                light
              >
                <p class="text-sm">{{ errorMsg[documentType.id] }}</p>
              </x-alert>
            </div>
            <div class="pb-4">
              <Dropzone
                :id="documentType.id"
                :accept="documentType.accepted_files"
                :max-files="documentType.max_files"
                :max-size="documentType.max_size"
                :loading="uploadingStatus[documentType.id]"
                :document-type-code="documentType.code"
                :isDisabled="
                  documentType.code == documentTypeCodeEnum.AUDIT &&
                  !can(permissionEnum.AUDITDOCUMENT_UPLOAD)
                "
                :multiple="true"
                @change="uploadFile(documentType, $event)"
              />

              <template
                v-for="quoteDocument in quoteDocuments.filter(
                  d => d.document_type_code == documentType.code,
                )"
                :key="quoteDocument.id"
              >
                <a
                  v-if="hasAnyRole([rolesEnum.BetaUser])"
                  @click.prevent="getS3TempUrl(quoteDocument.doc_url)"
                  class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                >
                  {{ quoteDocument.original_name || quoteDocument.doc_name }}
                </a>
                <a
                  v-else
                  :href="
                    storageUrl +
                    (quoteDocument.watermarked_doc_url || quoteDocument.doc_url)
                  "
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
    <x-modal
      v-model="modals.docConfirm"
      title="Delete Document"
      show-close
      backdrop
    >
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
