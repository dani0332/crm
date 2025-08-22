<script setup>
import NProgress from 'nprogress';

const props = defineProps({
  claim: Object,
  documents: Object,
  documentTypes: Object,
  storageUrl: String,
});

const emit = defineEmits(['update', 'documentUploaded', 'documentDeleted']);

const page = usePage();
const can = permission => useCan(permission);
const canAny = permissions => useCanAny(permissions);
const hasAnyRole = roles => useHasAnyRole(roles);
const permissionsEnum = page.props.permissionsEnum;
const permissionEnum = page.props.permissionsEnum;
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;
const rolesEnum = page.props.rolesEnum;
const notification = useToast();

// Missing reactive variables
const selectedTab = ref(0);
const uploadingStatus = ref({});
const errorMsg = ref({});
const successStatus = ref({});

// Document-related reactive data
const modals = ref({
  doc: false,
  docConfirm: false,
});

const docForm = reactive({
  claim_id: props.claim?.id || null,
  claim_uuid: props.claim?.uuid || null,
  claim_type_id: null,
  document_type_code: null,
  file: null,
});

const documentToDelete = ref(null);

const claimDocuments = computed(() => props.documents || []);
const quoteDocuments = computed(() => props.documents || []);
const cdnPath = ref(props.storageUrl || '');

const claimDocumentsTable = ref({
  columns: [
    { text: 'Document Name', value: 'original_name' },
    { text: 'Type', value: 'type' },
    { text: 'Uploaded At', value: 'created_at' },
    { text: 'Actions', value: 'action' },
  ],
  isLoading: false,
});

const uploadFile = async (doc, filesWithInfo) => {
  successStatus.value[doc.id] = false;
  errorMsg.value[doc.id] = '';
  const { files, rejectReason } = filesWithInfo;

  if (files.length == 0) {
    notification.error({
      title: 'File upload failed',
      position: 'top',
    });
    errorMsg.value[doc.id] = useFileUploadErrorMessage(doc, rejectReason);
    return false;
  }

  // Correct URL for claim documents
  const url = route('claims.documents.store', props.claim?.uuid);
  const formData = new FormData();
  formData.append('claim_id', docForm.claim_id);
  formData.append('claim_uuid', docForm.claim_uuid);
  formData.append('document_type_code', doc.code);
  formData.append('folder_path', doc.folder_path);

  files.forEach(file => {
    formData.append('files[]', file.file);
  });

  uploadingStatus.value[doc.id] = true;

  try {
    NProgress.start();
    const response = await axios.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    successStatus.value[doc.id] = true;
    notification.success({
      title: 'Document uploaded successfully',
      position: 'top',
    });

    // Partial reload to update documents
    router.reload({
      only: ['claim', 'documents'],
      preserveScroll: true,
      preserveState: true,
    });

    emit('documentUploaded', response.data);

  } catch (error) {
    console.error('Upload error:', error);
    errorMsg.value[doc.id] = error.response?.data?.message || 'File upload failed';

    notification.error({
      title: 'File upload failed',
      position: 'top',
    });

    // Handle validation errors
    if (error.response?.data?.errors) {
      const errorMessages = error.response.data.errors;
      Object.keys(errorMessages).forEach(key => {
        notification.error({
          title: errorMessages[key][0] ?? errorMessages[key],
          position: 'top',
        });
      });
    }
  } finally {
    uploadingStatus.value[doc.id] = false;
    NProgress.done();
  }
};


const getS3TempUrl = async docURL => {
  try {
    NProgress.start();
    const response = await axios.post(route('claims.documents.get-s3-temp-url'), {
      docURL,
    });

    if (response.status === 200 && response.data.url) {
      window.open(response.data.url, '_blank');
    } else {
      notification.error({
        title: response.data.error || 'Failed to get document URL',
        position: 'top',
      });
    }
  } catch (error) { 
    notification.error({
      title: 'Failed to access document',
      position: 'top',
    });
    console.error('S3 URL error:', error);
  } finally {
    NProgress.done();
  }
};



const onDocDelete = (docId, docUuid, docName) => {
  documentToDelete.value = {
    id: docId,
    uuid: docUuid,
    name: docName
  };
  modals.value.docConfirm = true;
};

const confirmDeleteDoc = async () => {
  try {
    claimDocumentsTable.value.isLoading = true;

    const response = await axios.delete(route('claims.documents.destroy', {
      claim: props.claim?.uuid,
      document: documentToDelete.value.id
    }));

    notification.success({
      title: 'Document deleted successfully',
      position: 'top',
    });

    // Partial reload to update documents
    router.reload({
      only: ['claim', 'documents'],
      preserveScroll: true,
      preserveState: true,
    });

    emit('documentDeleted', documentToDelete.value);

  } catch (error) {
    console.error('Delete error:', error);
    notification.error({
      title: error.response?.data?.message || 'Failed to delete document',
      position: 'top',
    });
  } finally {
    claimDocumentsTable.value.isLoading = false;
    modals.value.docConfirm = false;
    documentToDelete.value = null;
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
            <x-tag size="sm">{{ claimDocuments.length || 0 }}</x-tag>
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
          :headers="claimDocumentsTable.columns"
          :items="claimDocuments || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="claimDocuments.length < 15"
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
          <template #item-action="{ id, doc_uuid, original_name }">
            <div>
              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="onDocDelete(id, doc_uuid, original_name)"
                v-if="can(permissionsEnum.CLAIM_DOCUMENT_DELETE)"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>

                <!-- Document Upload Modal -->
      </template>
    </Collapsible>

    <!-- Document Upload Modal -->
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
                  documentType.code == documentTypeCodeEnum.CLAIM_DOCUMENTS &&
                  !can(permissionEnum.CLAIM_DOCUMENT_UPLOAD)
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

    <!-- Delete Confirmation Modal -->
    <x-modal
      v-model="modals.docConfirm"
      title="Delete Document"
      show-close
      backdrop
    >
      <p>Are you sure you want to delete "{{ documentToDelete?.name }}"?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.docConfirm = false">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="confirmDeleteDoc"
            :loading="claimDocumentsTable.isLoading"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
