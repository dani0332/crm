<script setup>
import AppModal from './AppModal.vue';

const props = defineProps({
  notes: Object,
  modelType: String,
  quote: Object,
  documentType: Object,
});

const notification = useNotifications('toast');

const showModal = ref(false);
const showAddNotes = ref(false);
const isEdit = ref(false);
const isUploading = ref(false);
const notes = ref(props.notes);

const docForm = useForm({
  quote_id: props.quote?.id || null,
  quote_uuid: props.quote?.code || null,
  quote_type: props.modelType,
  quote_type_id: null,
  document_type_code: null,
  file: null,
});

const dateFormat = date => useDateFormat(date, 'DD-MMM-YYYY h:mm:ss a').value;

const tableHeader = reactive([
  { text: 'MODIFIED BY', value: 'created_by' },
  { text: 'MODIFIED DATE', value: 'updated_at' },
  { text: 'NOTES', value: 'note' },
  { text: 'LEAD STATUS', value: 'quote_status' },
  { text: 'ACTIONS', value: 'action' },
]);

const loader = ref(false);
const notesForm = reactive({
  notes: null,
  quote_request_id: props.quote?.id,
  quote_type: props.modelType,
  quote_status_id: props.quote?.quote_status_id,
});

const onNoteSubmit = () => {
  let notesData = {
    quoteType: notesForm.quote_type,
    quoteRequestId: notesForm.quote_request_id,
    notes: notesForm.notes,
    quoteStatusId: notesForm.quote_status_id,
  };
  loader.value = true;
  if (isEdit.value) {
    // Note: Endpoint for edit notes
    notesData['id'] = notesForm.id;
    axios
      .put('/update-quote-notes', notesData)
      .then(response => {
        if (response.status == 200) {
          let index = notes.value.findIndex(response.data.id);
          if (index != -1) {
            notes.value.splice(index, 1, response.data);
          }
          notification.success({
            title: 'Notes has been Updated',
            position: 'top',
          });
        } else {
          notification.error({
            title: 'Notes has not been updated.',
            position: 'top',
          });
        }
      })
      .catch(err => {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      })
      .finally(() => {
        loader.value = false;
      });
  } else {
    axios
      .post('/save-quote-notes', notesData)
      .then(response => {
        if (response.status == 200) {
          notes.value.push(response.data.data);
          notification.success({
            title: 'Notes has been saved',
            position: 'top',
          });
        } else {
          notification.error({
            title: 'Notes has not been saved. ',
            position: 'top',
          });
        }
      })
      .catch(err => {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      })
      .finally(() => {
        loader.value = false;
      });
  }
};

const notesLength = computed(() => notesForm.notes?.length ?? 0);

const onEditNote = data => {
  notesForm.notes = data.note;
  notesForm.id = data.id;
  showAddNotes.value = true;
  isEdit.value = true;
};

const showAddNotesModal = () => {
  notesForm.notes = null;
  notesForm.id = null;
  showAddNotes.value = true;
  isEdit.value = false;
};

const uploadFile = (doc, filesWithInfo) => {
  let url = `/quotes/${props.modelType}/documents/store`;
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
  <div>
    <x-tooltip>
      <x-button size="sm" color="emerald" @click="showModal = true">
        Notes
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
  <AppModal class="md:min-w-[900px]" v-model="showModal" show-close show-header>
    <template #header>
      <p class="font-bold m-0">Notes</p>
    </template>
    <div>
      <div class="flex justify-end">
        <x-button size="sm" color="orange" @click="showAddNotesModal()">
          Add Notes
        </x-button>
      </div>
      <DataTable
        table-class-name=""
        :headers="tableHeader"
        :items="notes.data || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
        class="mt-5"
      >
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
          <x-collapse v-else icon="chevronDown" show-icon>
            <div class="bg-gray-10 w-80">
              {{ note.slice(0, 40) }}
            </div>
            <template #content>
              <div>
                {{ note.slice(40, note.length) }}
              </div>
            </template>
          </x-collapse>
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
  >
    <template #header>
      <p class="font-bold m-0">{{ isEdit ? 'Update' : 'Add' }} Notes</p>
    </template>

    <div class="w-full">
      <x-field label="Notes" class="w-full">
        <x-textarea
          :adjustToText="false"
          maxlength="1000"
          class="w-full"
          v-model="notesForm.notes"
          rows="5"
        />
      </x-field>
      <p class="text-xs ml-auto flex justify-end mt-2">
        {{ notesLength }}/1000
      </p>
      <div class="mt-2">
        <Dropzone
          :id="documentType?.id"
          :accept="documentType?.accepted_files"
          :max-files="documentType?.max_files"
          :max-size="documentType?.max_size"
          :loading="isUploading"
          @change="uploadFile(documentType, $event)"
        />
        <!-- <x-tooltip align="top">
          <x-button size="sm" color="primary" icon="upload">
            Upload Documents
          </x-button>
          <template #tooltip>
            <span class="text-sm"
              >Use this button to attach and save documents that support your
              notes. You can drag and drop files or browse to upload them into
              the system, making it easy to store and access important
              files</span
            >
          </template>
        </x-tooltip> -->
      </div>
      <div class="mt-5 flex gap-2 justify-end">
        <x-button size="sm" @click.prevent="showAddNotes = false">
          Cancel
        </x-button>
        <x-button
          @click="onNoteSubmit"
          size="sm"
          color="emerald"
          :loading="loader"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </x-button>
      </div>
    </div>
  </AppModal>
</template>
