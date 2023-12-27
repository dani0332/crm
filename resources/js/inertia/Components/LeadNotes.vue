<script setup>
import AppModal from './AppModal.vue';

const showModal = ref(false);

const showAddNotes = ref(false);

const tableHeader = reactive([
  { text: 'MODIFIED BY', value: 'modified_by', is_active: true },
  { text: 'MODIFIED DATE', value: 'modified_date', is_active: true },
  { text: 'NOTES', value: 'notes', is_active: true },
  { text: 'LEAD STATUS', value: 'lead_status', is_active: true },
  { text: 'ACTIONS', value: 'actions', is_active: true },
]);

const data = reactive([
  {
    modified_by: 'MODIFIED BY',
    modified_date: 'code',
    notes:
      "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum",
    lead_status: 'pending',
  },
]);

const notesForm = useForm({
  notes: null,
});

const notesLength = computed(() => notesForm.notes?.length ?? 0);
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
  <AppModal class="min-w-[700px]" v-model="showModal" show-close show-header>
    <template #header>
      <p class="font-bold m-0">Notes</p>
    </template>
    <template #content> </template>
    <div>
      <div class="flex justify-end">
        <x-button size="sm" color="orange" @click="showAddNotes = true">
          Add Notes
        </x-button>
      </div>
      <DataTable
        table-class-name=""
        :headers="tableHeader"
        :items="data || []"
        border-cell
        hide-rows-per-page
        hide-footer
        fixed-checkbox
        class="mt-5"
      >
        <template #item-notes="{ notes }">
          <x-collapse icon="chevronDown" show-icon>
            <div class="bg-gray-10 w-80">
              {{ notes.slice(0, 40) }}
            </div>
            <template #content>
              <div>
                {{ notes.slice(40, notes.length) }}
              </div>
            </template>
          </x-collapse>
        </template>
      </DataTable>
      <Pagination
        :links="{
          next: data.next_page_url,
          prev: data.prev_page_url,
          current: data.current_page,
          from: data.from,
          to: data.to,
        }"
      />
    </div>
  </AppModal>

  <!-- Modal for add/Update notes related to Leads -->
  <AppModal class="min-w-[30%]" v-model="showAddNotes" show-header show-close>
    <template #header>
      <p class="font-bold m-0">Add Notes</p>
    </template>

    <form class="w-full">
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
      <div>
        <x-tooltip>
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
        </x-tooltip>
      </div>
      <div class="mt-5 flex gap-2 justify-end">
        <x-button size="sm"> Cancel </x-button>
        <x-button size="sm" color="emerald"> Save </x-button>
      </div>
    </form>
  </AppModal>
</template>