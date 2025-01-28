<script setup>
import AppModal from './AppModal.vue';

const props = defineProps({
  notes: Object,
  modelType: String,
  quote: Object,
  documentType: Object,
  cdn: String,
});

const notification = useNotifications('toast');
const { isRequired } = useRules();

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const showModal = ref(false);
const showAddNotes = ref(false);
const isEdit = ref(false);
const isUploading = ref(false);
const notes = ref(props.notes);
const expandNotes = ref(false);
const uploadedFiles = ref([]);

const dateFormat = date => useDateFormat(date, 'DD-MMM-YYYY h:mm:ss a').value;

const tableHeader = reactive([
  { text: 'MODIFIED BY', value: 'created_by' },
  { text: 'MODIFIED DATE', value: 'updated_at' },
  { text: 'NOTES', value: 'note' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ACTIONS', value: 'action' },
]);

const sampleData = reactive([
  {
    created_by: 'John Doe',
    updated_at: '2022-01-25',
    note: 'Lorem ipsum dolor sit amet,',
    quote_status: 'Pending',
    action: 'Edit',
  },
  {
    created_by: 'Jane Smith',
    updated_at: '2022-01-26',
    note: 'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In hac habitasse platea dictumst. Vestibulum non augue eu sem laoreet bibendum. Mauris id turpis id ligula efficitur gravida nec in purus. Sed ut justo eu tellus tincidunt consectetur. Suspendisse potenti.',
    quote_status: 'Approved',
    action: 'Delete',
  },
  // Add more sample data as needed
]);

const loader = ref({
  button: false,
  tableButton: false,
});

const fileInput = ref();

const notesForm = reactive({
  notes: null,
  quote_request_id: props.quote?.id,
  quote_type: props.modelType,
  quote_status_id: props.quote?.quote_status_id,
  document_type_code: props.documentType?.code,
  quote_uuid: props.quote?.code,
});

watchEffect(() => {
  notesForm.quote_status_id = props.quote?.quote_status_id;
});

const onNoteSubmit = isValid => {
  if (!isValid) return false;

  const formData = new FormData();
  formData.append('quoteType', notesForm.quote_type);
  formData.append('quoteRequestId', notesForm.quote_request_id);
  formData.append('notes', notesForm.notes);
  formData.append('quoteStatusId', notesForm.quote_status_id);
  formData.append('document_type_code', notesForm.document_type_code);
  formData.append('quote_uuid', notesForm.quote_uuid);

  uploadedFiles.value.forEach(x => {
    if (x && x.id) formData.append('old_documents[]', x.id);
    else formData.append('files[]', x);
  });

  loader.value.button = true;
  if (isEdit.value) {
    formData.append('id', notesForm.id);

    axios
      .post('/update-quote-notes', formData)
      .then(response => {
        notification.success({
          title: 'Notes has been Updated',
          position: 'top',
        });
        router.reload({
          only: ['quoteDocuments', 'quote', 'quoteNotes'],
        });
      })
      .catch(errors => {
        Object.keys(errors.response.data.errors.files).forEach(function (key) {
          notification.error({
            title: errors.response.data.errors.files[key],
            position: 'top',
          });
        });
      })
      .finally(() => {
        loader.value.button = false;
        showAddNotes.value = false;
      });
  } else {
    axios
      .post('/save-quote-notes', formData)
      .then(response => {
        // notes.value.data.unshift(response.data.response);
        notification.success({
          title: 'Notes has been saved',
          position: 'top',
        });
        router.reload({
          only: ['quoteDocuments', 'quote', 'quoteNotes'],
        });
      })
      .catch(errors => {
        Object.keys(errors.response.data.errors.files).forEach(function (key) {
          notification.error({
            title: errors.response.data.errors.files[key],
            position: 'top',
          });
        });
      })
      .finally(() => {
        loader.value.button = false;
        showAddNotes.value = false;
      });
  }
};

const notesLength = computed(() => notesForm.notes?.length ?? 0);

const onEditNote = data => {
  notesForm.notes = data.note;
  notesForm.id = data.id;
  uploadedFiles.value = data.documents.map(file => {
    return {
      name: file.original_name,
      url: file.doc_url,
      id: file.pivot.document_id,
    };
  });
  showAddNotes.value = true;
  isEdit.value = true;
};

const showAddNotesModal = () => {
  notesForm.notes = null;
  notesForm.id = null;
  showAddNotes.value = true;
  isEdit.value = false;
  uploadedFiles.value = [];
};

const onDeleteNote = item => {
  loader.value.tableButton = true;
  axios
    .delete(`/delete-quote-notes/${item.id}`)
    .then(response => {
      router.reload({
        only: ['quoteDocuments', 'quote', 'quoteNotes'],
      });
      // let index = notes.value.data.findIndex(note => note.id == item.id);
      // if (index != -1) {
      //   notes.value.data.splice(index, 1);
      // }
      notification.success({
        title: 'Notes has been deleted',
        position: 'top',
      });
    })
    .catch(err => {
      notification.error({
        title: 'Something went wrong',
        position: 'top',
      });
    })
    .finally(() => {
      loader.value.tableButton = false;
    });
};

const openImageDialog = () => {
  fileInput.value.click();
};

const url = file => {
  return URL.createObjectURL(file);
};

const fileValidation = file => {
  const maxSize = 10 * 1024 * 1024; // 10 MB
  // Check if file size exceeds the maximum size
  if (file.size > maxSize) {
    notification.error({
      title: 'File size must be less than 10 MB',
      position: 'top',
    });
    return false;
  }
  return true;
};

const handleDrop = event => {
  event.preventDefault();
  const files = event.dataTransfer.files;
  let isValid = fileValidation(files[0]);
  if (isValid) uploadedFiles.value.push(files[0]);
};

const uploadFile = event => {
  if (event) {
    let isValid = fileValidation(event.target.files[0]);
    if (isValid) uploadedFiles.value.push(event.target.files[0]);
  }
};

const handleRemoveFile = file => {
  let index = uploadedFiles.value.findIndex(f => f.name == file.name);
  if (index != -1) {
    uploadedFiles.value.splice(index, 1);
  }
};

watch(
  () => props.notes,
  () => {
    notes.value = props.notes;
  },
);
</script>
<template>
  <div v-if="can(permissionsEnum.SAVE_QUOTE_NOTES)">
    <x-tooltip>
      <x-button size="sm" color="emerald" @click="showModal = true">
        Notes ({{ notes?.data?.length }})
      </x-button>
      <template #tooltip>
        <span
          >Click this button to create or view notes related to this lead. It
          allows you to make notes and access important information about this
          item.
        </span>
      </template>
    </x-tooltip>
  </div>
  <AppModal class="lg:min-w-[900px]" v-model="showModal" show-close show-header>
    <template #header>
      <p class="font-bold m-0">Notes</p>
    </template>
    <div>
      <div class="flex justify-end">
        <x-button size="sm" color="orange" @click="showAddNotesModal()">
          {{ isEdit ? 'Edit' : 'Add' }} Notes
        </x-button>
      </div>
      <DataTable
        table-class-name=""
        :headers="tableHeader"
        :items="notes.data || []"
        border-cell
        hide-rows-per-page
        hide-footer
        class="mt-5"
      >
        <template #header-note="note">
          <div class="flex gap-3 items-center">
            {{ note.text }}
            <x-icon
              @click="expandNotes = !expandNotes"
              icon="chevronDown"
              :class="{ 'rotate-180': expandNotes }"
            />
          </div>
        </template>
        <template #item-created_by="{ created_by }">
          {{ created_by.name }}
        </template>
        <template #item-updated_at="{ updated_at }">
          {{ dateFormat(updated_at) }}
        </template>

        <template #item-quote_status="{ quote_status }">
          {{ quote_status.text }}
        </template>
        <template #item-note="{ note }">
          <template v-if="note.length < 40">
            {{ note }}
          </template>
          <x-accordion v-else show-icon icon="chevronDown">
            <x-accordion-item :expanded="expandNotes">
              <div class="bg-gray-10 w-80">
                {{ note.slice(0, 40) }}
              </div>
              <template #content>
                <div>
                  {{ note.slice(40, note.length) }}
                </div>
              </template>
            </x-accordion-item>
          </x-accordion>
        </template>
        <template #item-action="item">
          <div class="flex gap-2">
            <x-button
              size="xs"
              color="primary"
              outlined
              @click.prevent="onEditNote(item)"
            >
              Edit
            </x-button>
            <x-button
              size="xs"
              color="red"
              outlined
              :loading="loader.tableButton"
              @click.prevent="onDeleteNote(item)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>
      <Pagination
        :links="{
          next: notes.next_page_url,
          prev: notes.prev_page_url,
          current: notes.current_page,
          from: notes.from,
          to: notes.to,
        }"
      />
    </div>
  </AppModal>

  <!-- Modal for add/Update notes related to Leads -->
  <AppModal
    class="min-w-[30%] overflow-hidden"
    v-model="showAddNotes"
    show-header
    show-close
    :backdrop-close="false"
  >
    <template #header>
      <p class="font-bold m-0">{{ isEdit ? 'Update' : 'Add' }} Notes</p>
    </template>

    <x-form class="w-full" @submit="onNoteSubmit" :auto-focus="false">
      <x-field label="Notes" class="w-full">
        <x-textarea
          :adjustToText="false"
          maxlength="1000"
          class="w-full"
          v-model="notesForm.notes"
          rows="5"
          :rules="[isRequired]"
        />
      </x-field>
      <p class="text-xs ml-auto flex justify-end mt-2">
        {{ notesLength }}/1000
      </p>
      <div
        class="mt-2 h-12 relative"
        @drop.prevent="handleDrop"
        @dragover.prevent
      >
        <input
          @change.prevent="uploadFile"
          ref="fileInput"
          type="file"
          hidden
        />

        <x-tooltip placement="top">
          <x-button
            size="sm"
            color="primary"
            :loading="loader.button"
            icon="upload"
            @click.prevent="openImageDialog"
          >
            Upload Documents
          </x-button>
          <template #tooltip>
            <span class="text-sm">
              Use this button to attach and save documents that support your
              notes. You can drag and drop files or browse to upload them into
              the system, making it easy to store and access important files
            </span>
          </template>
        </x-tooltip>
        <template v-if="uploadedFiles.length > 0">
          <div
            v-for="file of uploadedFiles"
            :key="file.name"
            class="text-sm mt-2 flex gap-1 font-bold"
          >
            <p v-if="file && file.id" class="max-w-[200px] truncate">
              <a
                :href="cdn + `${file.url}`"
                target="_blank"
                class="text-primary"
                >{{ file.name }}</a
              >
            </p>
            <p v-else class="max-w-[200px] truncate">
              <a :href="url(file)" target="_blank" class="text-primary">{{
                file.name
              }}</a>
            </p>

            <x-icon
              icon="xmark"
              class="text-red-700"
              @click="handleRemoveFile(file)"
            ></x-icon>
          </div>
        </template>

        <!-- <Dropzone
          :id="documentType?.id"
          :accept="documentType?.accepted_files"
          :max-files="documentType?.max_files"
          :max-size="documentType?.max_size"
          :loading="isUploading"
          @change="uploadFile(documentType, $event)"
        /> -->
      </div>
      <div class="mt-5 flex gap-2 justify-end">
        <x-button
          size="sm"
          @click.prevent="((showAddNotes = false), (isEdit = false))"
        >
          Cancel
        </x-button>
        <x-button
          type="submit"
          size="sm"
          color="emerald"
          :loading="loader.button"
        >
          Save
        </x-button>
      </div>
    </x-form>
  </AppModal>
</template>
