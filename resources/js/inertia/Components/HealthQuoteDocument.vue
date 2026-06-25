<script setup>
import NProgress from 'nprogress';
import DownloadDocuments from './DownloadDocuments.vue';
import { useDocumentTempUrl } from '@/inertia/Composables/useDocumentTempUrl.js';

const props = defineProps({
  quote: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  quoteType: {
    type: String,
    required: true,
  },
  inslyId: String,
  sendPolicy: Boolean,
  bookPolicyDetails: Array,
  storageUrl: {
    type: String,
    default: '',
  },
});

const emit = defineEmits([
  'copyUploadURL',
  'sendPolicyToClient',
  'verifyDocuments',
]);

const page = usePage();
const selectedTab = ref(0);
const uploadingStatus = ref({});
const errorMsg = ref({});
const successStatus = ref({});
const can = permission => useCan(permission);
const hasAnyRole = roles => useHasAnyRole(roles);
const hasRole = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;
const quoteTypeCodeEnum = page.props.quoteTypeCodeEnum;
const selectedMember = ref(0);

// Restricted internal types require compliance-document-upload; others are always listed.
const canViewDocumentTypeInUploadModal = documentType => {
  if (!documentType.is_restricted_internal_document) {
    return true;
  }

  return can(permissionEnum.COMPLIANCE_DOCUMENT_UPLOAD);
};

/** Delete action: requires DOCUMENT_DELETE and row must not be internal-restricted. */
const canShowQuoteDocumentDelete = item => {
  if (!can(permissionEnum.DOCUMENT_DELETE)) {
    return false;
  }
  if (item?.is_restricted_internal_document) {
    return false;
  }
  return true;
};

// Format members for dropdown
const memberOptions = computed(() => {
  return page.props.membersDetail.map(m => ({
    label: m.name + (m.is_principal == 1 ? ' ★' : ''),
    value: m.id,
  }));
});

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
    ...(page.props.quoteType === quoteTypeCodeEnum.Health
      ? [
          {
            text: 'Member',
            value: 'member',
          },
        ]
      : []),
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

const modals = reactive({
  doc: false,
  docConfirm: false,
});

const notification = useNotifications('toast');

