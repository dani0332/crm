<script setup>
const props = defineProps({
  quote: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  storageUrl: String,
  inslyId: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true,
  },
  extras: {
    type: Object,
    required: false,
    default: () => ({}),
  },
  sendUpdateLog: {
    type: Object,
    required: true,
  },
  updateBtn: {
    type: String,
    required: false,
  },
  quoteType: {
    type: String,
    required: false,
  },
  isSentOrBooked: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const page = usePage();
const selectedTab = ref(0);
const notification = useNotifications('toast');
const sendUpdateStatusEnum = page.props.sendUpdateStatusEnum;

const rowsPerPage = props.extras?.pageType === 'send-update' ? 10 : 15;
const isSendUpdatePage =
  props.extras?.pageType === 'send-update' ? true : false;
const isUploading = ref(false);
const memberTabs = ref('quote-documents');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const leadSource = page.props.leadSource;
const documentTypeCodeEnum = page.props.documentTypeCodeEnum;
const hasAnyRole = roles => useHasAnyRole(roles);
const rolesEnum = page.props.rolesEnum;
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
      quoteId: isSendUpdatePage ? props.extras.sendLogId : page.props.quote.id,
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

const isStating = ref(false);
const isLoading = ref(false);
const isNotConfirmed = ref(false);
const loader = reactive({
  sendUpdateSectionBtn: false,
  sendUpdate: false,
  selectInvoice: false,
});

const modals = reactive({
  sendConfirm: false,
  isConfirmed: false,
  doc: false,
  docConfirm: false,
});

const docForm = useForm({
  quote_id: page.props.quote.id || null,
  quote_uuid: page.props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
  is_send_update: isSendUpdatePage,
  send_update_id: props.extras.sendLogId || null,
});

const uploadFile = (doc, filesWithInfo, memberId) => {
  let url = '/personal-quotes/' + docForm.quote_id + '/documents';
  const { files, rejectReason } = filesWithInfo;
  if (files.length == 0) {
    notification.error({
      title: 'File upload failed',
      position: 'top',
    });
    docForm.setError({ error: useFileUploadErrorMessage(doc, rejectReason) });
    return false;
  }
  let docFiles = [];
  files.forEach(file => {
    docFiles.push(file.file);
  });
  isUploading.value = true;
  docForm
    .transform(data => ({
      ...data,
      quote_type_id: doc.quote_type_id,
      document_type_code: doc.code,
      folder_path: doc.folder_path,
      files: docFiles,
      member_detail_id: memberId || null,
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
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
const isEN = computed(() => {
  return (
    isSendUpdatePage &&
    props.sendUpdateLog.category.code === sendUpdateStatusEnum.EN
  );
});

const isCPU = computed(() => {
  return (
    isSendUpdatePage &&
    props.sendUpdateLog.category.code === sendUpdateStatusEnum.CPU
  );
});

const sendUpdateButton = computed(() => {
  return (
    (isEN.value || isCPU.value) &&
    props.updateBtn &&
    props.updateBtn !== sendUpdateStatusEnum.SU
  );
});

const sendUpdateValidation = () => {
  loader.sendUpdateSectionBtn = true;
  axios
    .post('send-update-customer-validation', {
      quoteType: props.quoteType,
      quoteUuid: props.quote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
      action: sendUpdateStatusEnum?.ACTION_SUC,
    })
    .then(response => {
      modals.sendConfirm = true;
      isStating.value = response.data.message;
    })
    .catch(function (errors) {
      let responseError = errors.response.data.errors.error;
      Object.keys(responseError).forEach(function (key) {
        notification.error({
          title: responseError[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      loader.sendUpdateSectionBtn = false;
    });
};

const permissionEnum = page.props.permissionsEnum;

const [sendUpdateCustConfirmBtnTemp, SendUpdateCustReuseBtnTemp] =
  createReusableTemplate();

const submitToCustomer = () => {
  if (!modals.isConfirmed) {
    isNotConfirmed.value = true;
    return;
  }
  isLoading.value = true;
  let url = 'send-update-to-customer';
  let data = {
    sendUpdateId: props.sendUpdateLog.id,
    quoteType: props.quoteType,
    action: sendUpdateStatusEnum?.ACTION_SUC,
    isEmailSent: props.sendUpdateLog.is_email_sent,
  };
  axios
    .post(url, data)
    .then(response => {
      if (response.status == 200) {
        Object.keys(response.data).forEach(function (key) {
          notification.success({
            title: response.data[key],
            position: 'top',
          });
        });
        router.reload({ preserveState: true });
        modals.sendConfirm = isLoading.value = false;
      }
    })
    .catch(err => {
      const flash_messages = err.response.data.errors;
      Object.keys(flash_messages).forEach(function (key) {
        notification.error({
          title: flash_messages[key],
          position: 'top',
        });
      });
    })
    .finally(() => {
      modals.sendConfirm = false;
      isLoading.value = false;
      isNotConfirmed.value = false;
    });
};

const sendUpdatePermissionCheck = computed(() => {
  if (
    props.updateBtn === sendUpdateStatusEnum.SUC &&
    props.sendUpdateLog.status === sendUpdateStatusEnum.UPDATE_SENT_TO_CUSTOMER
  ) {
    return true;
  }

  if (props.updateBtn === sendUpdateStatusEnum.SU) {
    return !can(permissionEnum.BOOK_UPDATE_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SUC) {
    return !can(permissionEnum.SEND_UPDATE_TO_CUSTOMER_BUTTON);
  } else if (props.updateBtn === sendUpdateStatusEnum.SNBU) {
    return !can(permissionEnum.SEND_AND_BOOK_UPDATE_BUTTON);
  }

  return true;
});

const getS3TempUrl = async docURL => {
  try {
    const response = await axios.post('/quotes/documents/get-s3-temp-url', {
      docURL,
    });
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
        <div class="flex gap-2 mb-4 justify-end">
          <DownloadDocuments
            v-if="can(permissionsEnum.DOWNLOAD_ALL_DOCUMENTS)"
            :quote="page.props.quote"
            :quoteDocuments="
              page.props.quote.documents ?? page.props.quoteDocuments
            "
          />
          <Link
            v-if="inslyId && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
            :href="`/legacy-policy/${inslyId}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
          <Link
            v-else-if="
              quote.source == leadSource.RENEWAL_UPLOAD &&
              can(permissionsEnum.VIEW_LEGACY_DETAILS)
            "
            :href="route('legacy-policy.index')"
            :data="{
              policy_number: quote.previous_quote_policy_number,
            }"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
              View Legacy policy
            </x-button>
          </Link>
          <x-button
            @click.prevent="modals.doc = true"
            size="sm"
            color="orange"
            class="focus:ring-2 focus:ring-black"
          >
            Upload Documents
          </x-button>
        </div>

        <DataTable
          table-class-name="compact"
          :headers="quoteDocumentsTable.columns"
          :items="quoteDocuments.sort((a, b) => b.id - a.id) || []"
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
            #item-action="{ doc_name }"
            v-if="can(permissionEnum.DOCUMENT_DELETE)"
          >
            <div>
              <x-tooltip placement="bottom" v-if="props.isSentOrBooked">
                <x-button size="xs" color="error" outlined disabled>
                  Delete
                </x-button>
                <template #tooltip>
                  This request is now locked as the update has been booked. If
                  changes are needed, go to 'Send Update', select 'Add Update',
                  and choose 'Correction of Policy Upload'.
                </template>
              </x-tooltip>

              <x-button
                v-else
                size="xs"
                color="error"
                outlined
                @click.prevent="onDocDelete(doc_name)"
                class="focus:ring-2 focus:ring-black"
              >
                Delete
              </x-button>
            </div>
          </template>
        </DataTable>
        <div class="flex gap-2 mb-4 justify-end">
          <x-button
            size="sm"
            color="orange"
            class="mt-5"
            v-if="sendUpdateButton"
            :loading="loader.sendUpdateSectionBtn"
            @click="sendUpdateValidation"
            :disabled="sendUpdatePermissionCheck"
          >
            {{ props.updateBtn }}
          </x-button>
        </div>
      </template>
    </Collapsible>

    <x-modal
      v-model="modals.doc"
      size="xl"
      title="Upload Documents"
      show-close
      backdrop
    >
      <x-alert
        color="error"
        class="mb-5"
        v-if="Object.keys(docForm.errors).length"
      >
        <ul>
          <li v-for="error in docForm?.errors" :key="error">{{ error }}</li>
        </ul>
      </x-alert>

      <x-tab-group v-model="selectedTab" variant="block">
        <x-tab
          :value="index"
          :label="key.replace(/_/g, ' ')"
          v-for="(docType, key, index) in documentTypes"
          :key="index"
          :disabled="
            key === $page.props.documentTypeEnum.ISSUING_DOCUMENTS &&
            !quote.insurance_provider_id
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
                <span class="text-red-500">{{
                  documentType.is_required ? '*' : ''
                }}</span>
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
                :isDisabled="
                  documentType.code ==
                    documentTypeCodeEnum.SEND_UPDATE_AUDIT_RECORD &&
                  !can(permissionEnum.AUDITDOCUMENT_UPLOAD)
                "
                :multiple="true"
              />
              <div v-if="isSendUpdatePage">
                <a
                  v-if="hasAnyRole([rolesEnum.BetaUser])"
                  v-for="quoteDocument in quoteDocuments.filter(
                    d => d.document_type_text == documentType.text,
                  )"
                  :key="quoteDocument.id"
                  @click.prevent="getS3TempUrl(quoteDocument.doc_url)"
                  class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                >
                  {{ quoteDocument.original_name || quoteDocument.doc_name }}
                </a>
                <a
                  v-else
                  v-for="quoteDocument in quoteDocuments.filter(
                    d => d.document_type_text == documentType.text,
                  )"
                  :key="quoteDocument.id"
                  :href="
                    storageUrl +
                    (quoteDocument.watermarked_doc_url ?? quoteDocument.doc_url)
                  "
                  target="_blank"
                  class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                >
                  {{ quoteDocument.original_name || quoteDocument.doc_name }}
                </a>
              </div>
              <div v-else>
                <a
                  v-if="hasAnyRole([rolesEnum.BetaUser])"
                  v-for="quoteDocument in quoteDocuments.filter(
                    d => d.document_type_code == documentType.code,
                  )"
                  :key="quoteDocument.id"
                  @click.prevent="getS3TempUrl(quoteDocument.doc_url)"
                  class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                >
                  {{ quoteDocument.original_name || quoteDocument.doc_name }}
                </a>
                <a
                  v-else
                  v-for="quoteDocument in quoteDocuments.filter(
                    d => d.document_type_code == documentType.code,
                  )"
                  :key="quoteDocument.id"
                  :href="
                    storageUrl +
                    (quoteDocument.watermarked_doc_url ?? quoteDocument.doc_url)
                  "
                  target="_blank"
                  class="block px-2 py-1 border rounded mt-1 text-xs hover:text-primary-600 truncate cursor-pointer"
                >
                  {{ quoteDocument.original_name || quoteDocument.doc_name }}
                </a>
              </div>
            </div>
          </div>
        </x-tab>
      </x-tab-group>
    </x-modal>

    <x-modal
      v-model="modals.docConfirm"
      title="Delete Document"
      size="md"
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

    <sendUpdateCustConfirmBtnTemp>
      <x-button
        size="sm"
        color="error"
        @click.prevent="submitToCustomer"
        :disabled="!modals.isConfirmed"
        :loading="isLoading"
      >
        Confirm
      </x-button>
    </sendUpdateCustConfirmBtnTemp>

    <x-modal
      v-model="modals.sendConfirm"
      :title="props.updateBtn"
      size="md"
      show-close
      backdrop
    >
      <x-alert
        color="orange"
        light
        type="error"
        class="text-sm mb-4"
        v-if="isStating"
      >
        {{ isStating }}
      </x-alert>
      <x-checkbox
        v-model="modals.isConfirmed"
        label="I confirm and attest that all information recorded is correct. I confirm I am in compliance with the COC."
      />
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            :disabled="isLoading"
            @click.prevent="modals.sendConfirm = false"
          >
            Cancel
          </x-button>
          <template v-if="!modals.isConfirmed">
            <x-tooltip placement="left">
              <SendUpdateCustReuseBtnTemp />
              <template #tooltip>
                Please select the checkbox to proceed
              </template>
            </x-tooltip>
          </template>
          <SendUpdateCustReuseBtnTemp v-else />
        </div>
      </template>
    </x-modal>
  </div>
</template>
