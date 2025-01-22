<script setup>
import NProgress from 'nprogress';
import DownloadDocuments from './DownloadDocuments.vue';

defineProps({
  quote: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  storageUrl: String,
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
const rolesEnum = page.props.rolesEnum;
const permissionEnum = page.props.permissionsEnum;
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;
const paymentStatusEnum = page.props.paymentStatusEnum;

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

const onDocDelete = name => {
  modals.docConfirm = true;
  confirmDeleteData.docs = name;
};

const confirmDeleteData = reactive({
  docs: null,
  member: null,
  activity: null,
  contact: null,
});

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
        notification.success({
          title: 'File Deleted',
          position: 'top',
        });
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
});

const getS3TempUrl = async docURL => {
  try {
    NProgress.start();
    const response = await axios.post('/quotes/documents/get-s3-temp-url', {
      docURL,
    });
    NProgress.done();
    // Check if the request was successful and the response contains the URL
    if (response.status === 200 && response.data.url) {
      // Open the URL in a new tab
      window.open(response.data.url, '_blank');
    } else {
      notification.error({
        title: response.data.error,
        position: 'top',
      });
    }
  } catch (error) {
    notification.error({
      title: error,
      position: 'top',
    });
    console.error('An error occurred:', error);
  }
};
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
          :items="quoteDocuments || []"
          border-cell
          hide-rows-per-page
          :rows-per-page="15"
          :hide-footer="quoteDocuments.length < 15"
        >
          <template #item-original_name="item">
            <a
              v-if="hasAnyRole([rolesEnum.BetaUser])"
              @click.prevent="getS3TempUrl(item.doc_url)"
              class="text-primary-600 cursor-pointer"
            >
              {{ item.original_name }}
            </a>

            <a
              v-else
              :href="storageUrl + (item.watermarked_doc_url ?? item.doc_url)"
              target="_blank"
              class="text-primary-600"
            >
              {{ item.original_name }}
            </a>
          </template>
          <template
            v-if="can(permissionEnum.DOCUMENT_DELETE)"
            #item-action="{ doc_name }"
          >
            <div>
              <x-tooltip
                placement="left"
                v-if="bookPolicyDetails?.isEnableUploadDocument === false"
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
                @click.prevent="onDocDelete(doc_name)"
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
                    (quoteDocument.watermarked_doc_url ?? quoteDocument.doc_url)
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