const docForm = reactive({
  quote_id: page.props.quote.id || null,
  quote_uuid: page.props.quote.uuid || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const uploadFile = (doc, filesWithInfo) => {
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

  const url = '/personal-quotes/' + docForm.quote_id + '/documents';
  const formData = new FormData();
  formData.append('quote_id', docForm.quote_id);
  formData.append('quote_uuid', docForm.quote_uuid);
  formData.append('quote_type_id', doc.quote_type_id);
  formData.append('document_type_code', doc.code);
  formData.append('folder_path', doc.folder_path);
  formData.append('quote_type', usePage().props.quoteType);
  formData.append(
    'member_detail_id',
    doc.category == 'MEMBER' ? selectedMember.value : 0,
  );
  formData.append('category', doc.category);
  files.forEach(file => {
    formData.append('files[]', file.file);
  });

  uploadingStatus.value[doc.id] = true;

  axios
    .post(url, formData)
    .then(response => {
      successStatus.value[doc.id] = true;
      router.reload({
        preserveScroll: true,
      });
    })
    .catch(error => {
      errorMsg.value[doc.id] =
        error.response.data.message || 'File upload failed';
      notification.error({
        title: 'File upload failed',
        position: 'top',
      });
      let errorMessages = error.response.data.errors;
      Object.keys(errorMessages).forEach(function (key) {
        notification.error({
          title: errorMessages[key][0] ?? errorMessages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      uploadingStatus.value[doc.id] = false;
    });
};

const copyUploadURL = () => {
  emit('copyUploadURL');
};

const sendPolicyToClient = () => {
  emit('sendPolicyToClient');
};

const updateDocumentValidate = () => {
  emit('verifyDocuments', true);
};

const onDocDelete = (doc_id, doc_uuid) => {
  modals.docConfirm = true;
  confirmDeleteData.doc_id = doc_id;
  confirmDeleteData.doc_uuid = doc_uuid;
};

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
  doc_id: null,
  doc_uuid: null,
});

const confirmDeleteDoc = () => {
  quoteDocumentsTable.isLoading = true;
  router.post(
    `/documents/delete`,
    {
      doc_id: confirmDeleteData.doc_id,
      doc_uuid: confirmDeleteData.doc_uuid,
    },
    {
      preserveScroll: true,
      onFinish: () => {
        modals.docConfirm = false;
        quoteDocumentsTable.isLoading = false;
        notification.success({
          title: 'File Deleted',
          position: 'top',
        });

        // Emit custom event for accuracy matrix updates
        window.dispatchEvent(
          new CustomEvent('document-deleted', {
            detail: {
              docId: confirmDeleteData.doc_id,
              docUuid: confirmDeleteData.doc_uuid,
            },
          }),
        );
      },
    },
  );
};

const uploadDocumentModal = () => {
  modals.doc = true;
  successStatus.value = {};
  errorMsg.value = {};
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionEnum.All_QUOTES_VIEWONLY_ACCESS);

  window.addEventListener('document-notification', handleDocumentNotification);
});

const documentVerificationStatus = ref(page.props.quote.documents_verified);

const handleDocumentNotification = event => {
  const { quoteUID, status } = event.detail;

  if (quoteUID === page.props.quote.uuid && status === 'success') {
    documentVerificationStatus.value = true;
  }
};

onUnmounted(() => {
  window.removeEventListener(
    'document-notification',
    handleDocumentNotification,
  );
});

const { openTempUrl } = useDocumentTempUrl();

const filteredQuoteDocuments = computed(() =>
  (props.quoteDocuments || []).filter(
    d => d.document_type_code !== documentTypeCodeEnum.BOR_SIGN,
  ),
);

const openDocumentInNewTab = async item => {
  const docUrl = item.watermarked_doc_url || item.doc_url;
  if (docUrl) {
    await openTempUrl(docUrl);
  }
};

// Filter Quote signed medical application form documents to show under issuing tab
const signedMedicalApplicationDocs = computed(() => {
  return (page.props.quoteDocuments || []).filter(doc => {
    if (doc.document_type_code !== 'MED_HLTH') return false;

    const name = (doc.original_name || doc.doc_name || '').toLowerCase();
    return name.includes('signed');
  });
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Documents
            <x-tag size="sm">{{ quoteDocuments.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />

        <div
          class="flex gap-2 mb-4 justify-end"
          v-if="readOnlyMode.isDisable === true"
        >
          <x-tag
            v-if="quoteType == quoteTypeCodeEnum.Car"
            :color="documentVerificationStatus ? 'success' : 'amber'"
          >
            {{
              documentVerificationStatus ? 'Verified' : 'Verification Pending'
            }}
          </x-tag>
          <DownloadDocuments
            v-if="can(permissionEnum.DOWNLOAD_ALL_DOCUMENTS)"
            :quote="page.props.quote"
            :quoteDocuments="
              page.props.quote.documents ?? page.props.quoteDocuments
            "
          />
          <Link
            v-if="inslyId && can(permissionEnum.VIEW_LEGACY_DETAILS)"
            :href="`/legacy-policy/${inslyId}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
          <x-button
            v-if="
              quoteType == 'Car' &&
              quote.payment_status_id === paymentStatusEnum.AUTHORISED
            "
            class="mr-2"
            @click.prevent="copyUploadURL"
            size="sm"
            color="orange"
          >
            Copy upload Link
          </x-button>
          <x-tooltip placement="top">
            <x-button
              @click.prevent="updateDocumentValidate"
              v-if="
                (can(permissionEnum.DOCUMENT_VERIFY) ||
                  hasAnyRole([
                    rolesEnum.Admin,
                    rolesEnum.Engineering,
                    rolesEnum.TravelHapex,
                  ])) &&
                quoteType == 'Travel'
              "
              size="sm"
              color="green"
            >
              Verify Documents
            </x-button>
            <template #tooltip>
              Verify Documents: Clicking this button confirms that all submitted
              documents are accurate and valid.</template
            >
          </x-tooltip>

          <x-button
            @click.prevent="uploadDocumentModal"
            size="sm"
            color="orange"
          >
            Upload Documents
          </x-button>
          <x-button
            size="sm"
            color="red"
            v-if="sendPolicy"
            @click="sendPolicyToClient"
          >
            Send Policy
          </x-button>
        </div>
        <DataTable
          table-class-name="compact"
          :headers="quoteDocumentsTable.columns"
          :items="filteredQuoteDocuments"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="quoteDocuments.length < 15"
        >
          <template #item-original_name="item">
            <a
              class="text-primary-600 cursor-pointer"
              @click.prevent="openDocumentInNewTab(item)"
            >
              <span>{{ item.original_name }}</span>
            </a>
            <span
              v-if="hasRole(rolesEnum.Engineering) && item.document_type_code"
              class="text-gray-600 text-xs font-mono block mt-0.5"
            >
              {{ item.document_type_code }}
            </span>
          </template>
          <template #item-member="item">
            {{ item.member_detail?.first_name }}
            {{ item.member_detail?.last_name }}
          </template>
          <template #item-action="item">
            <div v-if="canShowQuoteDocumentDelete(item)">
              <x-tooltip
                placement="left"
                v-if="
                  bookPolicyDetails?.isEnableDocumentUploadOrDelete?.delete ===
                  false
                "
              >
                <x-button size="xs" color="error" outlined disabled="true">
                  Delete
                </x-button>
                <template #tooltip>
                  This lead is now locked as the policy has been booked. If
                  changes are needed, go to 'Send Update', select 'Add Update',
                  and choose 'Correction of Policy Upload'
                </template>
              </x-tooltip>

              <x-button
                size="xs"
                color="error"
                outlined
                @click.prevent="onDocDelete(item.id, item.doc_uuid)"
                v-else-if="readOnlyMode.isDisable === true"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>
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
          <!-- Dropdown for selecting member -->
          <div
            v-if="key.replace(/_/g, ' ') == 'MEMBER'"
            class="mb-4 mt-2 grid md:grid-cols-2 gap-2"
          >
            <div class="flex flex-col gap-1">
              <x-select
                v-model="selectedMember"
                label="Member"
                :options="memberOptions"
                placeholder="Select Member"
                filterable
              />
            </div>
          </div>

          <!-- Showing documents -->
          <template v-for="documentType in docType" :key="documentType.id">
            <div
              v-if="canViewDocumentTypeInUploadModal(documentType)"
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
                <p
                  v-if="hasRole(rolesEnum.Engineering) && documentType.code"
                  class="text-xs"
                >
                  Document type code: {{ documentType.code }}
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
                    d =>
                      d.document_type_code == documentType.code && // Filter for quote and member tab
                      (documentType.category !== 'MEMBER' ||
                        d.member_detail_id == selectedMember),
                  )"
                  :key="quoteDocument.id"
                >
                  <a
                    @click.prevent="openDocumentInNewTab(quoteDocument)"
                    class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                  >
                    <span>{{
                      quoteDocument.original_name || quoteDocument.doc_name
                    }}</span>
                    <span
                      v-if="
                        hasRole(rolesEnum.Engineering) &&
                        quoteDocument.document_type_code
                      "
                      class="text-gray-600 font-mono block truncate"
                    >
                      {{ quoteDocument.document_type_code }}
                    </span>
                  </a>
                </template>

                <!-- Show quote medical signed document here as per ADNIC requirement -->
                <div
                  v-if="
                    documentType.text?.includes(
                      'Signed medical application form',
                    )
                  "
                >
                  <a
                    v-for="doc in signedMedicalApplicationDocs"
                    :key="doc.id"
                    @click.prevent="openDocumentInNewTab(doc)"
                    class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                  >
                    <span>{{ doc.original_name || doc.doc_name }}</span>
                    <span
                      v-if="
                        hasRole(rolesEnum.Engineering) && doc.document_type_code
                      "
                      class="text-gray-600 font-mono block truncate"
                    >
                      {{ doc.document_type_code }}
                    </span>
                  </a>
                </div>
              </div>
            </div>
          </template>
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
