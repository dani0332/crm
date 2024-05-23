<script setup>
import {fileUploadErrorMessage} from "@/inertia/Composables/utilities.js";
import {computed} from "vue";

const props = defineProps({
  quote: Object,
  quoteDocuments: Object,
  documentTypes: Object,
  storageUrl: String,
  inslyId: String,
  expanded: {
    type: Boolean,
    required: false,
    default: true
  },
  extras: {
    type: Object,
    required: false,
    default: () => ({}),
  },
  selectedCategory: {
    type: Object,
    required: true,
  },
  updateBtn: {
    type: String,
    required: false,
  },
});

const page = usePage();
const notification = useNotifications('toast');
const sendUpdateStatusEnum = page.props.sendUpdateStatusEnum;

const rowsPerPage = props.extras?.pageType === 'send-update-log' ? 10 : 15;
const isSendUpdatePage =
  props.extras?.pageType === 'send-update-log' ? true : false;
const isUploading = ref(false);
const memberTabs = ref('quote-documents');

const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

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

const modals = reactive({
  doc: false,
  docConfirm: false,
});

const docForm = useForm({
  quote_id: usePage().props.quote.id || null,
  quote_uuid: usePage().props.quote.code || null,
  quote_type_id: null,
  document_type_code: null,
  file: null,
  is_send_update: isSendUpdatePage,
  send_update_id: props.extras.sendLogId || null,
});

const uploadFile = (doc, filesWithInfo, memberId) => {
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

const isEN = computed(() => {
  return isSendUpdatePage && props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.EN;
});

const isCPU = computed(() => {
  return isSendUpdatePage && props.selectedCategory?.subCategory.slug === sendUpdateStatusEnum.CPU;
});

const sendUpdateButton = computed(() => {
  return (isEN.value || isCPU.value) && (props.updateBtn && props.updateBtn !== 'Send Update') && can(permissionsEnum.SEND_UPDATE_TO_CUSTOMER_BUTTON);
});
const sendUpdateValidation = () => {
  axios
    .post('send-update-validation', {
      quoteType: props.quoteType,
      quoteUuid: props.realQuote.uuid,
      sendUpdateId: props.sendUpdateLog.id,
    })
    .then(response => {
      if (response.status == 200) {
        modals.sendConfirm = true;
        isStating.value = response.data.message;
      }
    })
    .catch(function (errors) {
      let responseError = errors.response.data.errors.error;
      Object.keys(responseError).forEach(function (key) {
        notification.error({
          title: responseError[key],
          position: 'top',
        });
      });
    });
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
          <Link
            v-if="inslyId && can(permissionsEnum.VIEW_LEGACY_DETAILS)"
            :href="`/legacy-policy/${inslyId}`"
            preserve-scroll
          >
            <x-button size="sm" color="#ff5e00" tag="div">
                View Legacy policy
            </x-button>
          </Link>
          <x-button @click.prevent="modals.doc = true" size="sm" color="orange">
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
          :href="storageUrl + item.doc_url"
          target="_blank"
          class="text-primary-600"
        >
          {{ item.original_name }}
        </a>
      </template>
      <template #item-action="{ doc_name }" v-if="!isSendUpdatePage">
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
        <div class="flex gap-2 mb-4 justify-end">
          <x-button
              size="sm"
              color="orange"
              class="mt-5"
              v-if="sendUpdateButton"
              @click="sendUpdateValidation"
          >
            {{ props.updateBtn }}
          </x-button>
        </div>
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

      <div
        v-for="documentType in documentTypes"
        :key="documentType.id" 
        class="grid md:grid-cols-2 gap-2 my-4 border-b"
      >
        <div class="flex flex-col gap-1">
          <h5 class="text-sm font-semibold">
            {{ documentType.text }}  {{ documentType.is_required ? '*' : ''}}
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
