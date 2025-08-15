<script setup>
const props = defineProps({
  claim: Object,
  documents: Object,
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

    // Implement document deletion logic here
    // await deleteDocument(documentToDelete.value);

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

// Mock function - implement based on your requirements
const memberDataDocs = travelers => {
  return travelers || [];
};

// Mock function - implement based on your requirements
const getupdateDocumentValidate = validate => {
  console.log('Validating documents:', validate);
  // Implement document validation logic
};

// Mock function - implement based on your requirements
const sendPolicyToClient = () => {
  console.log('Sending policy to client');
  // Implement send policy logic
};

// Computed properties for display logic
const displaySendPolicyButton = computed(() => {
  // Implement logic to determine when to show send policy button
  return false;
});

const readOnlyMode = computed(() => {
  return {
    isDisable: can(permissionsEnum.DOCUMENT_UPLOAD), // Adjust based on your permissions
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
          <div class="flex gap-2">
            <Link
              v-if="
                claim?.insly_id &&
                canAny([
                  permissionsEnum.VIEW_LEGACY_DETAILS,
                  permissionsEnum.VIEW_ALL_LEADS,
                ])
              "
              :href="`/legacy-policy/${claim.insly_id}`"
              preserve-scroll
            >
              <x-button size="sm" color="#ff5e00" tag="div">
                View Legacy policy
              </x-button>
            </Link>
            <x-tooltip placement="top">
              <x-button
                @click.prevent="getupdateDocumentValidate(true)"
                v-if="can(permissionsEnum.DOCUMENT_VERIFY)"
                size="sm"
                color="green"
              >
                Verify Documents
              </x-button>
              <template #tooltip>
                Verify Documents: Clicking this button confirms that all
                submitted documents are accurate and valid.
              </template>
            </x-tooltip>
            <x-button
              @click.prevent="modals.doc = true"
              size="sm"
              color="primary"
              v-if="readOnlyMode.isDisable === true"
            >
              Upload Documents
            </x-button>
            <x-button
              size="sm"
              color="red"
              v-if="
                displaySendPolicyButton &&
                permissions.notProductionApproval &&
                permissions.isQuoteDocumentEnabled
              "
              @click="sendPolicyToClient"
            >
              Send Policy
            </x-button>
          </div>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
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
        <x-modal
          v-model="modals.doc"
          size="xl"
          title="Upload Documents"
          show-close
          backdrop
        >
          <LazyDocumentUploader
            :members="memberDataDocs([])"
            :doc-types="documentTypes"
            :docs="quoteDocuments || []"
            :cdn="cdnPath"
            @uploaded="doc => emit('documentUploaded', doc)"
          />
        </x-modal>

        <!-- Delete Confirmation Modal -->
        <x-modal
          v-model="modals.docConfirm"
          title="Delete Document"
          show-close
          backdrop
        >
          <p>Are you sure you want to delete this document?</p>
          <template #actions>
            <div class="text-right space-x-4">
              <x-button
                size="sm"
                ghost
                @click.prevent="modals.docConfirm = false"
              >
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
      </template>
    </Collapsible>
  </div>
</template>
